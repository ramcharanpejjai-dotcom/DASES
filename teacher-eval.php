<?php
require_once 'auth.php';
checkAccess('teacher');
require_once 'db.php';

$paper_id = intval($_GET['paper_id'] ?? 0);
$teacher_id = $_SESSION['user_id'];

// Fetch paper details
$stmt = $pdo->prepare("SELECT * FROM papers WHERE id = ? AND assigned_teacher_id = ?");
$stmt->execute([$paper_id, $teacher_id]);
$paper = $stmt->fetch();

if (!$paper) {
    die("Error: Paper not found or not assigned to you.");
}

// Check anonymous evaluation setting
$anonSettingStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'anonymous_evaluation'");
$isAnonymous = ($anonSettingStmt->fetchColumn() === '1');
$displayCandidateId = $isAnonymous ? ($paper['dummy_token'] ?: ('ANON-' . strtoupper(substr(md5($paper['id']), 0, 6)))) : $paper['student_pin'];

// Fetch existing evaluation if any (for editing or re-evaluation)
$evalStmt = $pdo->prepare("SELECT * FROM evaluations WHERE paper_id = ?");
$evalStmt->execute([$paper_id]);
$existingEval = $evalStmt->fetch();

$savedQMarks = !empty($existingEval['question_marks']) ? json_decode($existingEval['question_marks'], true) : [];
$savedSecA   = $savedQMarks['q_3m'] ?? array_fill(0, 10, 0);
$savedSecB   = $savedQMarks['q_10m'] ?? array_fill(0, 8, 0);
$savedInternal = $existingEval['internal_marks'] ?? 0;
$savedFeedback = $existingEval['feedback'] ?? '';
$savedAnnotations = $existingEval['annotations_data'] ?? '[]';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Evaluation Studio - DASES</title>
    <link rel="stylesheet" href="style.css?v=2.5">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        :root {
            --eval-bg: #0F172A;
            --eval-panel: #1E293B;
            --eval-border: #334155;
            --eval-text: #F8FAFC;
        }

        body.eval-page {
            margin: 0;
            padding: 0;
            background: #F1F5F9;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Guarantee floating overlays never block studio controls or canvas */
        .eval-page #dases-mascot-container,
        .eval-page #dases-theme-bar,
        .eval-page #dases-particle-canvas,
        #dases-mascot-container,
        #dases-theme-bar,
        #dases-particle-canvas {
            display: none !important;
        }

        /* Top Studio Header */
        .eval-header {
            height: 56px;
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            z-index: 50;
        }

        .eval-header-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .candidate-badge {
            background: #EFF6FF;
            color: #1D4ED8;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #BFDBFE;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .eval-layout {
            display: flex;
            flex: 1;
            height: calc(100vh - 56px);
            overflow: hidden;
        }

        /* Left: Script Annotation Workspace */
        .script-workspace {
            flex: 1.35;
            display: flex;
            flex-direction: column;
            background: #334155;
            position: relative;
            overflow: hidden;
            border-right: 1px solid #CBD5E1;
        }

        /* Floating / Sticky Annotation Toolbar */
        .annotation-toolbar {
            height: 48px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            color: white;
            z-index: 20;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .tool-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tool-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            color: #F8FAFC;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
        }

        .tool-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-1px);
        }

        .tool-btn.active {
            background: #6366F1 !important;
            border-color: #818CF8 !important;
            color: white !important;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
        }

        .tool-btn.danger-tool:hover {
            background: #EF4444;
            border-color: #F87171;
        }

        /* PDF & Canvas Viewport */
        .script-viewport {
            flex: 1;
            overflow-y: auto;
            overflow-x: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            user-select: none;
            background: #475569;
        }

        .pdf-page-container {
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            background: white;
            border-radius: 4px;
            transition: transform 0.2s ease;
        }

        .pdf-canvas-layer {
            display: block;
        }

        .drawing-canvas-layer {
            position: absolute;
            top: 0;
            left: 0;
            cursor: crosshair;
            touch-action: none;
        }

        /* Right: Evaluation Form & Scoring */
        .eval-sidebar {
            flex: 0.95;
            background: #FFFFFF;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            box-shadow: -4px 0 20px rgba(0,0,0,0.05);
        }

        .q-section {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 16px;
        }

        .q-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .q-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0F172A;
            margin: 0;
        }

        .q-grid-5 {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        .q-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .q-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .q-item label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #475569;
        }

        .q-item input {
            width: 100%;
            padding: 8px 4px;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 700;
            border: 1.5px solid #CBD5E1;
            border-radius: 8px;
            outline: none;
            background: #FFFFFF;
            transition: all 0.2s ease;
        }

        .q-item input:focus {
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            transform: translateY(-2px);
        }

        /* Summary Score Panel */
        .summary-card {
            background: linear-gradient(135deg, #1E1B4B 0%, #312E81 50%, #4338CA 100%);
            color: white;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 10px 25px -5px rgba(67, 56, 202, 0.4);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #E0E7FF;
        }

        .summary-grand {
            font-size: 1.25rem;
            font-weight: 800;
            color: #FFFFFF;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 8px;
            margin-top: 6px;
        }

        .progress-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            height: 10px;
            width: 100%;
            margin-top: 12px;
            overflow: hidden;
        }

        .progress-bar {
            background: #10B981;
            height: 100%;
            width: 0%;
            border-radius: 10px;
            transition: width 0.4s ease-out, background-color 0.4s ease;
        }

        .result-badge {
            margin-top: 14px;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 800;
            text-align: center;
            letter-spacing: 0.5px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.15);
        }

        .btn-submit-eval {
            width: 100%;
            padding: 14px;
            font-size: 1.05rem;
            font-weight: 800;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit-eval:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.5);
        }

        .auto-save-pill {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748B;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Full Screen Workspace */
        .fullscreen-active .eval-sidebar {
            display: none;
        }
        .fullscreen-active .script-workspace {
            flex: 1;
        }
    </style>
</head>
<body class="eval-page">
    <!-- Top Studio Header -->
    <header class="eval-header">
        <div class="eval-header-title">
            <a href="teacher-dashboard.php?tab=pending" class="btn-secondary" style="padding: 6px 12px; font-size: 0.85rem; text-decoration: none; border-radius: 6px;">
                ← Back to Papers
            </a>
            <div>
                <strong style="font-size: 1.05rem; color: #0F172A;"><?php echo htmlspecialchars($paper['subject_name'] ?? 'Subject'); ?></strong>
                <span style="font-size: 0.82rem; color: #64748B; margin-left: 8px;">Code: <?php echo htmlspecialchars($paper['subject_code']); ?></span>
            </div>
            <div class="candidate-badge">
                <?php if ($isAnonymous): ?>
                    <span style="display:inline-flex; align-items:center; gap:5px;"><i data-lucide="shield" class="icon-xs"></i> Masked ID: <strong><?php echo htmlspecialchars($displayCandidateId); ?></strong></span>
                <?php else: ?>
                    <span style="display:inline-flex; align-items:center; gap:5px;"><i data-lucide="graduation-cap" class="icon-xs"></i> Candidate PIN: <strong><?php echo htmlspecialchars($displayCandidateId); ?></strong></span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 14px;">
            <span class="auto-save-pill" id="draft-status-indicator">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10B981;"></span> Auto-Save Ready
            </span>
            <button type="button" onclick="toggleDarkMode()" id="btn-theme-toggle" class="tool-btn" title="Toggle Theme" aria-label="Toggle Theme" style="padding: 6px 10px;">
                <i data-lucide="moon" class="icon-sm"></i>
            </button>
            <button type="button" class="tool-btn" onclick="saveDraftManual()" title="Save current progress as draft">
                <i data-lucide="save" class="icon-xs"></i> Save Draft
            </button>
            <button type="button" class="tool-btn" onclick="toggleFullScreen()" id="btn-fullscreen" title="Full-Screen Focus Mode">
                <i data-lucide="maximize-2" class="icon-xs"></i> Focus Mode
            </button>
        </div>
    </header>

    <!-- Workspace Container -->
    <div class="eval-layout" id="eval-layout-container">
        <!-- Left: Script Annotation Workspace -->
        <section class="script-workspace">
            <div class="annotation-toolbar">
                <div class="tool-group">
                    <button type="button" class="tool-btn active" id="tool-pen" onclick="setTool('pen')" title="Red Marking Pen (Alt+P)">
                        <i data-lucide="edit-3" class="icon-xs" style="color:#EF4444;"></i> Red Pen
                    </button>
                    <button type="button" class="tool-btn" id="tool-tick" onclick="setTool('tick')" title="Tick Stamp (Alt+T)">
                        <i data-lucide="check" class="icon-xs" style="color:#10B981;"></i> Tick
                    </button>
                    <button type="button" class="tool-btn" id="tool-cross" onclick="setTool('cross')" title="Cross Stamp (Alt+X)">
                        <i data-lucide="x" class="icon-xs" style="color:#EF4444;"></i> Cross
                    </button>
                    <button type="button" class="tool-btn" id="tool-comment" onclick="setTool('comment')" title="Sticky Note Comment">
                        <i data-lucide="message-square" class="icon-xs" style="color:#3B82F6;"></i> Note
                    </button>
                </div>

                <div class="tool-group">
                    <button type="button" class="tool-btn" onclick="zoomIn()" title="Zoom In (+)">
                        <i data-lucide="zoom-in" class="icon-xs"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="zoomOut()" title="Zoom Out (-)">
                        <i data-lucide="zoom-out" class="icon-xs"></i>
                    </button>
                    <button type="button" class="tool-btn" onclick="resetZoom()" title="Reset Zoom">
                        100%
                    </button>
                    <button type="button" class="tool-btn" onclick="rotateScript()" title="Rotate Script 90°">
                        <i data-lucide="rotate-cw" class="icon-xs"></i> Rotate
                    </button>
                </div>

                <div class="tool-group">
                    <button type="button" class="tool-btn danger-tool" onclick="undoAnnotation()" title="Undo Last Mark (Alt+Z)">
                        <i data-lucide="undo-2" class="icon-xs"></i> Undo
                    </button>
                    <button type="button" class="tool-btn danger-tool" onclick="clearCurrentPageAnnotations()" title="Clear Page Annotations">
                        <i data-lucide="trash-2" class="icon-xs"></i> Clear
                    </button>
                </div>
            </div>

            <!-- PDF Scrollable Canvas Viewport -->
            <div class="script-viewport" id="script-viewport">
                <div id="pdf-loading-msg" style="color: white; margin-top: 60px; font-weight: 600; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i data-lucide="loader-2" class="icon-sm" style="animation: spin 1s linear infinite;"></i> Rendering high-resolution script pages...
                </div>
            </div>
        </section>

        <!-- Right: Evaluation Form & Scoring Matrix -->
        <section class="eval-sidebar">
            <form id="eval-form-main" action="submit_eval.php" method="POST" onsubmit="return finalizeSubmission(event)">
                <input type="hidden" name="paper_id" value="<?php echo $paper['id']; ?>">
                <input type="hidden" name="annotations_data" id="inp_annotations_data" value="">
                <input type="hidden" name="external_score" id="inp_external" value="0">
                <input type="hidden" name="total_score" id="inp_grand" value="0">

                <!-- Section A: 10 Questions (Max 3M each) -->
                <div class="q-section">
                    <div class="q-section-header">
                        <h3 class="q-section-title">Section A: Short Questions (3M each | Max 30)</h3>
                        <span style="font-size: 0.8rem; font-weight: 700; color: #6366F1;" id="header-sec-a-total">0 / 30</span>
                    </div>
                    <div class="q-grid-5">
                        <?php for ($i = 1; $i <= 10; $i++): 
                            $valA = $savedSecA[$i - 1] ?? 0;
                        ?>
                            <div class="q-item">
                                <label>Q<?php echo $i; ?></label>
                                <input type="number" step="0.5" min="0" max="3" class="sec-a" name="q_3m[]" value="<?php echo htmlspecialchars($valA); ?>" oninput="calculateTotal()" onfocus="this.select()">
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Section B: 8 Questions (Max 10M each | Best 5 Scored: Max 50M) -->
                <div class="q-section">
                    <div class="q-section-header">
                        <h3 class="q-section-title">Section B: Long Questions (10M each | Best 5 Scored | Max 50)</h3>
                        <span style="font-size: 0.8rem; font-weight: 700; color: #6366F1;" id="header-sec-b-total">0 / 50</span>
                    </div>
                    <p style="font-size: 0.78rem; color: #64748B; margin: 0 0 10px 0;">Student may attempt any questions. Enter marks below — system automatically calculates the <strong>Best 5 highest marks</strong>.</p>
                    <div class="q-grid-4">
                        <?php for ($i = 1; $i <= 8; $i++): 
                            $valB = $savedSecB[$i - 1] ?? 0;
                        ?>
                            <div class="q-item" id="q-item-b-<?php echo $i; ?>">
                                <label style="display:flex; justify-content:space-between; align-items:center;">
                                    <span>Q<?php echo $i; ?></span>
                                    <span class="q-b-badge" id="badge-b-<?php echo $i; ?>" style="font-size: 0.65rem; font-weight: 700; color: #94A3B8;">—</span>
                                </label>
                                <input type="number" step="0.5" min="0" max="10" class="sec-b" data-q-index="<?php echo $i; ?>" name="q_10m[]" value="<?php echo htmlspecialchars($valB); ?>" oninput="calculateTotal()" onfocus="this.select()">
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Internal Marks -->
                <div class="q-section">
                    <div class="q-section-header">
                        <h3 class="q-section-title">Internal Marks (Max 20)</h3>
                        <span style="font-size: 0.8rem; font-weight: 700; color: #6366F1;" id="header-internal-disp">0 / 20</span>
                    </div>
                    <div class="q-item" style="max-width: 140px;">
                        <input type="number" step="0.5" min="0" max="20" id="internal_marks" name="internal_marks" value="<?php echo htmlspecialchars($savedInternal); ?>" oninput="calculateTotal()" required onfocus="this.select()">
                    </div>
                </div>

                <!-- Remarks / Feedback -->
                <div class="q-section">
                    <h3 class="q-section-title" style="margin-bottom: 8px;">Evaluator Remarks & Feedback</h3>
                    <textarea name="feedback" rows="2" style="width: 100%; box-sizing: border-box; padding: 10px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-family: inherit; font-size: 0.88rem; resize: vertical;" placeholder="Add remarks for moderation or student feedback..."><?php echo htmlspecialchars($savedFeedback); ?></textarea>
                </div>

                <!-- Live Score Summary Card -->
                <div class="summary-card">
                    <div class="summary-row"><span>Section A (Max 30):</span> <span id="disp-sec-a">0 / 30</span></div>
                    <div class="summary-row"><span>Section B (Max 50):</span> <span id="disp-sec-b">0 / 50</span></div>
                    <div class="summary-row"><span>External Total (Max 80):</span> <span id="disp-external">0 / 80</span></div>
                    <div class="summary-row"><span>Internal Marks (Max 20):</span> <span id="disp-internal">0 / 20</span></div>
                    <div class="summary-row summary-grand">
                        <span>Grand Total:</span> <span id="disp-grand">0 / 100</span>
                    </div>

                    <div class="progress-container">
                        <div class="progress-bar" id="animated-progress"></div>
                    </div>

                    <div class="result-badge" id="result-badge">
                        <i data-lucide="calculator" class="icon-xs"></i> Enter marks to calculate result
                    </div>
                </div>

                <button type="submit" class="btn-submit-eval" id="btn-submit-final">
                    <i data-lucide="check-circle" class="icon-sm"></i> Submit Final Evaluation
                </button>
            </form>
        </section>
    </div>

    <!-- PDF.js & Annotation Canvas Engine -->
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        const pdfUrl = <?php echo json_encode($paper['file_path']); ?>;
        let pdfDoc = null;
        let scale = 1.15;
        let rotationAngle = 0;
        let currentTool = 'pen'; // 'pen', 'tick', 'cross', 'comment'
        
        // Annotations array: array of objects { pageNum, type: 'stroke'|'stamp'|'comment', data }
        let annotations = <?php echo !empty($savedAnnotations) ? $savedAnnotations : '[]'; ?>;
        let undoStack = [];

        // Active drawing state
        let isDrawing = false;
        let currentStroke = null;

        // Render PDF onto layered canvas
        async function loadAndRenderPDF() {
            try {
                const loadingTask = pdfjsLib.getDocument(pdfUrl);
                pdfDoc = await loadingTask.promise;
                document.getElementById('pdf-loading-msg').style.display = 'none';
                renderAllPages();
            } catch (err) {
                console.warn('PDF.js render fallback to iframe:', err);
                const viewport = document.getElementById('script-viewport');
                viewport.innerHTML = `
                    <div style="width:100%; height:100%; min-height:800px;">
                        <iframe src="${pdfUrl}" width="100%" height="800px" style="border:none; border-radius:8px; background:white;"></iframe>
                    </div>
                `;
            }
        }

        async function renderAllPages() {
            const viewport = document.getElementById('script-viewport');
            viewport.innerHTML = '';

            for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
                const page = await pdfDoc.getPage(pageNum);
                const baseViewport = page.getViewport({ scale: scale, rotation: rotationAngle });

                const container = document.createElement('div');
                container.className = 'pdf-page-container';
                container.id = `page-container-${pageNum}`;
                container.style.width = `${baseViewport.width}px`;
                container.style.height = `${baseViewport.height}px`;

                // PDF Background Canvas
                const pdfCanvas = document.createElement('canvas');
                pdfCanvas.className = 'pdf-canvas-layer';
                pdfCanvas.width = baseViewport.width;
                pdfCanvas.height = baseViewport.height;
                const pdfCtx = pdfCanvas.getContext('2d');

                await page.render({ canvasContext: pdfCtx, viewport: baseViewport }).promise;

                // Drawing & Annotation Overlay Canvas
                const drawCanvas = document.createElement('canvas');
                drawCanvas.className = 'drawing-canvas-layer';
                drawCanvas.width = baseViewport.width;
                drawCanvas.height = baseViewport.height;
                drawCanvas.dataset.pageNum = pageNum;

                setupCanvasListeners(drawCanvas, pageNum);

                container.appendChild(pdfCanvas);
                container.appendChild(drawCanvas);
                viewport.appendChild(container);

                redrawPageAnnotations(pageNum);
            }
        }

        function setupCanvasListeners(canvas, pageNum) {
            const ctx = canvas.getContext('2d');

            canvas.addEventListener('mousedown', (e) => {
                const rect = canvas.getBoundingClientRect();
                const x = (e.clientX - rect.left);
                const y = (e.clientY - rect.top);

                if (currentTool === 'pen') {
                    isDrawing = true;
                    currentStroke = {
                        pageNum: pageNum,
                        type: 'stroke',
                        color: '#EF4444',
                        lineWidth: 2.5,
                        points: [{ x, y }]
                    };
                } else if (currentTool === 'tick') {
                    dropStamp(pageNum, 'tick', x, y);
                } else if (currentTool === 'cross') {
                    dropStamp(pageNum, 'cross', x, y);
                } else if (currentTool === 'comment') {
                    const text = prompt('Enter Evaluator Sticky Note / Comment:');
                    if (text && text.trim()) {
                        dropComment(pageNum, text.trim(), x, y);
                    }
                }
            });

            canvas.addEventListener('mousemove', (e) => {
                if (!isDrawing || !currentStroke || currentStroke.pageNum !== pageNum) return;
                const rect = canvas.getBoundingClientRect();
                const x = (e.clientX - rect.left);
                const y = (e.clientY - rect.top);
                currentStroke.points.push({ x, y });

                // Draw live line
                ctx.strokeStyle = currentStroke.color;
                ctx.lineWidth = currentStroke.lineWidth;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                const len = currentStroke.points.length;
                if (len > 1) {
                    ctx.beginPath();
                    ctx.moveTo(currentStroke.points[len - 2].x, currentStroke.points[len - 2].y);
                    ctx.lineTo(currentStroke.points[len - 1].x, currentStroke.points[len - 1].y);
                    ctx.stroke();
                }
            });

            const stopDrawing = () => {
                if (isDrawing && currentStroke && currentStroke.points.length > 1) {
                    annotations.push(currentStroke);
                    undoStack.push(currentStroke);
                    updateDraftIndicator();
                }
                isDrawing = false;
                currentStroke = null;
            };

            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseleave', stopDrawing);
        }

        function dropStamp(pageNum, type, x, y) {
            const stamp = {
                pageNum: pageNum,
                type: type,
                x: x,
                y: y
            };
            annotations.push(stamp);
            undoStack.push(stamp);
            redrawPageAnnotations(pageNum);
            updateDraftIndicator();
        }

        function dropComment(pageNum, text, x, y) {
            const comment = {
                pageNum: pageNum,
                type: 'comment',
                text: text,
                x: x,
                y: y
            };
            annotations.push(comment);
            undoStack.push(comment);
            redrawPageAnnotations(pageNum);
            updateDraftIndicator();
        }

        function redrawPageAnnotations(pageNum) {
            const canvas = document.querySelector(`.drawing-canvas-layer[data-page-num="${pageNum}"]`);
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const pageItems = annotations.filter(a => a.pageNum === pageNum);

            for (const item of pageItems) {
                if (item.type === 'stroke' && item.points && item.points.length > 1) {
                    ctx.strokeStyle = item.color || '#EF4444';
                    ctx.lineWidth = item.lineWidth || 2.5;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.beginPath();
                    ctx.moveTo(item.points[0].x, item.points[0].y);
                    for (let i = 1; i < item.points.length; i++) {
                        ctx.lineTo(item.points[i].x, item.points[i].y);
                    }
                    ctx.stroke();
                } else if (item.type === 'tick') {
                    // Draw nice Green Checkmark (✓)
                    ctx.strokeStyle = '#10B981';
                    ctx.lineWidth = 3.5;
                    ctx.lineCap = 'round';
                    ctx.beginPath();
                    ctx.moveTo(item.x - 10, item.y);
                    ctx.lineTo(item.x - 3, item.y + 10);
                    ctx.lineTo(item.x + 14, item.y - 12);
                    ctx.stroke();
                } else if (item.type === 'cross') {
                    // Draw nice Red Crossmark (✗)
                    ctx.strokeStyle = '#EF4444';
                    ctx.lineWidth = 3.5;
                    ctx.lineCap = 'round';
                    ctx.beginPath();
                    ctx.moveTo(item.x - 8, item.y - 8);
                    ctx.lineTo(item.x + 8, item.y + 8);
                    ctx.moveTo(item.x + 8, item.y - 8);
                    ctx.lineTo(item.x - 8, item.y + 8);
                    ctx.stroke();
                } else if (item.type === 'comment') {
                    // Draw Callout Box
                    ctx.fillStyle = '#FEF08A';
                    ctx.strokeStyle = '#EAB308';
                    ctx.lineWidth = 1.5;
                    ctx.beginPath();
                    ctx.roundRect(item.x, item.y - 24, Math.max(120, item.text.length * 7.5), 26, 6);
                    ctx.fill();
                    ctx.stroke();

                    ctx.fillStyle = '#713F12';
                    ctx.font = 'bold 11px sans-serif';
                    ctx.fillText(item.text, item.x + 6, item.y - 7);
                }
            }
        }

        // Toolbar Action Handlers
        function setTool(toolName) {
            currentTool = toolName;
            document.querySelectorAll('.annotation-toolbar .tool-btn').forEach(b => b.classList.remove('active'));
            const btn = document.getElementById(`tool-${toolName}`);
            if (btn) btn.classList.add('active');
        }

        function zoomIn() {
            scale = Math.min(scale + 0.15, 2.5);
            renderAllPages();
        }

        function zoomOut() {
            scale = Math.max(scale - 0.15, 0.65);
            renderAllPages();
        }

        function resetZoom() {
            scale = 1.15;
            renderAllPages();
        }

        function rotateScript() {
            rotationAngle = (rotationAngle + 90) % 360;
            renderAllPages();
        }

        function undoAnnotation() {
            if (undoStack.length === 0) return;
            const last = undoStack.pop();
            const index = annotations.lastIndexOf(last);
            if (index > -1) {
                annotations.splice(index, 1);
                redrawPageAnnotations(last.pageNum);
                updateDraftIndicator();
            }
        }

        function clearCurrentPageAnnotations() {
            if (!confirm('Clear all marks and annotations on all pages?')) return;
            annotations = [];
            undoStack = [];
            if (pdfDoc) {
                for (let i = 1; i <= pdfDoc.numPages; i++) {
                    redrawPageAnnotations(i);
                }
            }
            updateDraftIndicator();
        }

        function toggleFullScreen() {
            const layout = document.getElementById('eval-layout-container');
            layout.classList.toggle('fullscreen-active');
            const btn = document.getElementById('btn-fullscreen');
            if (layout.classList.contains('fullscreen-active')) {
                btn.innerHTML = '<i data-lucide="minimize-2" class="icon-xs"></i> Split Mode';
            } else {
                btn.innerHTML = '<i data-lucide="maximize-2" class="icon-xs"></i> Focus Mode';
            }
            if (window.lucide) lucide.createIcons();
        }

        // Auto-Save Draft System
        function updateDraftIndicator(msg = 'Unsaved Changes') {
            const ind = document.getElementById('draft-status-indicator');
            ind.innerHTML = `<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#F59E0B;"></span> ${msg}`;
        }

        async function saveDraftManual() {
            const ind = document.getElementById('draft-status-indicator');
            ind.innerHTML = `Saving draft...`;

            document.getElementById('inp_annotations_data').value = JSON.stringify(annotations);
            const form = document.getElementById('eval-form-main');
            const formData = new FormData(form);
            formData.append('is_draft', '1');

            try {
                const res = await fetch('submit_eval.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    ind.innerHTML = `<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10B981;"></span> Saved (${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})})`;
                } else {
                    ind.innerHTML = `<span style="color:#EF4444; display:inline-flex; align-items:center; gap:4px;"><i data-lucide="alert-circle" class="icon-xs"></i> Save failed</span>`;
                    if (window.lucide) lucide.createIcons();
                }
            } catch (e) {
                ind.innerHTML = `<span style="display:inline-flex; align-items:center; gap:4px;"><i data-lucide="save" class="icon-xs"></i> Saved locally</span>`;
                if (window.lucide) lucide.createIcons();
                localStorage.setItem(`dases_draft_${<?php echo $paper['id']; ?>}`, JSON.stringify(annotations));
            }
        }

        // Periodic auto-save every 45s
        setInterval(() => {
            saveDraftManual();
        }, 45000);

        // Scoring Calculation Matrix
        function calculateTotal() {
            // Section A
            let secAInputs = document.querySelectorAll('.sec-a');
            let totalA = 0;
            secAInputs.forEach(input => {
                let val = parseFloat(input.value) || 0;
                totalA += Math.min(Math.max(val, 0), 3);
            });

            // Section B: 8 Questions, Max 10M each, Best 5 counted (Max 50M)
            let secBInputs = document.querySelectorAll('.sec-b');
            let itemsB = [];

            secBInputs.forEach((input, index) => {
                let val = parseFloat(input.value) || 0;
                val = Math.min(Math.max(val, 0), 10);
                itemsB.push({ index: index, qNum: index + 1, val: val, input: input });
            });

            // Sort descending by value (preserve order for equals)
            let sortedB = [...itemsB].sort((a, b) => b.val - a.val);

            // Select up to top 5 items with highest marks
            let best5Indices = new Set();
            let sumBest5 = 0;
            let countCounted = 0;

            for (let i = 0; i < sortedB.length; i++) {
                if (countCounted < 5 && sortedB[i].val > 0) {
                    best5Indices.add(sortedB[i].index);
                    sumBest5 += sortedB[i].val;
                    countCounted++;
                }
            }

            // Update badges and styling for each Section B question
            itemsB.forEach(item => {
                const badge = document.getElementById('badge-b-' + item.qNum);
                const qBox = document.getElementById('q-item-b-' + item.qNum);
                
                if (best5Indices.has(item.index)) {
                    if (badge) {
                        badge.innerText = '⭐ Counted';
                        badge.style.color = '#10B981';
                    }
                    if (qBox) {
                        qBox.style.borderColor = '#10B981';
                        item.input.style.borderColor = '#10B981';
                        item.input.style.backgroundColor = 'rgba(16, 185, 129, 0.08)';
                    }
                } else if (item.val > 0) {
                    if (badge) {
                        badge.innerText = 'Extra';
                        badge.style.color = '#F59E0B';
                    }
                    if (qBox) {
                        qBox.style.borderColor = '#E2E8F0';
                        item.input.style.borderColor = '#CBD5E1';
                        item.input.style.backgroundColor = 'rgba(245, 158, 11, 0.08)';
                    }
                } else {
                    if (badge) {
                        badge.innerText = '—';
                        badge.style.color = '#94A3B8';
                    }
                    if (qBox) {
                        qBox.style.borderColor = '#E2E8F0';
                        item.input.style.borderColor = '#CBD5E1';
                        item.input.style.backgroundColor = '';
                    }
                }
            });

            let totalB = Math.min(50, sumBest5);

            let externalTotal = Math.min(80, totalA + totalB);
            let internalVal = Math.min(20, parseFloat(document.getElementById('internal_marks').value) || 0);
            let grandTotal = Math.min(100, Math.round(externalTotal + internalVal));

            // Update UI elements
            document.getElementById('header-sec-a-total').innerText = totalA + ' / 30';
            document.getElementById('header-sec-b-total').innerText = totalB + ' / 50';
            document.getElementById('header-internal-disp').innerText = internalVal + ' / 20';

            document.getElementById('disp-sec-a').innerText = totalA + ' / 30';
            document.getElementById('disp-sec-b').innerText = totalB + ' / 50';
            document.getElementById('disp-external').innerText = externalTotal + ' / 80';
            document.getElementById('disp-internal').innerText = internalVal + ' / 20';
            document.getElementById('disp-grand').innerText = grandTotal + ' / 100';

            // Progress Bar
            const progressBar = document.getElementById('animated-progress');
            progressBar.style.width = grandTotal + '%';
            if (grandTotal < 35) {
                progressBar.style.backgroundColor = '#EF4444';
            } else if (grandTotal < 70) {
                progressBar.style.backgroundColor = '#F59E0B';
            } else {
                progressBar.style.backgroundColor = '#10B981';
            }

            // Pass/Fail Badge
            const badge = document.getElementById('result-badge');
            if (grandTotal >= 35) {
                badge.innerHTML = `<span style="display:inline-flex; align-items:center; gap:5px;"><i data-lucide="check-circle-2" class="icon-xs"></i> PASS — Score ${grandTotal} / 100</span>`;
                badge.style.background = 'rgba(16, 185, 129, 0.25)';
                badge.style.color = '#D1FAE5';
                badge.style.borderColor = 'rgba(16, 185, 129, 0.5)';
            } else {
                badge.innerHTML = `<span style="display:inline-flex; align-items:center; gap:5px;"><i data-lucide="x-circle" class="icon-xs"></i> FAIL — Score ${grandTotal} / 100 (Pass: 35)</span>`;
                badge.style.background = 'rgba(239, 68, 68, 0.25)';
                badge.style.color = '#FEE2E2';
                badge.style.borderColor = 'rgba(239, 68, 68, 0.5)';
            }
            if (window.lucide) lucide.createIcons();

            document.getElementById('inp_external').value = externalTotal;
            document.getElementById('inp_grand').value = grandTotal;
        }

        function finalizeSubmission(e) {
            document.getElementById('inp_annotations_data').value = JSON.stringify(annotations);
            return confirm('Are you sure you want to submit this final evaluation? Marks and script annotations will be locked.');
        }

        // Global Keyboard Shortcuts
        window.addEventListener('keydown', (e) => {
            if (e.altKey && e.key.toLowerCase() === 'p') { setTool('pen'); }
            if (e.altKey && e.key.toLowerCase() === 't') { setTool('tick'); }
            if (e.altKey && e.key.toLowerCase() === 'x') { setTool('cross'); }
            if (e.altKey && e.key.toLowerCase() === 'z') { undoAnnotation(); }
            if (e.altKey && e.key.toLowerCase() === 's') { e.preventDefault(); saveDraftManual(); }
        });

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadAndRenderPDF();
            calculateTotal();
            if (window.lucide) lucide.createIcons();
        });
    </script>
    <script src="animations.js?v=3.0.1788777855" defer></script>
</body>
</html>