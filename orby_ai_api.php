<?php
/**
 * DASES ? Orby AI Gemini Backend Gateway
 * Powered by Google Gemini API
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

// Load local environment / config if present (never committed to git)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

$geminiKey = getenv('GEMINI_API_KEY') ?: (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
define('GOOGLE_GEMINI_API_KEY', $geminiKey);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['message'])) {
    echo json_encode(['success' => false, 'error' => 'No message provided.']);
    exit;
}

$userMessage = trim($input['message']);
$chatHistory = $input['history'] ?? [];
$pageContext = $input['context'] ?? [];
$userRole = $_SESSION['user_role'] ?? ($pageContext['role'] ?? 'guest');
$userName = $_SESSION['user_name'] ?? 'User';

$systemPrompt = "You are Orby, the AI companion for DASES (Digital Answer Script Evaluation System).

DASES KNOWLEDGE:
- DASES = Digital Answer Script Evaluation System: a state-of-the-art web platform that digitizes and streamlines exam paper evaluation.
- Semesters Progression:
  * Sem-1: Evaluates the combined 1st & 2nd semesters (1st Year) as a unified curriculum.
  * Sem-3, Sem-4, Sem-5: Subsequent progressive semesters.
- Examination & Marking Structure:
  * Section A: 10 mandatory questions, 3 marks each = Max 30 marks.
  * Section B: 8 questions, 10 marks each. DASES automatically picks the BEST 5 highest scores = Max 50 marks.
  * Internal Assessment: Max 20 marks. Grand Total: Max 100 marks. PASS = minimum 35/100 total score.

- SBTET AP C-23 GRADE POINT SCALE & FORMULAS:
  * 90% and above: Grade O (Outstanding) -> 10 Grade Points
  * 80% to 89%: Grade A+ (Excellent) -> 9 Grade Points
  * 70% to 79%: Grade A (Very Good) -> 8 Grade Points
  * 60% to 69%: Grade B+ (Good) -> 7 Grade Points
  * 50% to 59%: Grade B (Above Average) -> 6 Grade Points
  * 40% to 49%: Grade C (Pass) -> 5 Grade Points
  * Below 40%: Grade F (Fail / Backlog) -> 0 Grade Points
  * SGPA Formula (Single Semester): SGPA = Sum(Course Credits * Grade Points Earned) / Sum(Total Credits of the Semester).
  * CGPA Formula (Overall Diploma): CGPA = Sum(SGPA_n * Total Credits_n) / Sum(Total Credits of All Semesters).
  * CGPA to Percentage Formula: Percentage (%) = (CGPA - 0.5) * 10. Example: (8.5 - 0.5) * 10 = 80%.

- SBTET AP C-23 CURRICULUM BRANCHES & SUBJECTS:
  * Computer Engineering (CME / CM):
    - 1st Year (Sem-1): CM-101 English, CM-102 Maths-I, CM-103 Physics, CM-104 Chemistry & Env, CM-105 Basics of Comp Engg, CM-106 Prog in C, CM-107 Drawing, CM-108 C Lab, CM-109 Physics Lab, CM-110 Chemistry Lab, CM-111 Computer Fundamentals Lab.
    - Sem-3: CM-301 Maths-II, CM-302 Digital Electronics, CM-303 OS, CM-304 Data Structures, CM-305 DBMS, CM-306 DS Lab, CM-307 DBMS Lab, CM-308 DE & OS Lab.
    - Sem-4: CM-401 Software Engg, CM-402 Web Tech, CM-403 Computer Org & Microprocessors, CM-404 Java, CM-405 Networks & Cyber Security, Labs: CM-406 to CM-409.
    - Sem-5: CM-501 Industrial Mgmt, CM-502 Big Data & Cloud, CM-503 Android, CM-504 IoT, CM-505 Python, Labs: CM-506 to CM-508.
  * Electronics & Communication (ECE / EC): EC-101 to EC-111 (1st Year), EC-301 to EC-308 (Sem-3), EC-401 to EC-408 (Sem-4), EC-501 to EC-508 (Sem-5).
  * Mechanical Engineering (ME / M): M-101 to M-110 (1st Year), M-301 to M-308 (Sem-3), M-401 to M-410 (Sem-4), M-501 to M-508 (Sem-5).
  * Electrical & Electronics (EEE / EE): EE-101 to EE-110 (1st Year), EE-301 to EE-308 (Sem-3), EE-401 to EE-408 (Sem-4), EE-501 to EE-508 (Sem-5).
  * Civil Engineering (CE / C): C-101 to C-110 (1st Year), C-301 to C-310 (Sem-3), C-401 to C-409 (Sem-4), C-501 to C-508 (Sem-5).

- Portals:
  * Student Dashboard (student-dashboard.php): Private student portal to view semester results (Sem-1, Sem-3, Sem-4, Sem-5), credit-weighted SGPA/CGPA, official equivalent percentage, Section A & B score details, download verified printable marksheets, and apply for re-evaluation.
  * Teacher Studio (teacher-eval.php): Digital evaluation with annotations, rubrics, and autosave.
  * Admin Dashboard (admin-dashboard.php): Upload single/bulk scripts with C-23 branch & subject auto-fill, assign evaluators, monitor status, and manage re-evaluation petitions.

STRICT CONFIDENTIALITY & SAFETY RULES:
- Anonymity: Papers are graded blindly with masked tokens (e.g. ANON-XXXXXX). Under NO circumstances should you reveal or guess teacher/evaluator names or identities to students.
- Script Confidentiality: In accordance with university examination protocols, students do NOT view scanned answer sheets or examiner ink markings. They only receive official marksheets and scores.
- Integrity: Never alter scores or suggest that marks can be changed without going through the formal re-evaluation process.

User role: {$userRole}. Page: " . ($pageContext['pageTitle'] ?? 'DASES Portal') . ".
Always answer the user directly. Be helpful, polite, and encouraging. Use bullet points and bold formatting for readability.";

$modelsToTry = [
    'gemini-3.6-flash',
    'gemini-flash-latest',
    'gemini-3-flash-preview',
    'gemini-3.1-flash-lite'
];

$contents = [];
if (is_array($chatHistory)) {
    foreach (array_slice($chatHistory, -8) as $turn) {
        $role = ($turn['role'] === 'user') ? 'user' : 'model';
        $text = trim($turn['text'] ?? $turn['parts'][0]['text'] ?? '');
        if (!empty($text)) {
            $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
        }
    }
}
$contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

$payload = [
    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
    'contents' => $contents,
    'generationConfig' => ['temperature' => 0.7, 'topP' => 0.95, 'maxOutputTokens' => 1000]
];

$responseOutput = null;
$chosenModel = null;
$lastError = null;

if (!empty(GOOGLE_GEMINI_API_KEY)) {
    foreach ($modelsToTry as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode(GOOGLE_GEMINI_API_KEY);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $rawRes = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && $rawRes) {
            $jsonRes = json_decode($rawRes, true);
            $candidateText = $jsonRes['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($candidateText) {
                $responseOutput = $candidateText;
                $chosenModel = $model;
                break;
            }
        } else {
            $lastError = "HTTP $httpCode: " . ($curlErr ?: substr($rawRes, 0, 300));
        }
    }
}

if ($responseOutput !== null) {
    echo json_encode(['success' => true, 'reply' => $responseOutput, 'model' => $chosenModel]);
} else {
    echo json_encode([
        'success' => true,
        'reply' => getSmartFallbackResponse($userMessage, $userRole),
        'fallback' => true,
        'debug' => $lastError
    ]);
}

function getSmartFallbackResponse($query, $role) {
    $q = strtolower($query);

    if (strpos($q, 'what is dases') !== false || strpos($q, 'about dases') !== false || strpos($q, 'overview') !== false || strpos($q, 'explain dases') !== false || strpos($q, 'what does dases') !== false || strpos($q, 'dases stand') !== false) {
        return "**DASES** stands for **Digital Answer Script Evaluation System** ? a modern web platform that digitizes and streamlines exam paper evaluation.\n\n**Key Features:**\n? **Bulk PDF Upload** ? Digitize physical answer sheets\n? **Blind Evaluation** ? Student identities hidden via tokens (e.g. ANON-4F1A)\n? **Teacher Digital Studio** ? Annotate PDFs with pens, stamps, and comments\n? **Auto Scoring** ? Best 5 of 8 Section B calculated automatically\n? **Student Result Portal** ? View marks, download grade cards, apply re-evaluation\n? **Audit Logs** ? Every action tracked with IP timestamps";
    }
    if (strpos($q, 'hello') !== false || strpos($q, 'hi') !== false || strpos($q, 'hey') !== false || $q === 'hi' || $q === 'hii') {
        return "Hello! I'm **Orby**, your AI guide for DASES!\n\nI can help with:\n? Scoring & grading rules\n? Admin workflows\n? Teacher evaluation tools\n? Student results & re-evaluation\n\nWhat would you like to know?";
    }
    if (strpos($q, 'best 5') !== false || strpos($q, 'section b') !== false || strpos($q, 'scoring') !== false || strpos($q, 'calculation') !== false) {
        return "**Section B Scoring in DASES:**\n\n? Section B has **8 questions** (10 marks each)\n? DASES auto-selects the **5 highest scores** = Max **50 marks**\n\nNo manual selection ? the system does it automatically!";
    }
    if (strpos($q, 'upload') !== false || strpos($q, 'bulk') !== false || strpos($q, 'pdf') !== false) {
        return "**Uploading Answer Scripts:**\n\n1. Go to **Admin Dashboard ? Upload Papers**\n2. Select **Bulk Upload** (multiple PDFs at once)\n3. Assign student PINs\n4. DASES indexes everything securely.";
    }
    if (strpos($q, 'pass') !== false || strpos($q, 'fail') !== false || strpos($q, 'marks') !== false || strpos($q, 'criteria') !== false || strpos($q, 'grade') !== false || strpos($q, '35') !== false || strpos($q, 'total') !== false) {
        return "**DASES Grading Formula:**\n\n| Component | Max Marks |\n|---|---|\n| Section A (10 x 3) | 30 |\n| Section B (Best 5 x 10) | 50 |\n| Internal Assessment | 20 |\n| **Grand Total** | **100** |\n\nPASS = 35+ marks, FAIL = below 35";
    }
    if (strpos($q, 'blind') !== false || strpos($q, 'anonymous') !== false || strpos($q, 'anon') !== false || strpos($q, 'mask') !== false) {
        return "**Blind Evaluation in DASES:**\n\nStudent PINs are replaced with random tokens (e.g. ANON-3A8B1C). Teachers grade without seeing any student identity ? ensuring fair, bias-free evaluation.";
    }
    if (strpos($q, 'reeval') !== false || strpos($q, 're-eval') !== false || strpos($q, 'appeal') !== false || strpos($q, 'grievance') !== false || strpos($q, 'recounting') !== false) {
        return "**Re-evaluation Process:**\n\n1. Student clicks **Apply for Re-evaluation** on result page\n2. Admin reviews in the **Re-evaluation Queue**\n3. Paper is reassigned to a secondary evaluator\n4. Updated marks are published to the student portal.";
    }
    if (strpos($q, 'teacher') !== false || strpos($q, 'annotate') !== false || strpos($q, 'stamp') !== false || strpos($q, 'studio') !== false || strpos($q, 'eval') !== false) {
        return "**Teacher Evaluation Studio:**\n\n? Open assigned scripts from **Teacher Dashboard ? Evaluation Queue**\n? Annotate with Pen, Highlighter, Stamps (check, cross, half)\n? Marks auto-save every **30 seconds**\n? Click **Submit Evaluation** to lock scores permanently.";
    }
    if (strpos($q, 'student') !== false || strpos($q, 'result') !== false || strpos($q, 'marksheet') !== false || strpos($q, 'check') !== false) {
        return "**Student Result Portal:**\n\n1. Enter your **Roll Number** or **Blind Token**\n2. View question-wise marks breakdown\n3. See your **letter grade** (O, A+, A, B, C, or F)\n4. Download your official **grade card**\n5. Apply for **Re-evaluation** if needed.";
    }
    if (strpos($q, 'admin') !== false || strpos($q, 'dashboard') !== false || strpos($q, 'csv') !== false || strpos($q, 'audit') !== false || strpos($q, 'export') !== false) {
        return "**Admin Dashboard Features:**\n\n? Bulk PDF Upload\n? Anonymous Faculty Assignment\n? Blind Evaluation Toggle\n? CSV Export of marksheets\n? Re-evaluation Queue management\n? Full Audit Log with IP tracking";
    }

    return "I'm **Orby**, your DASES AI! Try asking:\n\n? What is DASES?\n? How does Section B Best 5 scoring work?\n? What are the passing marks?\n? How does blind evaluation work?\n? How do students check their results?";
}

