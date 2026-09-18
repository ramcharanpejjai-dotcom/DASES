<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DASES - Digital Answer Script Evaluation System</title>
    <link rel="stylesheet" href="style.css?v=2.5">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --mesh-bg: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.12) 0px, transparent 50%),
                       radial-gradient(at 100% 0%, rgba(16, 185, 129, 0.1) 0px, transparent 50%),
                       radial-gradient(at 50% 100%, rgba(124, 58, 237, 0.08) 0px, transparent 50%),
                       #F8FAFC;
        }

        body {
            background: var(--mesh-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #0F172A;
        }

        /* Top Header Navbar */
        .landing-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 48px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .brand-text h1 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.5px;
            margin: 0;
            line-height: 1.1;
        }

        .brand-text span {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748B;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #065F46;
        }

        .pulsing-dot {
            width: 8px;
            height: 8px;
            background-color: #10B981;
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Hero Content */
        .landing-hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 50px 20px 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: #EEF2FF;
            border: 1px solid #C7D2FE;
            border-radius: 30px;
            color: #4F46E5;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: 0.4px;
        }

        .hero-title {
            font-size: 3.2rem;
            font-weight: 800;
            letter-spacing: -1.5px;
            line-height: 1.15;
            color: #0F172A;
            margin-bottom: 16px;
            max-width: 800px;
        }

        .hero-title span {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 50%, #2563EB 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description {
            font-size: 1.15rem;
            color: #64748B;
            max-width: 620px;
            line-height: 1.6;
            margin-bottom: 45px;
        }

        /* Cards Grid */
        .portals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            width: 100%;
            max-width: 840px;
            margin-bottom: 50px;
        }

        .portal-box {
            background: #FFFFFF;
            border-radius: 20px;
            border: 1px solid #E2E8F0;
            padding: 36px 30px;
            text-align: left;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .portal-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }

        .portal-box.admin::before {
            background: linear-gradient(90deg, #6366F1, #4F46E5);
        }

        .portal-box.teacher::before {
            background: linear-gradient(90deg, #10B981, #059669);
        }

        .portal-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.08), 0 10px 15px -5px rgba(0, 0, 0, 0.04);
            border-color: #CBD5E1;
        }

        .portal-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .portal-icon-wrap {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .admin .portal-icon-wrap {
            background: #EEF2FF;
            color: #4F46E5;
            border: 1px solid #C7D2FE;
        }

        .teacher .portal-icon-wrap {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }

        .portal-header-info h2 {
            font-size: 1.3rem;
            font-weight: 800;
            color: #0F172A;
            margin: 0 0 2px 0;
        }

        .portal-header-info span {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .admin .portal-header-info span { color: #6366F1; }
        .teacher .portal-header-info span { color: #10B981; }

        .portal-desc {
            font-size: 0.92rem;
            color: #64748B;
            line-height: 1.55;
            margin-bottom: 22px;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin: 0 0 28px 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .features-list li {
            font-size: 0.88rem;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .features-list li .check-icon {
            width: 18px;
            height: 18px;
            background: #DCFCE7;
            color: #16A34A;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .portal-btn {
            width: 100%;
            padding: 13px 20px;
            border-radius: 10px;
            font-size: 0.98rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.25s ease;
        }

        .btn-admin-portal {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
        }

        .btn-admin-portal:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
            color: white;
        }

        .btn-teacher-portal {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }

        .btn-teacher-portal:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            color: white;
        }

        /* Feature Pills Strip */
        .feature-strip {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 10px;
        }

        .feature-pill {
            background: white;
            border: 1px solid #E2E8F0;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 600;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        /* Footer */
        .landing-footer {
            padding: 24px;
            text-align: center;
            font-size: 0.85rem;
            color: #94A3B8;
            border-top: 1px solid rgba(226, 232, 240, 0.6);
            background: rgba(255, 255, 255, 0.5);
        }

        @media (max-width: 768px) {
            .landing-navbar { padding: 15px 20px; }
            .hero-title { font-size: 2.2rem; }
            .hero-description { font-size: 1rem; }
            .portals-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <header class="landing-navbar">
        <a href="index.php" class="brand-logo">
            <div class="brand-icon"><i data-lucide="graduation-cap" style="width:24px;height:24px;"></i></div>
            <div class="brand-text">
                <h1>DASES</h1>
                <span>Digital Answer Script Evaluation System</span>
            </div>
        </a>
        <div class="status-pill">
            <span class="pulsing-dot"></span>
            System Live & Secure
        </div>
    </header>

    <!-- Hero Content -->
    <main class="landing-hero">
        <div class="hero-tag"><i data-lucide="sparkles" class="icon-sm" style="color:#6366F1;"></i> Next-Generation Institutional Evaluation</div>
        
        <h1 class="hero-title">
            Digital Answer Script Evaluation with <span>Blind Grading</span>
        </h1>
        
        <p class="hero-description">
            Streamline script uploads, evaluate papers with live question-wise auto calculation, and maintain 100% evaluator anonymity with institutional blind grading.
        </p>

        <!-- Portals Grid -->
        <div class="portals-grid">
            <!-- Admin Portal -->
            <div class="portal-box admin">
                <div>
                    <div class="portal-header">
                        <div class="portal-icon-wrap"><i data-lucide="shield-check" style="width:28px;height:28px;color:#4F46E5;"></i></div>
                        <div class="portal-header-info">
                            <h2>Admin Portal</h2>
                            <span>Management Suite</span>
                        </div>
                    </div>
                    <p class="portal-desc">
                        Manage paper uploads with student PINs, assign scripts to evaluators, and download consolidated score reports.
                    </p>
                    <ul class="features-list">
                        <li><span class="check-icon"><i data-lucide="check" class="icon-xs"></i></span> Upload & archive paper scripts (PDF)</li>
                        <li><span class="check-icon"><i data-lucide="check" class="icon-xs"></i></span> Student PIN security & blind allocation</li>
                        <li><span class="check-icon"><i data-lucide="check" class="icon-xs"></i></span> Assign & reassign evaluators seamlessly</li>
                        <li><span class="check-icon"><i data-lucide="check" class="icon-xs"></i></span> One-click CSV results & marks export</li>
                    </ul>
                </div>
                <a href="login.php?role=admin" class="portal-btn btn-admin-portal">
                    Admin Sign In <i data-lucide="arrow-right" class="icon-sm"></i>
                </a>
            </div>

            <!-- Teacher Portal -->
            <div class="portal-box teacher">
                <div>
                    <div class="portal-header">
                        <div class="portal-icon-wrap"><i data-lucide="file-edit" style="width:28px;height:28px;color:#059669;"></i></div>
                        <div class="portal-header-info">
                            <h2>Evaluator Portal</h2>
                            <span>Grading Suite</span>
                        </div>
                    </div>
                    <p class="portal-desc">
                        Review assigned answer scripts in real-time split view, calculate section marks dynamically, and record feedback.
                    </p>
                    <ul class="features-list">
                        <li><span class="check-icon"><i data-lucide="check" class="icon-xs"></i></span> Side-by-side interactive PDF viewer</li>
                        <li><span class="check-icon"><i data-lucide="check" class="icon-xs"></i></span> 18-question marks auto-calculation</li>
                        <li><span class="check-icon"><i data-lucide="check" class="icon-xs"></i></span> Anonymized paper codes (unbiased grading)</li>
                        <li><span class="check-icon"><i data-lucide="check" class="icon-xs"></i></span> Instant question-wise marksheet breakdown</li>
                    </ul>
                </div>
                <a href="login.php?role=teacher" class="portal-btn btn-teacher-portal">
                    Evaluator Sign In <i data-lucide="arrow-right" class="icon-sm"></i>
                </a>
            </div>
        </div>

        <!-- Student Result & Academic Dashboard Portal Link -->
        <div style="margin-top: 24px; text-align: center;">
            <a href="student-dashboard.php" style="
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 14px 28px;
                background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
                border: 1.5px solid #C7D2FE;
                border-radius: 14px;
                font-size: 0.95rem;
                font-weight: 800;
                color: #4338CA;
                text-decoration: none;
                box-shadow: 0 4px 14px rgba(99, 102, 241, 0.15);
                transition: all 0.25s ease;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(99, 102, 241, 0.25)';"
               onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 14px rgba(99, 102, 241, 0.15)';">
                <i data-lucide="award" class="icon-sm" style="color:#4F46E5;"></i> Student Academic Dashboard & Semester Results (Sem-1 to 5) <i data-lucide="arrow-right" class="icon-xs"></i>
            </a>
        </div>

        <!-- Trust Badges Strip -->
        <div class="feature-strip">
            <div class="feature-pill"><i data-lucide="shield" class="icon-sm" style="color:#4F46E5;"></i> Blind Anonymous Grading</div>
            <div class="feature-pill"><i data-lucide="zap" class="icon-sm" style="color:#D97706;"></i> Dynamic Score Auto-Calc</div>
            <div class="feature-pill"><i data-lucide="table" class="icon-sm" style="color:#2563EB;"></i> Question-Level Marksheets</div>
            <div class="feature-pill"><i data-lucide="download" class="icon-sm" style="color:#059669;"></i> Instant CSV Export</div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="landing-footer">
        © <?php echo date('Y'); ?> DASES. Academic Answer Script Evaluation & Assessment Platform.
    </footer>
    <script src="animations.js?v=3.0.1788777855" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>