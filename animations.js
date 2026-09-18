/**
 * DASES — Next-Gen Interactive Mascot & Website Guidance AI System ("Orby")
 * Production Ready — 100% Crisp, High-Performance, Fully Interactive
 */

(function () {
    'use strict';

    /* ==============================================================
       0. DETECT USER ROLE & CONTEXT
       ============================================================== */
    function detectPageContext() {
        const path = window.location.pathname.toLowerCase();
        let role = 'guest';
        let pageTitle = 'DASES Portal';

        if (path.includes('admin') || document.querySelector('.sidebar .role-badge, .badge-admin')) {
            role = 'admin';
            pageTitle = 'Admin Dashboard';
        } else if (path.includes('teacher') || path.includes('eval') || document.querySelector('.badge-teacher')) {
            role = 'teacher';
            pageTitle = 'Teacher Portal';
        } else if (path.includes('student') || path.includes('result') || path.includes('marksheet') || path.includes('reeval')) {
            role = 'student';
            pageTitle = 'Student Portal';
        } else if (path.includes('login') || path.includes('register') || path.includes('forgot') || path.includes('reset')) {
            role = 'auth';
            pageTitle = 'Authentication';
        }

        return { role, path, pageTitle };
    }

    /* ==============================================================
       1. INJECT STYLES FOR ORBY, DRAWER & SPOTLIGHTS
       ============================================================== */
    function injectOrbyStyles() {
        if (document.getElementById('dases-orby-styles')) return;

        const style = document.createElement('style');
        style.id = 'dases-orby-styles';
        style.textContent = `
            /* MASCOT CONTAINER */
            .dases-mascot-wrapper {
                position: fixed !important;
                bottom: 24px !important;
                right: 24px !important;
                z-index: 999990 !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-end !important;
                user-select: none !important;
                font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
                pointer-events: none !important;
                transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
            }

            .dases-mascot-wrapper.minimized {
                transform: translateY(48px) scale(0.85) !important;
            }

            /* SPEECH BUBBLE */
            .mascot-speech-bubble {
                background: #FFFFFF !important;
                color: #0F172A !important;
                padding: 10px 16px !important;
                border-radius: 16px !important;
                font-size: 0.82rem !important;
                font-weight: 700 !important;
                box-shadow: 0 16px 32px -4px rgba(0, 0, 0, 0.18), 0 4px 8px -2px rgba(0, 0, 0, 0.08) !important;
                border: 1.5px solid #E2E8F0 !important;
                margin-bottom: 10px !important;
                max-width: 250px !important;
                opacity: 0 !important;
                transform: translateY(8px) scale(0.92) !important;
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
                pointer-events: auto !important;
                position: relative !important;
                line-height: 1.45 !important;
                display: none !important;
                align-items: center !important;
                gap: 8px !important;
            }

            body.dark-mode .mascot-speech-bubble,
            body.theme-midnight .mascot-speech-bubble {
                background: #1E293B !important;
                color: #F8FAFC !important;
                border-color: #334155 !important;
                box-shadow: 0 16px 36px rgba(0, 0, 0, 0.5) !important;
            }

            .mascot-speech-bubble.active {
                display: flex !important;
                opacity: 1 !important;
                transform: translateY(0) scale(1) !important;
            }

            .mascot-speech-bubble::after {
                content: '' !important;
                position: absolute !important;
                bottom: -7px !important;
                right: 32px !important;
                width: 12px !important;
                height: 12px !important;
                background: inherit !important;
                border-right: 1.5px solid #E2E8F0 !important;
                border-bottom: 1.5px solid #E2E8F0 !important;
                transform: rotate(45deg) !important;
            }

            body.dark-mode .mascot-speech-bubble::after,
            body.theme-midnight .mascot-speech-bubble::after {
                border-right-color: #334155 !important;
                border-bottom-color: #334155 !important;
            }

            .speech-bubble-icon {
                font-size: 1.1rem !important;
                flex-shrink: 0 !important;
            }

            /* MASCOT STACK */
            .mascot-stack {
                position: relative !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                pointer-events: auto !important;
            }

            /* ORBY PILL */
            .orby-action-pill {
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
                background: linear-gradient(135deg, #4F46E5, #6366F1) !important;
                color: #FFFFFF !important;
                font-size: 0.72rem !important;
                font-weight: 800 !important;
                padding: 4px 10px !important;
                border-radius: 20px !important;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4) !important;
                border: 1.5px solid rgba(255, 255, 255, 0.4) !important;
                cursor: pointer !important;
                margin-bottom: 6px !important;
                transition: transform 0.2s ease, box-shadow 0.2s ease !important;
                animation: orbyPillFloat 2.5s ease-in-out infinite alternate !important;
            }

            .orby-action-pill:hover {
                transform: scale(1.06) translateY(-2px) !important;
                box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6) !important;
            }

            @keyframes orbyPillFloat {
                0% { transform: translateY(0); }
                100% { transform: translateY(-3px); }
            }

            /* MASCOT 3D BALL */
            .mascot-ball {
                width: 74px !important;
                height: 74px !important;
                border-radius: 50% !important;
                background: radial-gradient(circle at 30% 28%, #818CF8 0%, #6366F1 45%, #4338CA 80%, #312E81 100%) !important;
                box-shadow: 
                    0 16px 36px -4px rgba(99, 102, 241, 0.55),
                    0 0 20px rgba(99, 102, 241, 0.35),
                    inset 0 -8px 16px rgba(30, 27, 75, 0.7),
                    inset 0 5px 10px rgba(255, 255, 255, 0.65) !important;
                position: relative !important;
                cursor: pointer !important;
                pointer-events: auto !important;
                animation: orbyFloat 3.6s ease-in-out infinite alternate !important;
                transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .mascot-ball:hover {
                transform: scale(1.12) translateY(-4px) !important;
                box-shadow: 
                    0 20px 42px -2px rgba(99, 102, 241, 0.7),
                    0 0 30px rgba(99, 102, 241, 0.5),
                    inset 0 -8px 16px rgba(30, 27, 75, 0.7),
                    inset 0 6px 12px rgba(255, 255, 255, 0.8) !important;
            }

            .mascot-ball.mascot-spin {
                animation: orbySpin 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) forwards !important;
            }

            .mascot-ball.mascot-thinking {
                animation: orbyThinkingPulse 1.2s ease-in-out infinite alternate !important;
            }

            @keyframes orbyThinkingPulse {
                0% { transform: translateY(0px) scale(1); box-shadow: 0 10px 24px -4px rgba(99, 102, 241, 0.5); }
                100% { transform: translateY(-8px) scale(1.08); box-shadow: 0 16px 36px -4px rgba(129, 140, 248, 0.8), 0 0 20px rgba(99, 102, 241, 0.6); }
            }

            @keyframes orbyFloat {
                0% { transform: translateY(0px) rotate(0deg); }
                100% { transform: translateY(-9px) rotate(2deg); }
            }

            @keyframes orbySpin {
                0% { transform: scale(1) rotate(0deg); }
                50% { transform: scale(1.3) translateY(-16px) rotate(180deg); }
                100% { transform: scale(1) rotate(360deg); }
            }

            /* GLOSS & HALO */
            .mascot-gloss {
                position: absolute !important;
                top: 7px !important;
                left: 14px !important;
                width: 25px !important;
                height: 14px !important;
                border-radius: 50% !important;
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0)) !important;
                transform: rotate(-32deg) !important;
                pointer-events: none !important;
            }

            .mascot-halo {
                position: absolute !important;
                inset: -7px !important;
                border-radius: 50% !important;
                background: radial-gradient(circle, rgba(99, 102, 241, 0.3), transparent 70%) !important;
                pointer-events: none !important;
                z-index: -1 !important;
            }

            /* FACE & EYES */
            .mascot-face {
                width: 54px !important;
                height: 44px !important;
                position: relative !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                transition: transform 0.08s ease-out !important;
            }

            .mascot-eye {
                width: 19px !important;
                height: 23px !important;
                background: #FFFFFF !important;
                border-radius: 50% !important;
                position: absolute !important;
                top: 5px !important;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3), 0 1px 3px rgba(0,0,0,0.15) !important;
                overflow: hidden !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .mascot-eye.left { left: 5px !important; }
            .mascot-eye.right { right: 5px !important; }

            .mascot-eyelid {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 0% !important;
                background: #4F46E5 !important;
                transition: height 0.09s ease !important;
                z-index: 4 !important;
            }

            .mascot-blinking .mascot-eyelid {
                height: 100% !important;
            }

            .mascot-pupil {
                width: 10.5px !important;
                height: 10.5px !important;
                background: radial-gradient(circle, #0F172A 60%, #312E81 100%) !important;
                border-radius: 50% !important;
                position: relative !important;
                transition: transform 0.04s ease-out !important;
                display: flex !important;
                align-items: flex-start !important;
                justify-content: flex-end !important;
            }

            .mascot-pupil-glint {
                width: 4px !important;
                height: 4px !important;
                background: #FFFFFF !important;
                border-radius: 50% !important;
                margin: 1.5px !important;
                box-shadow: 0 0 2px #FFFFFF !important;
            }

            .mascot-cheek {
                position: absolute !important;
                bottom: 7px !important;
                width: 10px !important;
                height: 5px !important;
                background: rgba(244, 114, 182, 0.85) !important;
                border-radius: 50% !important;
                filter: blur(0.5px) !important;
            }

            .mascot-cheek.left { left: 1px !important; }
            .mascot-cheek.right { right: 1px !important; }

            .mascot-mouth {
                position: absolute !important;
                bottom: 5px !important;
                width: 9px !important;
                height: 4px !important;
                border-bottom: 2.5px solid #312E81 !important;
                border-radius: 0 0 8px 8px !important;
                transition: all 0.2s ease !important;
            }

            .mascot-mouth.happy {
                width: 13px !important;
                height: 8px !important;
                background: #EF4444 !important;
                border: none !important;
                border-radius: 0 0 12px 12px !important;
                box-shadow: inset 0 2px 2px rgba(0,0,0,0.2) !important;
            }

            .mascot-shadow {
                position: absolute !important;
                bottom: -12px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                width: 50px !important;
                height: 8px !important;
                background: radial-gradient(ellipse, rgba(15, 23, 42, 0.25), transparent 70%) !important;
                border-radius: 50% !important;
                animation: orbyShadowPulse 3.6s ease-in-out infinite alternate !important;
            }

            @keyframes orbyShadowPulse {
                0% { transform: translateX(-50%) scale(1); opacity: 0.6; }
                100% { transform: translateX(-50%) scale(0.72); opacity: 0.22; }
            }

            /* CONTROLS */
            .orby-controls {
                position: absolute !important;
                top: -12px !important;
                left: -12px !important;
                display: flex !important;
                gap: 4px !important;
                opacity: 0 !important;
                transition: opacity 0.2s ease !important;
                pointer-events: auto !important;
            }

            .dases-mascot-wrapper:hover .orby-controls {
                opacity: 1 !important;
            }

            .orby-ctrl-btn {
                width: 22px !important;
                height: 22px !important;
                border-radius: 50% !important;
                background: #FFFFFF !important;
                border: 1px solid #CBD5E1 !important;
                color: #475569 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 0.65rem !important;
                cursor: pointer !important;
                box-shadow: 0 2px 6px rgba(0,0,0,0.12) !important;
                transition: transform 0.15s ease, background 0.15s ease !important;
            }

            .orby-ctrl-btn:hover {
                transform: scale(1.15) !important;
                background: #EEF2FF !important;
                color: #4F46E5 !important;
            }

            /* ==============================================================
               ORBY AI FLOATING ASSISTANT DRAWER (100% CLEAR, FULLY CLICKABLE)
               ============================================================== */
            .orby-drawer {
                position: fixed !important;
                top: 20px !important;
                bottom: 20px !important;
                right: 20px !important;
                width: 440px !important;
                max-width: calc(100vw - 40px) !important;
                background: #FFFFFF !important;
                border-radius: 24px !important;
                box-shadow: 0 25px 60px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(226, 232, 240, 0.95) !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
                z-index: 10000000 !important;
                transform: translateX(120%) !important;
                transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1) !important;
                font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
                pointer-events: auto !important;
            }

            body.dark-mode .orby-drawer,
            body.theme-midnight .orby-drawer {
                background: #111827 !important;
                border: 1px solid #1F2937 !important;
                box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7) !important;
                color: #F3F4F6 !important;
            }

            .orby-drawer.open {
                transform: translateX(0) !important;
            }

            .orby-header {
                padding: 18px 22px !important;
                background: linear-gradient(135deg, #4F46E5 0%, #6366F1 50%, #8B5CF6 100%) !important;
                color: #FFFFFF !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                position: relative !important;
                overflow: hidden !important;
                flex-shrink: 0 !important;
            }

            .orby-header-glow {
                position: absolute !important;
                top: -30px !important;
                right: -30px !important;
                width: 120px !important;
                height: 120px !important;
                background: radial-gradient(circle, rgba(255,255,255,0.25), transparent 70%) !important;
                border-radius: 50% !important;
            }

            .orby-header-left {
                display: flex !important;
                align-items: center !important;
                gap: 12px !important;
                z-index: 2 !important;
            }

            .orby-mini-avatar {
                width: 38px !important;
                height: 38px !important;
                border-radius: 50% !important;
                background: #FFFFFF !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 1.3rem !important;
                box-shadow: 0 4px 10px rgba(0,0,0,0.2) !important;
            }

            .orby-header-title {
                font-size: 1.05rem !important;
                font-weight: 800 !important;
                margin: 0 !important;
                line-height: 1.2 !important;
            }

            .orby-header-subtitle {
                font-size: 0.74rem !important;
                opacity: 0.9 !important;
                font-weight: 600 !important;
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
            }

            .orby-role-tag {
                background: rgba(255, 255, 255, 0.25) !important;
                padding: 1px 7px !important;
                border-radius: 10px !important;
                font-size: 0.68rem !important;
                font-weight: 800 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
            }

            .orby-close-btn {
                background: rgba(255, 255, 255, 0.2) !important;
                border: none !important;
                color: #FFFFFF !important;
                width: 34px !important;
                height: 34px !important;
                border-radius: 50% !important;
                cursor: pointer !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 1.1rem !important;
                font-weight: 800 !important;
                transition: background 0.2s ease, transform 0.2s ease !important;
                z-index: 10 !important;
                pointer-events: auto !important;
            }

            .orby-close-btn:hover {
                background: rgba(255, 255, 255, 0.35) !important;
                transform: scale(1.1) !important;
            }

            .orby-tabs {
                display: flex !important;
                padding: 8px 14px 0 14px !important;
                background: #F8FAFC !important;
                border-bottom: 1px solid #E2E8F0 !important;
                gap: 6px !important;
                flex-shrink: 0 !important;
            }

            body.dark-mode .orby-tabs,
            body.theme-midnight .orby-tabs {
                background: #161F30 !important;
                border-bottom-color: #242F48 !important;
            }

            .orby-tab {
                flex: 1 !important;
                padding: 10px 10px !important;
                background: transparent !important;
                border: none !important;
                border-bottom: 3px solid transparent !important;
                font-size: 0.82rem !important;
                font-weight: 800 !important;
                color: #64748B !important;
                cursor: pointer !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 6px !important;
                transition: all 0.2s ease !important;
                pointer-events: auto !important;
            }

            body.dark-mode .orby-tab,
            body.theme-midnight .orby-tab {
                color: #94A3B8 !important;
            }

            .orby-tab.active {
                color: #4F46E5 !important;
                border-bottom-color: #4F46E5 !important;
            }

            body.dark-mode .orby-tab.active,
            body.theme-midnight .orby-tab.active {
                color: #818CF8 !important;
                border-bottom-color: #818CF8 !important;
            }

            .orby-body {
                flex: 1 !important;
                overflow-y: auto !important;
                padding: 18px 20px !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 16px !important;
                pointer-events: auto !important;
            }

            /* ORBY GEMINI AI CHAT STYLES */
            .orby-chat-container {
                display: flex !important;
                flex-direction: column !important;
                height: 100% !important;
                min-height: 420px !important;
                gap: 12px !important;
            }

            .orby-chat-messages {
                flex: 1 !important;
                overflow-y: auto !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
                padding-right: 4px !important;
                max-height: calc(100vh - 310px) !important;
                scroll-behavior: smooth !important;
            }

            .orby-chat-messages::-webkit-scrollbar {
                width: 5px !important;
            }
            .orby-chat-messages::-webkit-scrollbar-thumb {
                background: #CBD5E1 !important;
                border-radius: 4px !important;
            }
            body.dark-mode .orby-chat-messages::-webkit-scrollbar-thumb,
            body.theme-midnight .orby-chat-messages::-webkit-scrollbar-thumb {
                background: #334155 !important;
            }

            .orby-msg-row {
                display: flex !important;
                gap: 10px !important;
                align-items: flex-start !important;
                animation: orbyMsgIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
            }

            @keyframes orbyMsgIn {
                from { opacity: 0; transform: translateY(8px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .orby-msg-row.user {
                justify-content: flex-end !important;
            }

            .orby-msg-avatar {
                width: 30px !important;
                height: 30px !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 0.95rem !important;
                flex-shrink: 0 !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.12) !important;
            }

            .orby-msg-avatar.bot {
                background: linear-gradient(135deg, #4F46E5, #818CF8) !important;
                color: #FFFFFF !important;
            }

            .orby-msg-avatar.user {
                background: linear-gradient(135deg, #059669, #10B981) !important;
                color: #FFFFFF !important;
            }

            .orby-bubble {
                max-width: 82% !important;
                padding: 12px 15px !important;
                border-radius: 18px !important;
                font-size: 0.84rem !important;
                line-height: 1.55 !important;
                word-break: break-word !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
            }

            .orby-bubble.bot {
                background: #F8FAFC !important;
                color: #0F172A !important;
                border: 1px solid #E2E8F0 !important;
                border-top-left-radius: 4px !important;
            }

            body.dark-mode .orby-bubble.bot,
            body.theme-midnight .orby-bubble.bot {
                background: #1E293B !important;
                color: #F8FAFC !important;
                border-color: #334155 !important;
            }

            .orby-bubble.user {
                background: linear-gradient(135deg, #4F46E5, #6366F1) !important;
                color: #FFFFFF !important;
                border-top-right-radius: 4px !important;
            }

            .orby-bubble p {
                margin: 0 0 8px 0 !important;
            }
            .orby-bubble p:last-child {
                margin-bottom: 0 !important;
            }
            .orby-bubble ul, .orby-bubble ol {
                margin: 4px 0 8px 18px !important;
                padding: 0 !important;
            }
            .orby-bubble li {
                margin-bottom: 4px !important;
            }
            .orby-bubble strong {
                font-weight: 800 !important;
            }
            .orby-bubble code {
                background: rgba(0, 0, 0, 0.08) !important;
                padding: 2px 5px !important;
                border-radius: 6px !important;
                font-family: monospace !important;
                font-size: 0.85em !important;
            }
            body.dark-mode .orby-bubble code,
            body.theme-midnight .orby-bubble code {
                background: rgba(255, 255, 255, 0.15) !important;
            }

            .orby-action-btn {
                display: inline-flex !important;
                align-items: center !important;
                gap: 5px !important;
                background: #EEF2FF !important;
                color: #4F46E5 !important;
                border: 1.5px solid #C7D2FE !important;
                padding: 4px 10px !important;
                border-radius: 12px !important;
                font-size: 0.75rem !important;
                font-weight: 700 !important;
                cursor: pointer !important;
                margin: 4px 4px 0 0 !important;
                transition: all 0.2s ease !important;
            }
            .orby-action-btn:hover {
                background: #4F46E5 !important;
                color: #FFFFFF !important;
                transform: translateY(-1px) !important;
            }

            /* TYPING INDICATOR */
            .orby-typing {
                display: flex !important;
                align-items: center !important;
                gap: 4px !important;
                padding: 10px 16px !important;
                background: #F1F5F9 !important;
                border-radius: 16px !important;
                width: fit-content !important;
                border-top-left-radius: 4px !important;
            }
            body.dark-mode .orby-typing,
            body.theme-midnight .orby-typing {
                background: #1E293B !important;
            }
            .orby-dot {
                width: 7px !important;
                height: 7px !important;
                background: #6366F1 !important;
                border-radius: 50% !important;
                animation: orbyDotBounce 1.4s infinite ease-in-out both !important;
            }
            .orby-dot:nth-child(1) { animation-delay: -0.32s !important; }
            .orby-dot:nth-child(2) { animation-delay: -0.16s !important; }
            @keyframes orbyDotBounce {
                0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
                40% { transform: scale(1.1); opacity: 1; }
            }

            /* CHAT INPUT FORM */
            .orby-chat-footer {
                display: flex !important;
                flex-direction: column !important;
                gap: 8px !important;
                padding-top: 8px !important;
                border-top: 1px solid #E2E8F0 !important;
            }
            body.dark-mode .orby-chat-footer,
            body.theme-midnight .orby-chat-footer {
                border-top-color: #242F48 !important;
            }

            .orby-input-wrapper {
                display: flex !important;
                align-items: center !important;
                background: #F8FAFC !important;
                border: 1.5px solid #E2E8F0 !important;
                border-radius: 16px !important;
                padding: 4px 6px 4px 14px !important;
                transition: all 0.2s ease !important;
            }
            body.dark-mode .orby-input-wrapper,
            body.theme-midnight .orby-input-wrapper {
                background: #161F30 !important;
                border-color: #2D3748 !important;
            }
            .orby-input-wrapper:focus-within {
                border-color: #6366F1 !important;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
                background: #FFFFFF !important;
            }
            body.dark-mode .orby-input-wrapper:focus-within,
            body.theme-midnight .orby-input-wrapper:focus-within {
                background: #1E293B !important;
            }

            .orby-chat-input {
                flex: 1 !important;
                border: none !important;
                background: transparent !important;
                outline: none !important;
                font-size: 0.86rem !important;
                color: #0F172A !important;
                font-family: inherit !important;
                padding: 8px 0 !important;
                resize: none !important;
            }
            body.dark-mode .orby-chat-input,
            body.theme-midnight .orby-chat-input {
                color: #F8FAFC !important;
            }

            .orby-send-btn {
                background: linear-gradient(135deg, #4F46E5, #6366F1) !important;
                color: #FFFFFF !important;
                border: none !important;
                width: 36px !important;
                height: 36px !important;
                border-radius: 12px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                cursor: pointer !important;
                transition: transform 0.18s ease, background 0.18s ease !important;
                font-size: 1.1rem !important;
                flex-shrink: 0 !important;
            }
            .orby-send-btn:hover:not(:disabled) {
                transform: scale(1.08) !important;
                background: #4338CA !important;
            }
            .orby-send-btn:disabled {
                opacity: 0.5 !important;
                cursor: not-allowed !important;
            }

            .orby-header-actions {
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
            }
            .orby-icon-btn {
                background: rgba(255, 255, 255, 0.2) !important;
                border: none !important;
                color: #FFFFFF !important;
                width: 32px !important;
                height: 32px !important;
                border-radius: 50% !important;
                cursor: pointer !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 0.95rem !important;
                transition: all 0.2s ease !important;
            }
            .orby-icon-btn:hover {
                background: rgba(255, 255, 255, 0.35) !important;
                transform: scale(1.1) !important;
            }
            .orby-icon-btn.active {
                background: #10B981 !important;
            }

            .orby-search-box {
                position: relative !important;
                display: flex !important;
                align-items: center !important;
            }

            .orby-search-input {
                width: 100% !important;
                padding: 11px 14px 11px 38px !important;
                border-radius: 14px !important;
                border: 1.5px solid #E2E8F0 !important;
                background: #F8FAFC !important;
                font-size: 0.86rem !important;
                font-weight: 600 !important;
                color: #0F172A !important;
                outline: none !important;
                transition: all 0.2s ease !important;
                font-family: inherit !important;
                pointer-events: auto !important;
            }

            body.dark-mode .orby-search-input,
            body.theme-midnight .orby-search-input {
                background: #1A233A !important;
                border-color: #2D3748 !important;
                color: #F1F5F9 !important;
            }

            .orby-search-input:focus {
                border-color: #6366F1 !important;
                background: #FFFFFF !important;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
            }

            .orby-search-icon {
                position: absolute !important;
                left: 12px !important;
                color: #94A3B8 !important;
                font-size: 0.95rem !important;
                pointer-events: none !important;
            }

            .orby-chips {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 6px !important;
            }

            .orby-chip {
                background: #EEF2FF !important;
                color: #4F46E5 !important;
                border: 1px solid #C7D2FE !important;
                padding: 6px 12px !important;
                border-radius: 20px !important;
                font-size: 0.74rem !important;
                font-weight: 700 !important;
                cursor: pointer !important;
                transition: all 0.18s ease !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 4px !important;
                pointer-events: auto !important;
            }

            body.dark-mode .orby-chip,
            body.theme-midnight .orby-chip {
                background: #1E293B !important;
                color: #A5B4FC !important;
                border-color: #3730A3 !important;
            }

            .orby-chip:hover {
                background: #4F46E5 !important;
                color: #FFFFFF !important;
                border-color: #4F46E5 !important;
                transform: translateY(-1px) !important;
            }

            .orby-card {
                background: #F8FAFC !important;
                border: 1.5px solid #E2E8F0 !important;
                border-radius: 16px !important;
                padding: 14px 16px !important;
                transition: all 0.2s ease !important;
                pointer-events: auto !important;
            }

            body.dark-mode .orby-card,
            body.theme-midnight .orby-card {
                background: #161F30 !important;
                border-color: #242F48 !important;
            }

            .orby-card:hover {
                border-color: #CBD5E1 !important;
                box-shadow: 0 4px 14px rgba(0,0,0,0.04) !important;
            }

            .orby-card-header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                font-weight: 800 !important;
                font-size: 0.88rem !important;
                color: #1E293B !important;
                margin-bottom: 8px !important;
            }

            body.dark-mode .orby-card-header,
            body.theme-midnight .orby-card-header {
                color: #F8FAFC !important;
            }

            .orby-card-desc {
                font-size: 0.8rem !important;
                color: #475569 !important;
                line-height: 1.5 !important;
                margin-bottom: 10px !important;
            }

            body.dark-mode .orby-card-desc,
            body.theme-midnight .orby-card-desc {
                color: #94A3B8 !important;
            }

            .orby-btn-sm {
                display: inline-flex !important;
                align-items: center !important;
                gap: 5px !important;
                padding: 7px 13px !important;
                border-radius: 10px !important;
                font-size: 0.76rem !important;
                font-weight: 800 !important;
                cursor: pointer !important;
                border: none !important;
                transition: all 0.2s ease !important;
                text-decoration: none !important;
                pointer-events: auto !important;
            }

            .orby-btn-primary {
                background: #4F46E5 !important;
                color: #FFFFFF !important;
            }

            .orby-btn-primary:hover {
                background: #4338CA !important;
                transform: translateY(-1px) !important;
            }

            .orby-btn-secondary {
                background: #E2E8F0 !important;
                color: #1E293B !important;
            }

            body.dark-mode .orby-btn-secondary,
            body.theme-midnight .orby-btn-secondary {
                background: #2D3748 !important;
                color: #F1F5F9 !important;
            }

            .orby-tour-banner {
                background: linear-gradient(135deg, #10B981, #059669) !important;
                color: #FFFFFF !important;
                border-radius: 16px !important;
                padding: 16px !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 8px !important;
                box-shadow: 0 10px 20px -4px rgba(16, 185, 129, 0.35) !important;
                pointer-events: auto !important;
            }

            .orby-tour-banner h4 {
                margin: 0 !important;
                font-size: 0.95rem !important;
                font-weight: 800 !important;
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
            }

            .orby-tour-banner p {
                margin: 0 !important;
                font-size: 0.78rem !important;
                opacity: 0.95 !important;
                line-height: 1.4 !important;
            }

            .orby-tour-banner button {
                align-self: flex-start !important;
                background: #FFFFFF !important;
                color: #065F46 !important;
                border: none !important;
                padding: 7px 14px !important;
                border-radius: 10px !important;
                font-size: 0.78rem !important;
                font-weight: 800 !important;
                cursor: pointer !important;
                transition: transform 0.18s ease !important;
                margin-top: 4px !important;
                pointer-events: auto !important;
            }

            /* SPOTLIGHT TOUR HIGHLIGHTS */
            .orby-spotlight-target-box {
                position: absolute !important;
                border-radius: 14px !important;
                box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.4), 0 10px 30px rgba(99, 102, 241, 0.25) !important;
                border: 3px solid #6366F1 !important;
                pointer-events: none !important;
                z-index: 9999992 !important;
                display: none !important;
                transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1) !important;
            }

            .orby-tour-popover {
                position: absolute !important;
                z-index: 9999995 !important;
                background: #FFFFFF !important;
                color: #0F172A !important;
                border-radius: 18px !important;
                padding: 18px 20px !important;
                width: 320px !important;
                box-shadow: 0 20px 40px -4px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(226, 232, 240, 0.9) !important;
                border: 1.5px solid #E2E8F0 !important;
                font-family: 'Plus Jakarta Sans', system-ui, sans-serif !important;
                display: none !important;
                pointer-events: auto !important;
            }

            body.dark-mode .orby-tour-popover,
            body.theme-midnight .orby-tour-popover {
                background: #1E293B !important;
                color: #F8FAFC !important;
                border-color: #334155 !important;
            }

            .orby-popover-header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                margin-bottom: 8px !important;
            }

            .orby-step-pill {
                background: #EEF2FF !important;
                color: #4F46E5 !important;
                padding: 2px 8px !important;
                border-radius: 12px !important;
                font-size: 0.68rem !important;
                font-weight: 800 !important;
            }

            .orby-popover-title {
                font-size: 0.95rem !important;
                font-weight: 800 !important;
                margin: 0 0 6px 0 !important;
            }

            .orby-popover-text {
                font-size: 0.8rem !important;
                color: #475569 !important;
                line-height: 1.45 !important;
                margin: 0 0 14px 0 !important;
            }

            body.dark-mode .orby-popover-text,
            body.theme-midnight .orby-popover-text {
                color: #94A3B8 !important;
            }

            .orby-popover-actions {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
            }

            .orby-popover-actions-right {
                display: flex !important;
                gap: 6px !important;
            }

            .mascot-sparkle {
                position: fixed !important;
                font-size: 1.4rem !important;
                pointer-events: none !important;
                z-index: 99999999 !important;
                animation: orbySparkleFly 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
            }

            @keyframes orbySparkleFly {
                0% { opacity: 1; transform: scale(0.5) translateY(0); }
                100% { opacity: 0; transform: scale(1.5) translateY(-40px); }
            }
        `;
        document.head.appendChild(style);
    }

    /* ==============================================================
       2. MASCOT INITIALIZATION & CURSOR TRACKING
       ============================================================== */
    let orbyMascotElement = null;
    let orbySpeechBubble = null;
    let orbySpeechText = null;
    let orbyFaceElement = null;
    let orbyPupilL = null;
    let orbyPupilR = null;
    let orbyMouth = null;

    function createOrbyMascot() {
        if (document.body.classList.contains('eval-page') || window.location.pathname.includes('teacher-eval.php')) return;
        injectOrbyStyles();
        if (document.getElementById('dases-mascot-container')) return;

        const ctx = detectPageContext();

        const container = document.createElement('div');
        container.id = 'dases-mascot-container';
        container.className = 'dases-mascot-wrapper';

        let roleGreeting = "Hi! I'm <strong>Orby</strong>, your DASES AI companion!";
        if (ctx.role === 'admin') {
            roleGreeting = "👋 Welcome Admin! Need help managing papers or faculty?";
        } else if (ctx.role === 'teacher') {
            roleGreeting = "📝 Welcome Teacher! Ready to evaluate student scripts?";
        } else if (ctx.role === 'student') {
            roleGreeting = "🎓 Welcome Student! Looking for your results or re-eval?";
        }

        container.innerHTML = `
            <div class="mascot-speech-bubble" id="mascotSpeech">
                <span class="speech-bubble-icon">💡</span>
                <span id="mascotSpeechText">${roleGreeting}</span>
            </div>

            <div class="mascot-stack">
                <div class="orby-action-pill" id="orbyGuidePill" title="Open Orby AI Guidance Center">
                    <span>✨ Ask Orby AI</span>
                </div>

                <div class="mascot-ball" id="mascotBall" title="Hi, I'm Orby! Click me for AI Help & Tours!">
                    <div class="orby-controls">
                        <button class="orby-ctrl-btn" id="orbyMinBtn" title="Minimize / Dock Orby">🗕</button>
                    </div>
                    <div class="mascot-gloss"></div>
                    <div class="mascot-halo"></div>
                    
                    <div class="mascot-face" id="mascotFace">
                        <div class="mascot-eye left">
                            <div class="mascot-eyelid"></div>
                            <div class="mascot-pupil" id="mascotPupilL">
                                <div class="mascot-pupil-glint"></div>
                            </div>
                        </div>

                        <div class="mascot-eye right">
                            <div class="mascot-eyelid"></div>
                            <div class="mascot-pupil" id="mascotPupilR">
                                <div class="mascot-pupil-glint"></div>
                            </div>
                        </div>

                        <div class="mascot-cheek left"></div>
                        <div class="mascot-cheek right"></div>
                        <div class="mascot-mouth" id="mascotMouth"></div>
                    </div>

                    <div class="mascot-shadow"></div>
                </div>
            </div>
        `;

        document.body.appendChild(container);

        orbyMascotElement = document.getElementById('mascotBall');
        orbySpeechBubble = document.getElementById('mascotSpeech');
        orbySpeechText = document.getElementById('mascotSpeechText');
        orbyFaceElement = document.getElementById('mascotFace');
        orbyPupilL = document.getElementById('mascotPupilL');
        orbyPupilR = document.getElementById('mascotPupilR');
        orbyMouth = document.getElementById('mascotMouth');

        initCursorTracking();
        initMascotBlinking();
        initMascotEvents();
        initOrbyDrawer();
        initSpotlightTourEngine();

        setTimeout(() => {
            showOrbySpeech(roleGreeting, 4500);
        }, 1000);
    }

    function initCursorTracking() {
        window.addEventListener('mousemove', function (e) {
            if (!orbyMascotElement) return;

            const rect = orbyMascotElement.getBoundingClientRect();
            const ballCenterX = rect.left + rect.width / 2;
            const ballCenterY = rect.top + rect.height / 2;

            const dx = e.clientX - ballCenterX;
            const dy = e.clientY - ballCenterY;
            const dist = Math.hypot(dx, dy);
            const angle = Math.atan2(dy, dx);

            const pupilMaxDistance = 7.2;
            const pupilDist = Math.min(dist / 22, pupilMaxDistance);
            const pupilX = Math.cos(angle) * pupilDist;
            const pupilY = Math.sin(angle) * pupilDist;

            if (orbyPupilL && orbyPupilR) {
                orbyPupilL.style.transform = `translate(${pupilX}px, ${pupilY}px)`;
                orbyPupilR.style.transform = `translate(${pupilX}px, ${pupilY}px)`;
            }

            const faceMaxTilt = 8;
            const faceX = Math.max(-faceMaxTilt, Math.min(faceMaxTilt, (dx / window.innerWidth) * faceMaxTilt * 2));
            const faceY = Math.max(-faceMaxTilt, Math.min(faceMaxTilt, (dy / window.innerHeight) * faceMaxTilt * 2));

            if (orbyFaceElement) {
                orbyFaceElement.style.transform = `translate(${faceX}px, ${faceY}px)`;
            }
        }, { passive: true });
    }

    function initMascotBlinking() {
        function blink() {
            if (!orbyMascotElement) return;
            orbyMascotElement.classList.add('mascot-blinking');
            setTimeout(() => {
                orbyMascotElement.classList.remove('mascot-blinking');
            }, 180);

            const nextBlink = Math.random() * 4500 + 2500;
            setTimeout(blink, nextBlink);
        }
        setTimeout(blink, 2000);
    }

    function initMascotEvents() {
        const guidePill = document.getElementById('orbyGuidePill');
        const minBtn = document.getElementById('orbyMinBtn');
        const wrapper = document.getElementById('dases-mascot-container');

        if (guidePill) {
            guidePill.addEventListener('click', (e) => {
                e.stopPropagation();
                openOrbyDrawer();
            });
        }

        if (minBtn && wrapper) {
            minBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                wrapper.classList.toggle('minimized');
                minBtn.innerText = wrapper.classList.contains('minimized') ? '🗖' : '🗕';
            });
        }

        if (orbyMascotElement) {
            orbyMascotElement.addEventListener('click', function (e) {
                e.stopPropagation();
                triggerOrbySpin(e.clientX, e.clientY);
                setTimeout(openOrbyDrawer, 250);
            });
        }
    }

    function triggerOrbySpin(x, y) {
        if (!orbyMascotElement) return;
        orbyMascotElement.classList.add('mascot-spin');
        if (orbyMouth) orbyMouth.classList.add('happy');

        createSparkles(x || window.innerWidth - 60, y || window.innerHeight - 60);

        setTimeout(() => {
            orbyMascotElement.classList.remove('mascot-spin');
            if (orbyMouth) orbyMouth.classList.remove('happy');
        }, 900);
    }

    function showOrbySpeech(text, duration = 4000) {
        if (!orbySpeechBubble || !orbySpeechText) return;
        orbySpeechText.innerHTML = text;
        orbySpeechBubble.classList.add('active');

        if (window._orbySpeechTimeout) clearTimeout(window._orbySpeechTimeout);
        window._orbySpeechTimeout = setTimeout(() => {
            orbySpeechBubble.classList.remove('active');
        }, duration);
    }

    window.orbySpeak = showOrbySpeech;

    function createSparkles(x, y) {
        const icons = ['✨', '⭐', '✦', '•'];
        for (let i = 0; i < 5; i++) {
            const sparkle = document.createElement('span');
            sparkle.innerText = icons[Math.floor(Math.random() * icons.length)];
            sparkle.className = 'mascot-sparkle';
            sparkle.style.color = '#818CF8';
            sparkle.style.left = `${x + (Math.random() - 0.5) * 50}px`;
            sparkle.style.top = `${y + (Math.random() - 0.5) * 50}px`;
            document.body.appendChild(sparkle);
            setTimeout(() => sparkle.remove(), 700);
        }
    }

    /* ==============================================================
       3. KNOWLEDGE BASE & DRAWER (STANDALONE & FULLY CLICKABLE)
       ============================================================== */
    const KNOWLEDGE_BASE = [
        {
            category: 'admin',
            role: 'Admin',
            q: 'How to upload student answer sheets in bulk?',
            a: 'Go to <strong>Admin Dashboard &gt; Bulk Upload</strong>. Upload a ZIP archive containing student PDF answer sheets named by PIN/Roll Number. DASES automatically indexes and creates database entries.'
        },
        {
            category: 'admin',
            role: 'Admin',
            q: 'How does blind evaluation work in DASES?',
            a: 'Student personal details (Name, Roll No, Hall Ticket) are masked behind an encrypted <strong>Blind PIN</strong>. Teachers only see the PIN, ensuring 100% unbiased evaluation.'
        },
        {
            category: 'admin',
            role: 'Admin',
            q: 'How to assign answer sheets to teachers?',
            a: 'Under <strong>Assign Papers</strong> in Admin Dashboard, choose the subject and select a faculty evaluator. Papers will instantly appear in their Teacher Portal.'
        },
        {
            category: 'admin',
            role: 'Admin',
            q: 'How to export marksheets and audit reports to CSV/Excel?',
            a: 'Click the <strong>Export CSV</strong> button on the Admin Dashboard or Audit Log page to download full evaluation data and timestamps.'
        },
        {
            category: 'teacher',
            role: 'Teacher',
            q: 'How to evaluate and mark assigned papers?',
            a: 'Open <strong>Teacher Dashboard &gt; Evaluation Queue</strong>. Click <em>Evaluate Paper</em> to open the digital paper viewer and question-by-question scoring panel.'
        },
        {
            category: 'teacher',
            role: 'Teacher',
            q: 'How are total marks calculated?',
            a: 'DASES automatically sums Section A and Section B question marks and validates them against max question scores before enabling the final <strong>Submit Evaluation</strong> button.'
        },
        {
            category: 'teacher',
            role: 'Teacher',
            q: 'What is the passing criteria?',
            a: 'Students require a minimum of <strong>35 marks out of 100</strong> to receive a PASS grade.'
        },
        {
            category: 'student',
            role: 'Student',
            q: 'How do students check their exam results?',
            a: 'Visit the <strong>Student Results</strong> portal and enter your registered <strong>Hall Ticket Number</strong> or <strong>Blind PIN</strong> to view the detailed marksheet.'
        },
        {
            category: 'student',
            role: 'Student',
            q: 'How to apply for paper re-evaluation or recounting?',
            a: 'On your marksheet page, click <strong>Apply for Re-evaluation</strong>. Select the subject, submit your request note, and track the status in real time.'
        },
        {
            category: 'general',
            role: 'All',
            q: 'How do I switch themes or dark mode?',
            a: 'Use the floating <strong>THEME palette bar</strong> at the bottom-left of the screen or toggle the sun/moon button in the top navigation.'
        }
    ];

    /* ==============================================================
       3. ORBY GEMINI AI ENGINE & INTERACTIVE DRAWER
       ============================================================== */
    // Gemini requests are securely handled backend-side in orby_ai_api.php
    let orbyChatHistory = [];
    let orbyVoiceEnabled = false;
    let isOrbyThinking = false;

    // Load persisted chat history if any
    try {
        const savedHistory = sessionStorage.getItem('dases_orby_chat_history');
        if (savedHistory) {
            orbyChatHistory = JSON.parse(savedHistory);
        }
    } catch (e) {}

    function saveOrbyHistory() {
        try {
            sessionStorage.setItem('dases_orby_chat_history', JSON.stringify(orbyChatHistory.slice(-16)));
        } catch (e) {}
    }

    function initOrbyDrawer() {
        if (document.getElementById('orbyDrawer')) return;

        const ctx = detectPageContext();

        const drawer = document.createElement('div');
        drawer.id = 'orbyDrawer';
        drawer.className = 'orby-drawer';

        drawer.innerHTML = `
            <div class="orby-header">
                <div class="orby-header-glow"></div>
                <div class="orby-header-left">
                    <div class="orby-mini-avatar" id="orbyHeaderAvatar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M3 21a9 9 0 0 1 18 0"/><circle cx="12" cy="8" r="2" fill="currentColor" opacity="0.3"/></svg>
                    </div>
                    <div>
                        <h3 class="orby-header-title">Orby AI Companion</h3>
                        <div class="orby-header-subtitle">
                            <span>Gemini 3.7 Intelligence</span>
                            <span class="orby-role-tag">${ctx.role} Mode</span>
                        </div>
                    </div>
                </div>
                <div class="orby-header-actions">
                    <button class="orby-icon-btn" id="orbyVoiceToggle" title="Toggle AI Voice / Speech (TTS)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide-vol-off"><line x1="2" y1="2" x2="22" y2="22"/><path d="M11 4.702a.705.705 0 0 0-1.203-.498L6.197 7H4a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h2.197l3.6 2.796A.705.705 0 0 0 11 19.298z"/><path d="M16.5 7.5A5 5 0 0 1 19 12a5 5 0 0 1-.5 2.1"/></svg>
                    </button>
                    <button class="orby-icon-btn" id="orbyClearHistory" title="Clear Conversation">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6 18.1 19a2 2 0 0 1-2 1.9H7.9a2 2 0 0 1-2-1.9L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </button>
                    <button class="orby-close-btn" id="orbyDrawerClose" title="Close Panel">✕</button>
                </div>
            </div>

            <div class="orby-tabs" id="orbyNavTabs">
                <button type="button" class="orby-tab active" data-tab="chat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:4px;"><path d="M12 8V4H8"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M12 12h.01"/><path d="M8 12h.01"/><path d="M16 12h.01"/></svg> Ask AI
                </button>
                <button type="button" class="orby-tab" data-tab="navigate">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:4px;"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg> Navigate
                </button>
                <button type="button" class="orby-tab" data-tab="guide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:4px;"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg> Guides
                </button>
                <button type="button" class="orby-tab" data-tab="tours">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:middle;margin-right:4px;"><path d="m13 2-2 2.5h3L12 7"/><path d="M10 14v-3"/><path d="M14 14v-3"/><path d="M11 19H6.5a3.5 3.5 0 0 1 0-7h11a3.5 3.5 0 0 1 0 7H13"/><path d="M12 22v-3"/></svg> Tours
                </button>
            </div>

            <div class="orby-body" id="orbyDrawerBody"></div>
        `;

        document.body.appendChild(drawer);

        // Header Action Listeners
        document.getElementById('orbyDrawerClose').addEventListener('click', function(e) {
            e.stopPropagation();
            closeOrbyDrawer();
        });

        const voiceBtn = document.getElementById('orbyVoiceToggle');
        if (voiceBtn) {
            voiceBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                orbyVoiceEnabled = !orbyVoiceEnabled;
                voiceBtn.innerHTML = orbyVoiceEnabled
                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="2" x2="22" y2="22"/><path d="M11 4.702a.705.705 0 0 0-1.203-.498L6.197 7H4a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h2.197l3.6 2.796A.705.705 0 0 0 11 19.298z"/><path d="M16.5 7.5A5 5 0 0 1 19 12a5 5 0 0 1-.5 2.1"/></svg>';
                voiceBtn.classList.toggle('active', orbyVoiceEnabled);
                showOrbySpeech(orbyVoiceEnabled ? "Voice mode enabled! I'll read my answers." : "Voice mode muted.", 2500);
            });
        }

        const clearBtn = document.getElementById('orbyClearHistory');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                orbyChatHistory = [];
                sessionStorage.removeItem('dases_orby_chat_history');
                renderDrawerTab('chat');
                showOrbySpeech("Chat history cleared! What can I help with next?", 2500);
            });
        }

        // Tab Switching listeners
        document.querySelectorAll('#orbyNavTabs .orby-tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.stopPropagation();
                document.querySelectorAll('#orbyNavTabs .orby-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                renderDrawerTab(tab.dataset.tab);
            });
        });

        // Close on escape key
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeOrbyDrawer();
        });

        renderDrawerTab('chat');
    }

    function openOrbyDrawer() {
        const drawer = document.getElementById('orbyDrawer');
        if (drawer) {
            drawer.classList.add('open');
            const input = document.getElementById('orbyChatInput');
            if (input) setTimeout(() => input.focus(), 300);
        }
    }

    function closeOrbyDrawer() {
        const drawer = document.getElementById('orbyDrawer');
        if (drawer) {
            drawer.classList.remove('open');
        }
    }

    function renderDrawerTab(tabName) {
        const body = document.getElementById('orbyDrawerBody');
        if (!body) return;

        const ctx = detectPageContext();

        if (tabName === 'chat') {
            body.innerHTML = `
                <div class="orby-chat-container">
                    <div class="orby-chat-messages" id="orbyChatMessages">
                        <!-- Welcome message if empty -->
                    </div>

                    <div class="orby-chips" id="orbyQuickChips">
                        ${getRolePromptChips(ctx.role)}
                    </div>

                    <div class="orby-chat-footer">
                        <form id="orbyChatForm" class="orby-input-wrapper" onsubmit="return false;">
                            <input type="text" id="orbyChatInput" class="orby-chat-input" placeholder="Ask Orby AI anything about DASES..." autocomplete="off">
                            <button type="submit" id="orbySendBtn" class="orby-send-btn" title="Send message">➔</button>
                        </form>
                    </div>
                </div>
            `;

            // Populate existing history
            const msgList = document.getElementById('orbyChatMessages');
            if (orbyChatHistory.length === 0) {
                appendOrbyBotMessage("👋 Hi! I'm **Orby**, your DASES AI Companion powered by Gemini.\n\nAsk me anything about grading formulas (e.g. *Best 5 of 8*), anonymous blind evaluations, bulk PDF uploads, marksheet verification, or portal navigation!", false);
            } else {
                orbyChatHistory.forEach(item => {
                    if (item.role === 'user') {
                        appendOrbyUserMessage(item.text, false);
                    } else {
                        appendOrbyBotMessage(item.text, false);
                    }
                });
            }

            // Bind Form Submit
            const form = document.getElementById('orbyChatForm');
            const input = document.getElementById('orbyChatInput');
            const sendBtn = document.getElementById('orbySendBtn');

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const q = input.value.trim();
                if (q && !isOrbyThinking) {
                    input.value = '';
                    handleOrbyUserQuery(q);
                }
            });

            // Bind Chips
            document.querySelectorAll('#orbyQuickChips .orby-chip').forEach(chip => {
                chip.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const query = chip.dataset.query || chip.innerText.trim();
                    if (!isOrbyThinking) {
                        handleOrbyUserQuery(query);
                    }
                });
            });

            // Scroll to bottom
            if (msgList) msgList.scrollTop = msgList.scrollHeight;

        } else if (tabName === 'navigate') {
            body.innerHTML = `
                <div style="font-size:0.86rem;font-weight:800;color:inherit;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                    <span>🧭</span> <span>DASES Institutional Portal Navigator</span>
                </div>
                <p style="font-size:0.82rem;opacity:0.85;margin:0 0 14px;line-height:1.4;">
                    Instant direct access to all portals, queues, and workflows:
                </p>

                <!-- Admin Management Suite -->
                <div class="orby-card">
                    <div class="orby-card-header">
                        <span>👑 Admin Management Suite</span>
                    </div>
                    <div class="orby-card-desc">
                        Institutional paper uploads, blind token generation, faculty assignment, and audit inspection.
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
                        <a href="admin-dashboard.php" class="orby-btn-sm orby-btn-primary">👑 Admin Dashboard</a>
                        <a href="admin-dashboard.php?tab=upload" class="orby-btn-sm orby-btn-secondary">📁 Upload Papers</a>
                        <a href="admin-dashboard.php?tab=assign" class="orby-btn-sm orby-btn-secondary">👥 Assign Papers</a>
                        <a href="admin-dashboard.php?tab=status" class="orby-btn-sm orby-btn-secondary">📊 Paper Status</a>
                        <a href="admin-dashboard.php?tab=reevals" class="orby-btn-sm orby-btn-secondary">🔄 Re-evals</a>
                    </div>
                </div>

                <!-- Teacher Evaluation Studio -->
                <div class="orby-card">
                    <div class="orby-card-header">
                        <span>🎓 Teacher / Evaluator Portal</span>
                    </div>
                    <div class="orby-card-desc">
                        Access assigned student answer sheets, annotate with PDF.js tools, and submit validated scores.
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
                        <a href="teacher-dashboard.php" class="orby-btn-sm orby-btn-primary">🎓 Teacher Portal</a>
                        <a href="teacher-dashboard.php?tab=pending" class="orby-btn-sm orby-btn-secondary">⏳ Pending Queue</a>
                        <a href="teacher-dashboard.php?tab=completed" class="orby-btn-sm orby-btn-secondary">✅ Completed</a>
                    </div>
                </div>

                <!-- Student Result Portal -->
                <div class="orby-card">
                    <div class="orby-card-header">
                        <span>📄 Student Result Portal</span>
                    </div>
                    <div class="orby-card-desc">
                        Search and verify marks by PIN/Roll Number, download official digital grade cards, and appeal re-evaluation.
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
                        <a href="student-result.php" class="orby-btn-sm orby-btn-primary">📄 Check Results</a>
                    </div>
                </div>

                <!-- Audit Log & Home -->
                <div class="orby-card">
                    <div class="orby-card-header">
                        <span>🛡️ System Logs & Account</span>
                    </div>
                    <div class="orby-card-desc">
                        Immutable cryptographic logs and institutional main landing.
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
                        <a href="audit-log.php" class="orby-btn-sm orby-btn-secondary">🛡️ Audit Log</a>
                        <a href="index.php" class="orby-btn-sm orby-btn-secondary">🏠 Portal Home</a>
                        <a href="profile.php" class="orby-btn-sm orby-btn-secondary">👤 Profile</a>
                    </div>
                </div>
            `;

        } else if (tabName === 'guide') {
            body.innerHTML = `
                <div class="orby-tour-banner">
                    <h4>🚀 Need a Quick Walkthrough?</h4>
                    <p>Take an interactive step-by-step tour of this page's features.</p>
                    <button type="button" id="btnStartPageTour">Start Page Tour ➔</button>
                </div>

                <div class="orby-card">
                    <div class="orby-card-header">
                        <span>👑 Admin Workflows</span>
                    </div>
                    <div class="orby-card-desc">
                        Bulk upload student papers, assign answer scripts to faculty evaluators, monitor audit logs, and approve re-evaluations.
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <a href="admin-dashboard.php" class="orby-btn-sm orby-btn-primary">Go to Admin Dashboard</a>
                        <button type="button" class="orby-btn-sm orby-btn-secondary" id="btnAdminTourLink">Admin Tour</button>
                    </div>
                </div>

                <div class="orby-card">
                    <div class="orby-card-header">
                        <span>🎓 Teacher Workflows</span>
                    </div>
                    <div class="orby-card-desc">
                        Access assigned double-blind answer scripts, award validated question scores, annotate sheets, and finalize evaluation.
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <a href="teacher-dashboard.php" class="orby-btn-sm orby-btn-primary">Go to Teacher Portal</a>
                        <button type="button" class="orby-btn-sm orby-btn-secondary" id="btnTeacherTourLink">Teacher Tour</button>
                    </div>
                </div>

                <div class="orby-card">
                    <div class="orby-card-header">
                        <span>📚 Student Workflows</span>
                    </div>
                    <div class="orby-card-desc">
                        Search and download official digitally verified marksheets using Hall Ticket number and apply for re-evaluation.
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <a href="student-result.php" class="orby-btn-sm orby-btn-primary">Check Results</a>
                        <button type="button" class="orby-btn-sm orby-btn-secondary" id="btnStudentTourLink">Student Tour</button>
                    </div>
                </div>
            `;

            const btnTour = document.getElementById('btnStartPageTour');
            if (btnTour) {
                btnTour.addEventListener('click', (e) => {
                    e.stopPropagation();
                    closeOrbyDrawer();
                    startTourForCurrentPage();
                });
            }

            const btnAdmin = document.getElementById('btnAdminTourLink');
            if (btnAdmin) {
                btnAdmin.addEventListener('click', (e) => {
                    e.stopPropagation();
                    window.startDasesTour('admin');
                });
            }

            const btnTeacher = document.getElementById('btnTeacherTourLink');
            if (btnTeacher) {
                btnTeacher.addEventListener('click', (e) => {
                    e.stopPropagation();
                    window.startDasesTour('teacher');
                });
            }

            const btnStudent = document.getElementById('btnStudentTourLink');
            if (btnStudent) {
                btnStudent.addEventListener('click', (e) => {
                    e.stopPropagation();
                    window.startDasesTour('student');
                });
            }

        } else if (tabName === 'tours') {
            body.innerHTML = `
                <div style="font-size:0.84rem;font-weight:700;color:var(--text-dark, #0F172A);margin-bottom:4px;">
                    🎯 Choose an Interactive Guided Tour:
                </div>

                <div class="orby-card">
                    <div class="orby-card-header">
                        <span>👑 Admin Command Center Tour</span>
                    </div>
                    <div class="orby-card-desc">Walkthrough of statistics, bulk paper upload, evaluator assignment, and live audit tracking.</div>
                    <button type="button" class="orby-btn-sm orby-btn-primary" id="btnLaunchAdminTour">Launch Admin Tour ➔</button>
                </div>

                <div class="orby-card">
                    <div class="orby-card-header">
                        <span>🎓 Teacher Evaluation Tour</span>
                    </div>
                    <div class="orby-card-desc">Learn how to review assigned papers, enter question marks, calculate totals, and submit scores.</div>
                    <button type="button" class="orby-btn-sm orby-btn-primary" id="btnLaunchTeacherTour">Launch Teacher Tour ➔</button>
                </div>

                <div class="orby-card">
                    <div class="orby-card-header">
                        <span>📚 Student Results &amp; Re-evaluation Tour</span>
                    </div>
                    <div class="orby-card-desc">Learn how to search marksheets, verify pass/fail criteria, and submit re-evaluation requests.</div>
                    <button type="button" class="orby-btn-sm orby-btn-primary" id="btnLaunchStudentTour">Launch Student Tour ➔</button>
                </div>
            `;

            document.getElementById('btnLaunchAdminTour').addEventListener('click', (e) => {
                e.stopPropagation();
                window.startDasesTour('admin');
            });
            document.getElementById('btnLaunchTeacherTour').addEventListener('click', (e) => {
                e.stopPropagation();
                window.startDasesTour('teacher');
            });
            document.getElementById('btnLaunchStudentTour').addEventListener('click', (e) => {
                e.stopPropagation();
                window.startDasesTour('student');
            });
        }
    }

    function getRolePromptChips(role) {
        if (role === 'admin') {
            return `
                <span class="orby-chip" data-query="How does bulk upload of answer sheets work in DASES?">📁 Bulk Upload</span>
                <span class="orby-chip" data-query="How do I assign papers to evaluators anonymously?">🔒 Blind Evaluation</span>
                <span class="orby-chip" data-query="How do I export results to Excel/CSV?">📊 Export CSV</span>
                <span class="orby-chip" data-query="How does the re-evaluation grievance approval workflow work?">🔄 Re-evaluation</span>
            `;
        } else if (role === 'teacher') {
            return `
                <span class="orby-chip" data-query="How is the Section B Best 5 of 8 calculated?">🎯 Best 5 of 8 Rules</span>
                <span class="orby-chip" data-query="How do I use the PDF annotation tools (stamps, pens, comments)?">✍️ Annotation Tools</span>
                <span class="orby-chip" data-query="What is the passing threshold for students?">🎓 Pass Criteria</span>
                <span class="orby-chip" data-query="How does auto-saving drafts work in evaluation?">💾 Auto-Save</span>
            `;
        } else if (role === 'student') {
            return `
                <span class="orby-chip" data-query="How do I check my result and download my grade card?">📄 Check Marks</span>
                <span class="orby-chip" data-query="How do I apply for paper re-evaluation or recounting?">🔄 Apply Re-evaluation</span>
                <span class="orby-chip" data-query="How are final grades (O, A+, B, etc.) awarded?">🏆 Grading System</span>
            `;
        }
        return `
            <span class="orby-chip" data-query="How is the Section B Best 5 of 8 calculated?">🎯 Best 5 of 8</span>
            <span class="orby-chip" data-query="What are the key features of DASES?">✨ System Overview</span>
            <span class="orby-chip" data-query="How does anonymous blind evaluation prevent bias?">🔒 Blind Evaluation</span>
            <span class="orby-chip" data-query="How do teachers evaluate PDF scripts?">📝 Teacher Studio</span>
        `;
    }

    /* ==============================================================
       CHAT MESSAGE APPENDING & RENDERING
       ============================================================== */
    function appendOrbyUserMessage(text, save = true) {
        const msgList = document.getElementById('orbyChatMessages');
        if (!msgList) return;

        const row = document.createElement('div');
        row.className = 'orby-msg-row user';
        row.innerHTML = `
            <div class="orby-bubble user">
                ${escapeHtml(text)}
            </div>
            <div class="orby-msg-avatar user">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M3 21a9 9 0 0 1 18 0"/></svg>
            </div>
        `;
        msgList.appendChild(row);
        msgList.scrollTop = msgList.scrollHeight;

        if (save) {
            orbyChatHistory.push({ role: 'user', text });
            saveOrbyHistory();
        }
    }

    function appendOrbyBotMessage(text, save = true) {
        const msgList = document.getElementById('orbyChatMessages');
        if (!msgList) return;

        const formattedHtml = parseOrbyMarkdown(text);

        const row = document.createElement('div');
        row.className = 'orby-msg-row';
        row.innerHTML = `
            <div class="orby-msg-avatar bot">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/></svg>
            </div>
            <div class="orby-bubble bot">
                ${formattedHtml}
            </div>
        `;
        msgList.appendChild(row);
        msgList.scrollTop = msgList.scrollHeight;

        // Attach action handlers for any embedded action buttons
        row.querySelectorAll('.orby-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const act = btn.dataset.action;
                const param = btn.dataset.param;
                executeOrbyAction(act, param);
            });
        });

        if (save) {
            orbyChatHistory.push({ role: 'model', text });
            saveOrbyHistory();
        }

        if (orbyVoiceEnabled && typeof window.speechSynthesis !== 'undefined') {
            speakText(text);
        }
    }

    function showOrbyTypingIndicator() {
        const msgList = document.getElementById('orbyChatMessages');
        if (!msgList || document.getElementById('orbyTypingRow')) return;

        const row = document.createElement('div');
        row.id = 'orbyTypingRow';
        row.className = 'orby-msg-row';
        row.innerHTML = `
            <div class="orby-msg-avatar bot">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/></svg>
            </div>
            <div class="orby-typing">
                <div class="orby-dot"></div>
                <div class="orby-dot"></div>
                <div class="orby-dot"></div>
            </div>
        `;
        msgList.appendChild(row);
        msgList.scrollTop = msgList.scrollHeight;
    }

    function removeOrbyTypingIndicator() {
        const row = document.getElementById('orbyTypingRow');
        if (row) row.remove();
    }

    /* ==============================================================
       INTELLIGENT NAVIGATION ENGINE & GEMINI PIPELINE
       ============================================================== */
    let autoNavTimer = null;
    let autoNavInterval = null;

    window.cancelOrbyAutoNav = function (btn) {
        if (autoNavTimer) {
            clearTimeout(autoNavTimer);
            autoNavTimer = null;
        }
        if (autoNavInterval) {
            clearInterval(autoNavInterval);
            autoNavInterval = null;
        }
        if (btn) {
            const card = btn.closest('.orby-nav-card') || btn.parentElement;
            if (card) {
                const timerEl = card.querySelector('.orby-countdown-timer');
                if (timerEl) {
                    timerEl.innerHTML = '<span style="color:#EF4444;font-weight:700;">Redirect cancelled</span>';
                }
            }
        }
        showOrbySpeech("Navigation cancelled. Where else can I take you?", 2500);
    };

    function detectNavigationIntent(rawQuery) {
        if (!rawQuery) return null;
        const q = rawQuery.toLowerCase().trim();
        const navKeywords = ['navigate', 'go to', 'open', 'take me to', 'switch to', 'show me', 'visit', 'redirect', 'portal', 'dashboard', 'where is', 'link to', 'access'];
        const isNavQuery = navKeywords.some(kw => q.includes(kw));

        // 1. Admin Destination
        if (q.includes('admin') || q.includes('upload') || q.includes('assign paper') || q.includes('manage paper')) {
            if (q.includes('upload')) {
                return {
                    title: 'Admin Paper Upload',
                    url: 'admin-dashboard.php?tab=upload',
                    icon: '📁',
                    desc: 'Upload single answer sheets or bulk ZIP files with automatic PIN mapping.'
                };
            }
            if (q.includes('assign')) {
                return {
                    title: 'Assign Papers to Evaluators',
                    url: 'admin-dashboard.php?tab=assign',
                    icon: '👥',
                    desc: 'Assign anonymized answer sheets to teachers and set blind evaluation tokens.'
                };
            }
            if (q.includes('status')) {
                return {
                    title: 'Paper Evaluation Status',
                    url: 'admin-dashboard.php?tab=status',
                    icon: '📊',
                    desc: 'Track pending vs evaluated paper status across all departments.'
                };
            }
            if (q.includes('reeval') || q.includes('grievance')) {
                return {
                    title: 'Admin Re-Evaluation Queue',
                    url: 'admin-dashboard.php?tab=reevals',
                    icon: '🔄',
                    desc: 'Process student re-evaluation requests and grievance assignments.'
                };
            }
            return {
                title: 'Admin Dashboard & Management Suite',
                url: 'admin-dashboard.php',
                icon: '👑',
                desc: 'Institutional administration dashboard for paper uploads, teacher assignments, and grading management.'
            };
        }

        // 2. Teacher / Evaluator Destination
        if (q.includes('teacher') || q.includes('evaluator') || q.includes('evaluat') || q.includes('marking') || q.includes('grading')) {
            if (isNavQuery || q.includes('teacher portal') || q.includes('evaluator portal') || q.includes('teacher dashboard')) {
                if (q.includes('completed')) {
                    return {
                        title: 'Completed Evaluations',
                        url: 'teacher-dashboard.php?tab=completed',
                        icon: '✅',
                        desc: 'Review all answer sheets you have finalized and submitted.'
                    };
                }
                return {
                    title: 'Teacher Evaluation Portal',
                    url: 'teacher-dashboard.php',
                    icon: '🎓',
                    desc: 'View assigned student scripts and access the digital PDF evaluation studio.'
                };
            }
        }

        // 3. Student Result Destination
        if (q.includes('student') || q.includes('result') || q.includes('marksheet') || q.includes('grade card') || q.includes('grades') || q.includes('score')) {
            if (isNavQuery || q.includes('check') || q.includes('portal') || q.includes('student') || q.includes('marksheet')) {
                return {
                    title: 'Student Result Portal',
                    url: 'student-result.php',
                    icon: '📄',
                    desc: 'Lookup exam scores by Roll Number/PIN, download grade cards, and apply for re-evaluation.'
                };
            }
        }

        // 4. Audit Log Destination
        if (q.includes('audit') || q.includes('logs') || q.includes('activity trail')) {
            return {
                title: 'Institutional Audit Log',
                url: 'audit-log.php',
                icon: '🛡️',
                desc: 'View cryptographic log history of evaluations, reassignments, and system updates.'
            };
        }

        // 5. Home / Landing Destination
        if (q.includes('home') || q.includes('landing') || q.includes('main page') || q.includes('welcome') || q === 'home') {
            return {
                title: 'DASES Main Landing Page',
                url: 'index.php',
                icon: '🏠',
                desc: 'Return to the main DASES institutional entry portal.'
            };
        }

        // 6. Login / Sign In Destination
        if (q.includes('login') || q.includes('sign in') || q.includes('sign-in')) {
            const role = q.includes('admin') ? 'admin' : (q.includes('teacher') ? 'teacher' : '');
            return {
                title: (role ? role.toUpperCase() : 'Portal') + ' Sign In',
                url: role ? `login.php?role=${role}` : 'login.php',
                icon: '🔑',
                desc: 'Authenticate to access your designated role portal.'
            };
        }

        // 7. Profile Destination
        if (q.includes('profile') || q.includes('account') || q.includes('my profile')) {
            return {
                title: 'User Profile & Settings',
                url: 'profile.php',
                icon: '👤',
                desc: 'Manage your profile details, password, and theme preferences.'
            };
        }

        return null;
    }

    async function handleOrbyUserQuery(query) {
        isOrbyThinking = true;
        appendOrbyUserMessage(query, true);

        // Check for navigation intent first
        const navTarget = detectNavigationIntent(query);
        if (navTarget) {
            isOrbyThinking = false;
            showOrbySpeech(`Navigating to ${navTarget.title}! 🚀`, 2500);

            const navCardHtml = `
                <div class="orby-nav-card" style="background:rgba(99,102,241,0.08);border:1.5px solid rgba(99,102,241,0.25);border-radius:12px;padding:12px 14px;margin-top:6px;">
                    <div style="font-size:1rem;font-weight:800;display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <span>${navTarget.icon}</span> <span>${navTarget.title}</span>
                    </div>
                    <p style="font-size:0.84rem;margin:0 0 10px;opacity:0.9;line-height:1.4;">${navTarget.desc}</p>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <a href="${navTarget.url}" class="orby-action-btn" style="background:#4F46E5;color:#FFF;padding:6px 14px;font-weight:700;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg> Open Now
                        </a>
                        <button type="button" class="orby-action-btn" style="background:transparent;border:1px solid #CBD5E1;padding:5px 12px;border-radius:8px;cursor:pointer;" onclick="cancelOrbyAutoNav(this)">
                            Cancel
                        </button>
                        <span class="orby-countdown-timer" style="font-size:0.78rem;opacity:0.8;font-weight:600;">(Auto-navigating in <span class="sec-num">2</span>s)</span>
                    </div>
                </div>
            `;
            appendOrbyBotMessage(navCardHtml, true);

            let remaining = 2;
            if (autoNavInterval) clearInterval(autoNavInterval);
            if (autoNavTimer) clearTimeout(autoNavTimer);

            autoNavInterval = setInterval(() => {
                remaining--;
                const timerSpan = document.querySelector('.orby-chat-messages .orby-countdown-timer .sec-num');
                if (timerSpan && remaining > 0) timerSpan.innerText = remaining;
                if (remaining <= 0) clearInterval(autoNavInterval);
            }, 1000);

            autoNavTimer = setTimeout(() => {
                clearInterval(autoNavInterval);
                window.location.href = navTarget.url;
            }, 2300);

            return;
        }

        showOrbyTypingIndicator();

        // Animate mascot to thinking state
        if (orbyMascotElement) {
            orbyMascotElement.classList.add('mascot-thinking');
        }
        showOrbySpeech("Thinking... ✨", 2500);

        const ctx = detectPageContext();

        try {
            const aiResponse = await fetchGeminiResponse(query, ctx);
            removeOrbyTypingIndicator();
            appendOrbyBotMessage(aiResponse, true);

            // Trigger happy celebration on answer
            if (orbyMouth) orbyMouth.classList.add('happy');
            createSparkles(window.innerWidth - 60, window.innerHeight - 60);
            setTimeout(() => {
                if (orbyMouth) orbyMouth.classList.remove('happy');
            }, 1200);

        } catch (err) {
            console.warn('Orby AI Gateway Warning:', err);
            removeOrbyTypingIndicator();
            // Provide intelligent local fallback
            const localFallback = getClientSmartFallback(query, ctx.role);
            appendOrbyBotMessage(localFallback, true);
        } finally {
            isOrbyThinking = false;
            if (orbyMascotElement) {
                orbyMascotElement.classList.remove('mascot-thinking');
            }
        }
    }

    async function fetchGeminiResponse(userQuery, ctx) {
        const endpoints = ['orby_ai_api.php', '/DASES/orby_ai_api.php'];
        for (const endpoint of endpoints) {
            try {
                const controller = new AbortController();
                const timer = setTimeout(() => controller.abort(), 35000);
                const proxyRes = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: userQuery,
                        history: orbyChatHistory.slice(-8),
                        context: ctx
                    }),
                    signal: controller.signal
                });
                clearTimeout(timer);
                if (proxyRes.ok) {
                    const d = await proxyRes.json();
                    if (d.success && d.reply) return d.reply;
                }
            } catch (e) {
                // Try next endpoint
            }
        }
        throw new Error('Proxy unavailable');
    }

    function getClientSmartFallback(query, role) {
        const q = query.toLowerCase();

        // What is DASES / Overview
        if (q === 'what is dases' || q.includes('tell me about dases') || q.includes('what is this') || q.includes('overview') || q.includes('about dases') || q.includes('what does dases') || q.includes('explain dases') || (q.includes('dases') && !q.includes('admin') && !q.includes('teacher') && !q.includes('student') && !q.includes('nav')) || q.includes('who are you')) {
            return "**DASES** stands for **Digital Answer Script Evaluation System** — a modern web platform that digitizes and streamlines the entire exam evaluation lifecycle.\n\n🔑 **Key Features:**\n• 📄 **Bulk PDF Upload** — Digitize physical answer sheets into the system\n• 🔒 **Blind Evaluation** — Student identities hidden via anonymous tokens (e.g. ANON-4F1A)\n• ✍️ **Teacher Digital Studio** — Annotate PDFs with pens, stamps, and comments\n• 📊 **Auto Scoring** — Best 5 of 8 Section B calculated automatically\n• 🎓 **Student Result Portal** — View marks, download grade cards, apply re-evaluation\n• 📋 **Audit Logs** — Every action tracked with IP timestamps";
        }
        if (q.includes('best 5') || q.includes('section b') || q.includes('calculation') || q.includes('scoring') || q.includes('how marks') || q.includes('how are marks')) {
            return "**DASES Section B Scoring:**\n\n• Section B has **8 questions** worth 10 marks each\n• DASES automatically picks the **5 highest scores** out of 8\n• Maximum = 5 × 10 = **50 marks**\n\nThis gives students the best possible advantage automatically — no manual selection needed.";
        }
        if (q.includes('pass') || q.includes('fail') || q.includes('criteria') || q.includes('grade') || q.includes('marks') || q.includes('total') || q.includes('35')) {
            return "**DASES Grading Formula:**\n\n| Component | Max Marks |\n|---|---|\n| Section A (10 × 3) | 30 |\n| Section B (Best 5 of 8 × 10) | 50 |\n| Internal Assessment | 20 |\n| **Grand Total** | **100** |\n\n✅ **PASS**: 35 or more marks\n❌ **FAIL**: Below 35 marks";
        }
        if (q.includes('upload') || q.includes('bulk') || q.includes('pdf')) {
            return "**Uploading Answer Scripts in DASES:**\n\n1. Go to **Admin Dashboard → Upload Papers** tab\n2. Select **Single Upload** or **Bulk Upload** (multiple PDFs at once)\n3. Assign student PINs to each script\n4. DASES securely stores and indexes every script for evaluation.";
        }
        if (q.includes('blind') || q.includes('anonymous') || q.includes('mask') || q.includes('anon')) {
            return "**Blind / Anonymous Evaluation in DASES:**\n\nWhen the Admin enables Blind Evaluation Mode, student Roll Numbers/PINs are replaced with randomized tokens (e.g. `ANON-3A8B1C`).\n\nTeachers evaluate scripts **without seeing any student identity**, ensuring fair, bias-free grading.";
        }
        if (q.includes('reeval') || q.includes('re-eval') || q.includes('recounting') || q.includes('grievance') || q.includes('appeal')) {
            return "**Re-evaluation Process in DASES:**\n\n1. Student visits **Student Result Portal**\n2. Clicks **Apply for Re-evaluation**\n3. Request appears in **Admin Dashboard → Re-evaluation Queue**\n4. Admin approves and reassigns to a secondary evaluator\n5. Updated marks are published back to the student portal.";
        }
        if (q.includes('teacher') || q.includes('eval') || q.includes('studio') || q.includes('annotate') || q.includes('stamp')) {
            return "**Teacher Evaluation Studio in DASES:**\n\n• Open assigned scripts from **Teacher Dashboard → Evaluation Queue**\n• Use the **PDF.js canvas** to view, zoom, and annotate answer scripts\n• Tools: ✏️ Pen, 🖊️ Highlighter, 🔖 Stamps (✓ ✗ ½ ?), 💬 Text Comments\n• Marks auto-save every **30 seconds** as drafts\n• Click **Submit Evaluation** to lock scores permanently.";
        }
        if (q.includes('student') || q.includes('result') || q.includes('marksheet') || q.includes('grade card') || q.includes('check')) {
            return "**Student Result Portal in DASES:**\n\n1. Visit the **Student Results** page\n2. Enter your **Roll Number** or **Blind Token**\n3. View your complete **question-wise marks breakdown**\n4. See your **letter grade** (O, A+, A, B, C, or F)\n5. Download your official **digital grade card**\n6. Apply for **Re-evaluation** if needed.";
        }
        if (q.includes('admin') || q.includes('dashboard') || q.includes('manage') || q.includes('export') || q.includes('csv') || q.includes('audit')) {
            return "**Admin Dashboard Features in DASES:**\n\n• 📁 **Bulk PDF Upload** — Upload multiple student scripts at once\n• 👥 **Faculty Assignment** — Assign scripts to evaluators anonymously\n• 🔒 **Blind Evaluation Toggle** — Mask student identities\n• 📊 **CSV Export** — Download complete marksheets as Excel files\n• 🔄 **Re-evaluation Queue** — Approve/reject student grievance appeals\n• 📋 **Audit Log** — Full history of every action with IP timestamps.";
        }
        if (q.includes('hi') || q.includes('hello') || q.includes('hey') || q.includes('helo') || q.includes('hii')) {
            return "👋 Hello! I'm **Orby**, your AI guide for DASES!\n\nI can help you with:\n• 📊 Scoring & grading rules\n• 👨‍💼 Admin workflows\n• 👨‍🏫 Teacher evaluation tools\n• 🎓 Student results & re-evaluation\n\nWhat would you like to know?";
        }

        return "I'm **Orby**, your DASES AI! Here are some things you can ask me:\n\n• *What is DASES?*\n• *How does Section B Best 5 scoring work?*\n• *What are the passing marks?*\n• *How does blind evaluation work?*\n• *How do I upload papers?*\n• *How do students check their results?*";
    }

    /* ==============================================================
       MARKDOWN PARSER & FORMATTER
       ============================================================== */
    function parseOrbyMarkdown(rawText) {
        if (!rawText) return '';

        let text = escapeHtml(rawText);

        // Bold **text**
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Italic *text*
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // Inline code `code`
        text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

        // Markdown Links [Label](url) -> converted into button links
        text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (match, label, url) => {
            return `<a href="${url}" class="orby-action-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin:2px 0;">🔗 ${label}</a>`;
        });

        // Headings ### Title
        text = text.replace(/^### (.*$)/gim, '<h5 style="margin:6px 0 4px;font-weight:800;color:inherit;">$1</h5>');
        text = text.replace(/^## (.*$)/gim, '<h4 style="margin:8px 0 4px;font-weight:800;color:inherit;">$1</h4>');

        // Bullet points • or -
        text = text.replace(/^\s*[-•*]\s+(.*)$/gim, '<li>$1</li>');
        text = text.replace(/(<li>.*<\/li>)/gims, '<ul style="margin:4px 0 8px 18px;padding:0;">$1</ul>');

        // Line breaks & paragraphs
        text = text.replace(/\n\n+/g, '</p><p>');
        text = text.replace(/\n/g, '<br>');

        // Wrap in <p> if not already
        if (!text.startsWith('<p>') && !text.startsWith('<ul') && !text.startsWith('<h')) {
            text = '<p>' + text + '</p>';
        }

        // Smart Action Pill Detectors
        if (!text.includes('orby-action-btn') && !text.includes('orby-nav-card')) {
            if (text.includes('Admin Dashboard') || text.includes('Bulk Upload')) {
                text += `<div style="margin-top:8px;"><button type="button" class="orby-action-btn" data-action="nav" data-param="admin-dashboard.php">👑 Open Admin Dashboard</button> <button type="button" class="orby-action-btn" data-action="tour" data-param="admin">🚀 Admin Tour</button></div>`;
            } else if (text.includes('Teacher') || text.includes('Best 5') || text.includes('Annotation')) {
                text += `<div style="margin-top:8px;"><button type="button" class="orby-action-btn" data-action="nav" data-param="teacher-dashboard.php">🎓 Open Teacher Portal</button> <button type="button" class="orby-action-btn" data-action="tour" data-param="teacher">🚀 Teacher Tour</button></div>`;
            } else if (text.includes('Student') || text.includes('Result') || text.includes('Re-evaluation')) {
                text += `<div style="margin-top:8px;"><button type="button" class="orby-action-btn" data-action="nav" data-param="student-result.php">📄 Check Results</button> <button type="button" class="orby-action-btn" data-action="tour" data-param="student">🚀 Student Tour</button></div>`;
            }
        }

        return text;
    }

    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function executeOrbyAction(action, param) {
        if (action === 'nav' && param) {
            window.location.href = param;
        } else if (action === 'tour' && param) {
            closeOrbyDrawer();
            window.startDasesTour(param);
        } else if (action === 'theme' && param) {
            applyTheme(param, true);
        }
    }

    function speakText(rawText) {
        if (!window.speechSynthesis) return;
        window.speechSynthesis.cancel();

        // Strip markdown and html for clean speech
        const cleanText = rawText.replace(/[*#`_•]/g, '').replace(/<[^>]*>/g, '').trim();
        const utterance = new SpeechSynthesisUtterance(cleanText);
        utterance.rate = 1.05;
        utterance.pitch = 1.0;

        // Animate mascot mouth while speaking
        utterance.onstart = () => {
            if (orbyMouth) orbyMouth.classList.add('happy');
        };
        utterance.onend = utterance.onerror = () => {
            if (orbyMouth) orbyMouth.classList.remove('happy');
        };

        window.speechSynthesis.speak(utterance);
    }

    /* ==============================================================
       4. SPOTLIGHT WALKTHROUGH ENGINE
       ============================================================== */
    const TOURS = {
        admin: [
            {
                selector: '.stat-card, .card',
                title: '📊 Live Evaluation Metrics',
                text: 'Track total uploaded answer sheets, pending reviews, completed evaluations, and pass percentages in real time.'
            },
            {
                selector: 'form[action*="upload"], .form-card, #uploadSection, input[type="file"]',
                fallbackSelector: '.main-content',
                title: '📁 Bulk & Single Paper Upload',
                text: 'Upload ZIP bundles or individual PDF answer scripts. DASES generates confidential blind PINs automatically.'
            },
            {
                selector: 'form[action*="assign"], .data-table',
                fallbackSelector: '.sidebar',
                title: '👨‍🏫 Faculty Assignment Matrix',
                text: 'Assign masked answer sheets to verified teachers without revealing any student identity details.'
            },
            {
                selector: 'a[href*="audit"], .pin-tag, .btn-secondary',
                fallbackSelector: '.top-navbar',
                title: '🛡️ Audit Logs & Fraud Prevention',
                text: 'Every mark entry, teacher assignment, and re-evaluation is cryptographically timestamped in the audit log.'
            }
        ],
        teacher: [
            {
                selector: '.stat-card, .card',
                title: '📋 Your Evaluation Queue',
                text: 'Check papers currently assigned to you for first-time evaluation or authorized re-evaluation.'
            },
            {
                selector: '.data-table, .subject-card, a[href*="eval"]',
                fallbackSelector: '.main-content',
                title: '✍️ Digital Scoring Interface',
                text: 'Click on any paper bundle to review scanned student answers and award section-by-section marks.'
            },
            {
                selector: '.btn-primary, .btn-submit-animated',
                fallbackSelector: '.top-navbar',
                title: '🔒 Mark Validation & Lock',
                text: 'DASES validates question totals automatically (35/100 pass threshold). Submissions are permanently locked.'
            }
        ],
        student: [
            {
                selector: 'input[name*="hall"], input[name*="pin"], .search-card',
                fallbackSelector: '.main-content',
                title: '🔍 Result Lookup by PIN or Hall Ticket',
                text: 'Enter your Hall Ticket number or confidential Blind PIN to access your verified marksheet.'
            },
            {
                selector: '.gradecard-header, .table-container, .data-table',
                fallbackSelector: '.card',
                title: '📄 Comprehensive Marksheet Breakdown',
                text: 'View subject-wise scores, total percentage, pass/fail status, and official evaluation timestamps.'
            },
            {
                selector: 'a[href*="reeval"], button[type="submit"], .btn-primary',
                fallbackSelector: '.card',
                title: '🔄 Re-evaluation & Recounting',
                text: 'Unhappy with your grade? You can easily file a digital re-evaluation application directly from here.'
            }
        ]
    };

    let activeTourSteps = [];
    let currentStepIndex = 0;

    function initSpotlightTourEngine() {
        if (document.getElementById('orby-spotlight-box')) return;

        const targetBox = document.createElement('div');
        targetBox.id = 'orby-spotlight-box';
        targetBox.className = 'orby-spotlight-target-box';
        targetBox.style.display = 'none';

        const popover = document.createElement('div');
        popover.id = 'orby-tour-popover';
        popover.className = 'orby-tour-popover';
        popover.style.display = 'none';

        document.body.appendChild(targetBox);
        document.body.appendChild(popover);
    }

    function startTourForCurrentPage() {
        const ctx = detectPageContext();
        let tourKey = ctx.role;
        if (!TOURS[tourKey]) tourKey = 'admin';
        window.startDasesTour(tourKey);
    }

    window.startDasesTour = function (tourName) {
        const tour = TOURS[tourName] || TOURS.admin;
        activeTourSteps = tour;
        currentStepIndex = 0;
        closeOrbyDrawer();

        showTourStep(0);
    };

    function showTourStep(index) {
        if (index < 0 || index >= activeTourSteps.length) {
            endTour();
            return;
        }

        currentStepIndex = index;
        const step = activeTourSteps[index];

        let targetEl = document.querySelector(step.selector);
        if (!targetEl && step.fallbackSelector) {
            targetEl = document.querySelector(step.fallbackSelector);
        }
        if (!targetEl) targetEl = document.body;

        targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });

        setTimeout(() => {
            const rect = targetEl.getBoundingClientRect();
            const box = document.getElementById('orby-spotlight-box');
            const popover = document.getElementById('orby-tour-popover');

            if (box && targetEl !== document.body) {
                box.style.display = 'block';
                box.style.top = `${window.scrollY + rect.top - 6}px`;
                box.style.left = `${window.scrollX + rect.left - 6}px`;
                box.style.width = `${rect.width + 12}px`;
                box.style.height = `${rect.height + 12}px`;
            } else if (box) {
                box.style.display = 'none';
            }

            if (popover) {
                popover.style.display = 'block';
                let popTop = window.scrollY + rect.bottom + 16;
                let popLeft = window.scrollX + rect.left;

                if (popLeft + 330 > window.innerWidth) {
                    popLeft = window.innerWidth - 350;
                }
                if (popTop + 200 > window.scrollY + window.innerHeight) {
                    popTop = Math.max(20, window.scrollY + rect.top - 210);
                }

                popover.style.top = `${Math.max(20, popTop)}px`;
                popover.style.left = `${Math.max(20, popLeft)}px`;

                popover.innerHTML = `
                    <div class="orby-popover-header">
                        <span class="orby-step-pill">Step ${index + 1} of ${activeTourSteps.length}</span>
                        <button type="button" style="background:none;border:none;cursor:pointer;font-weight:bold;color:#94A3B8;font-size:1.1rem;" id="btnTourSkip">✕</button>
                    </div>
                    <h4 class="orby-popover-title">${step.title}</h4>
                    <p class="orby-popover-text">${step.text}</p>
                    <div class="orby-popover-actions">
                        ${index > 0 ? `<button type="button" class="orby-btn-sm orby-btn-secondary" id="btnTourPrev">← Back</button>` : `<div></div>`}
                        <div class="orby-popover-actions-right">
                            <button type="button" class="orby-btn-sm orby-btn-primary" id="btnTourNext">
                                ${index === activeTourSteps.length - 1 ? 'Finish 🎉' : 'Next ➔'}
                            </button>
                        </div>
                    </div>
                `;

                document.getElementById('btnTourSkip').addEventListener('click', (e) => {
                    e.stopPropagation();
                    endTour(false);
                });
                if (document.getElementById('btnTourPrev')) {
                    document.getElementById('btnTourPrev').addEventListener('click', (e) => {
                        e.stopPropagation();
                        showTourStep(index - 1);
                    });
                }
                document.getElementById('btnTourNext').addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (index === activeTourSteps.length - 1) {
                        endTour(true);
                    } else {
                        showTourStep(index + 1);
                    }
                });
            }
        }, 250);
    }

    function endTour(celebrate = false) {
        const box = document.getElementById('orby-spotlight-box');
        const popover = document.getElementById('orby-tour-popover');

        if (box) box.style.display = 'none';
        if (popover) popover.style.display = 'none';

        if (celebrate) {
            launchConfetti();
            showOrbySpeech("🎉 Awesome! You completed the guided tour!", 4000);
        }
    }

    /* ==============================================================
       5. CONSTELLATION BACKGROUND CANVAS
       ============================================================== */
    function initCanvas() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        if (document.body.classList.contains('eval-page') || window.location.pathname.includes('teacher-eval.php')) return;
        if (document.getElementById('dases-particle-canvas')) return;

        const canvas = document.createElement('canvas');
        canvas.id = 'dases-particle-canvas';
        canvas.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:0;opacity:0.6;';
        document.body.insertBefore(canvas, document.body.firstChild);

        const ctx = canvas.getContext('2d');
        let w = (canvas.width = window.innerWidth);
        let h = (canvas.height = window.innerHeight);

        let mouse = { x: -1000, y: -1000, radius: 130 };
        const particles = [];
        const count = Math.min(Math.floor((w * h) / 25000), 45);
        const neutralColors = [
            'rgba(148, 163, 184, 0.45)',
            'rgba(160, 174, 192, 0.4)',
            'rgba(100, 116, 139, 0.35)',
            'rgba(203, 213, 225, 0.5)'
        ];

        class Particle {
            constructor() {
                this.x = Math.random() * w;
                this.y = Math.random() * h;
                this.vx = (Math.random() - 0.5) * 0.5;
                this.vy = (Math.random() - 0.5) * 0.5;
                this.r = Math.random() * 3.5 + 2;
                this.c = neutralColors[Math.floor(Math.random() * neutralColors.length)];
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                if (this.x < 0 || this.x > w) this.vx *= -1;
                if (this.y < 0 || this.y > h) this.vy *= -1;

                const dx = mouse.x - this.x;
                const dy = mouse.y - this.y;
                const dist = Math.hypot(dx, dy);
                if (dist < mouse.radius) {
                    const force = (mouse.radius - dist) / mouse.radius;
                    const angle = Math.atan2(dy, dx);
                    this.x -= Math.cos(angle) * force * 2;
                    this.y -= Math.sin(angle) * force * 2;
                }
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
                ctx.fillStyle = this.c;
                ctx.fill();
            }
        }

        for (let i = 0; i < count; i++) particles.push(new Particle());

        window.addEventListener('resize', () => {
            w = canvas.width = window.innerWidth;
            h = canvas.height = window.innerHeight;
        }, { passive: true });

        window.addEventListener('mousemove', e => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        }, { passive: true });

        window.addEventListener('mouseleave', () => {
            mouse.x = -1000;
            mouse.y = -1000;
        });

        function render() {
            if (!document.hidden) {
                ctx.clearRect(0, 0, w, h);
                for (let i = 0; i < particles.length; i++) {
                    particles[i].update();
                    particles[i].draw();
                    for (let j = i + 1; j < particles.length; j++) {
                        const dx = particles[i].x - particles[j].x;
                        const dy = particles[i].y - particles[j].y;
                        const dist = Math.hypot(dx, dy);
                        if (dist < 110) {
                            ctx.beginPath();
                            ctx.strokeStyle = `rgba(148, 163, 184, ${(1 - dist / 110) * 0.35})`;
                            ctx.lineWidth = 1.2;
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(particles[j].x, particles[j].y);
                            ctx.stroke();
                        }
                    }
                }
            }
            requestAnimationFrame(render);
        }
        render();
    }

    /* ==============================================================
       6. CONFETTI CELEBRATION
       ============================================================== */
    function checkConfetti() {
        const isStudentResult = window.location.pathname.includes('student-result') || document.querySelector('.result-container');
        if (!isStudentResult) return;

        const passBadge = document.querySelector('.result-badge.pass, .status-badge.pass, #result-badge.pass');
        const summaryContainer = document.querySelector('.result-container');
        const hasPass = passBadge || (summaryContainer && summaryContainer.innerText.includes('PASS'));

        if (hasPass && !sessionStorage.getItem('dases_confetti_fired')) {
            sessionStorage.setItem('dases_confetti_fired', 'true');
            setTimeout(launchConfetti, 400);
        }
    }

    function launchConfetti() {
        const canvas = document.createElement('canvas');
        canvas.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:9999999;';
        document.body.appendChild(canvas);
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const pieces = [];
        const colors = ['#10B981', '#6366F1', '#F59E0B', '#3B82F6', '#EC4899', '#8B5CF6'];

        for (let i = 0; i < 90; i++) {
            pieces.push({
                x: canvas.width * 0.5,
                y: canvas.height * 0.4,
                w: Math.random() * 8 + 5,
                h: Math.random() * 5 + 3,
                vx: (Math.random() - 0.5) * 14,
                vy: (Math.random() - 1.1) * 12,
                gravity: 0.3,
                rot: Math.random() * 360,
                rotSpeed: (Math.random() - 0.5) * 10,
                color: colors[Math.floor(Math.random() * colors.length)],
                opacity: 1
            });
        }

        function anim() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            let alive = false;
            pieces.forEach(p => {
                p.x += p.vx;
                p.y += p.vy;
                p.vy += p.gravity;
                p.rot += p.rotSpeed;
                p.opacity -= 0.007;

                if (p.opacity > 0) {
                    alive = true;
                    ctx.save();
                    ctx.translate(p.x, p.y);
                    ctx.rotate((p.rot * Math.PI) / 180);
                    ctx.globalAlpha = Math.max(p.opacity, 0);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                    ctx.restore();
                }
            });

            if (alive) {
                requestAnimationFrame(anim);
            } else {
                canvas.remove();
            }
        }
        anim();
    }

    /* ==============================================================
       7. CLICK TO COPY
       ============================================================== */
    function initCopy() {
        document.querySelectorAll('.pin-tag, strong[style*="monospace"]').forEach(tag => {
            const txt = tag.innerText.trim();
            if (txt.length >= 3) {
                tag.style.cursor = 'pointer';
                tag.title = 'Click to copy';
                tag.addEventListener('click', e => {
                    e.stopPropagation();
                    navigator.clipboard.writeText(txt).then(() => {
                        showCopyBadge(e.clientX, e.clientY, txt);
                    }).catch(() => {});
                });
            }
        });
    }

    function showCopyBadge(x, y, text) {
        const badge = document.createElement('div');
        badge.className = 'dases-copy-toast';
        badge.innerHTML = `Copied: <strong>${text}</strong>`;
        badge.style.cssText = `position:fixed;left:${x}px;top:${y - 35}px;background:#1E293B;color:#FFFFFF;padding:5px 10px;border-radius:8px;font-size:0.75rem;font-weight:700;z-index:999999;box-shadow:0 4px 12px rgba(0,0,0,0.2);pointer-events:none;transition:opacity 0.3s;`;
        document.body.appendChild(badge);
        setTimeout(() => {
            badge.style.opacity = '0';
            setTimeout(() => badge.remove(), 350);
        }, 1100);
    }

    /* ==============================================================
       8. COUNTERS & RIPPLES
       ============================================================== */
    function initCounters() {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const text = el.innerText.trim();
                    const match = text.match(/([\d.]+)/);
                    if (match) {
                        const target = parseFloat(match[1]);
                        const isFloat = text.includes('.') && !text.endsWith('%');
                        const suffix = text.includes('%') ? '%' : (text.includes('/ 100') ? ' / 100' : '');
                        let start = performance.now();
                        function step(now) {
                            const p = Math.min((now - start) / 1000, 1);
                            const val = target * (1 - Math.pow(1 - p, 3));
                            el.innerText = (isFloat ? val.toFixed(1) : Math.floor(val)) + suffix;
                            if (p < 1) requestAnimationFrame(step);
                            else el.innerText = (isFloat ? target.toFixed(1) : target) + suffix;
                        }
                        requestAnimationFrame(step);
                    }
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.3 });

        document.querySelectorAll('.card .number').forEach(el => observer.observe(el));
    }

    function initRipples() {
        document.querySelectorAll('.btn-primary, .btn-success, .btn-secondary, .btn-submit-animated, .portal-btn, .sidebar nav a, .role-tab').forEach(btn => {
            btn.addEventListener('click', function (e) {
                const rect = btn.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height) * 1.4;
                const ripple = document.createElement('span');
                ripple.className = 'dases-ripple';
                ripple.style.width = size + 'px';
                ripple.style.height = size + 'px';
                ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
                ripple.style.top = `${e.clientY - rect.top - size / 2}px`;
                btn.appendChild(ripple);
                setTimeout(() => ripple.remove(), 550);
            });
        });
    }

    function initSpotlight() {
        document.querySelectorAll('.card, .portal-box, .form-card, .login-box, .register-box, .result-card').forEach(card => {
            card.classList.add('dases-spotlight');
            card.addEventListener('mousemove', e => {
                const r = card.getBoundingClientRect();
                card.style.setProperty('--mouse-x', `${e.clientX - r.left}px`);
                card.style.setProperty('--mouse-y', `${e.clientY - r.top}px`);
            });
        });
    }

    /* ==============================================================
       9. MULTI-THEME ENGINE & PALETTE SWITCHER
       ============================================================== */
    const THEMES = [
        { id: 'indigo', name: 'Indigo Crystal', class: '', swatch: 'theme-swatch-indigo', orbyMsg: '✨ Classic Indigo Mode active!' },
        { id: 'midnight', name: 'Midnight Cyber', class: 'theme-midnight dark-mode', swatch: 'theme-swatch-midnight', orbyMsg: '🌌 Stealth Midnight Cyber activated!' },
        { id: 'emerald', name: 'Emerald Academy', class: 'theme-emerald', swatch: 'theme-swatch-emerald', orbyMsg: '🌿 Calming Emerald Forest enabled!' },
        { id: 'amethyst', name: 'Royal Amethyst', class: 'theme-amethyst', swatch: 'theme-swatch-amethyst', orbyMsg: '🔮 Luxury Royal Amethyst theme!' },
        { id: 'sunset', name: 'Sunset Amber', class: 'theme-sunset', swatch: 'theme-swatch-sunset', orbyMsg: '🌅 Warm Golden Sunset theme!' }
    ];

    function applyTheme(themeId, notifyOrby = false) {
        const selected = THEMES.find(t => t.id === themeId) || THEMES[0];
        
        document.body.classList.remove('theme-midnight', 'dark-mode', 'theme-emerald', 'theme-amethyst', 'theme-sunset');
        
        if (selected.class) {
            selected.class.split(' ').forEach(cls => {
                if (cls) document.body.classList.add(cls);
            });
        }

        const isDark = selected.id === 'midnight' || document.body.classList.contains('dark-mode');

        localStorage.setItem('dases_active_theme', selected.id);
        localStorage.setItem('dases_theme', isDark ? 'dark' : 'light');

        // Update all navbar theme toggle buttons
        document.querySelectorAll('#btn-theme-toggle, .btn-theme-toggle').forEach(btn => {
            if (isDark) {
                btn.innerHTML = '<i data-lucide="sun" style="width:16px;height:16px;"></i>';
                btn.title = 'Switch to Light Mode';
                btn.setAttribute('aria-label', 'Switch to Light Mode');
                btn.style.background = '#1E293B';
                btn.style.borderColor = '#475569';
                btn.style.color = '#F8FAFC';
            } else {
                btn.innerHTML = '<i data-lucide="moon" style="width:16px;height:16px;"></i>';
                btn.title = 'Switch to Dark Mode';
                btn.setAttribute('aria-label', 'Switch to Dark Mode');
                btn.style.background = '#F1F5F9';
                btn.style.borderColor = '#CBD5E1';
                btn.style.color = '#334155';
            }
        });
        if (window.lucide) lucide.createIcons();

        document.querySelectorAll('.theme-swatch').forEach(sw => {
            sw.classList.toggle('active', sw.dataset.themeId === selected.id);
        });

        if (notifyOrby && typeof window.orbySpeak === 'function') {
            window.orbySpeak(selected.orbyMsg);
        }
    }

    function toggleDarkMode() {
        const currentTheme = localStorage.getItem('dases_active_theme') || (document.body.classList.contains('dark-mode') ? 'midnight' : 'indigo');
        if (currentTheme === 'midnight' || document.body.classList.contains('dark-mode')) {
            applyTheme('indigo', true);
        } else {
            applyTheme('midnight', true);
        }
    }

    // Expose globally to window object for inline onclick and external calls
    window.toggleDarkMode = toggleDarkMode;
    window.applyTheme = applyTheme;

    function initThemeEngine() {
        const savedTheme = localStorage.getItem('dases_active_theme') || (localStorage.getItem('dases_theme') === 'dark' ? 'midnight' : 'indigo');
        applyTheme(savedTheme, false);
        createThemeSwitcherBar();
    }

    function createThemeSwitcherBar() {
        if (document.body.classList.contains('eval-page') || window.location.pathname.includes('teacher-eval.php')) return;
        if (document.getElementById('dases-theme-bar')) return;

        const bar = document.createElement('div');
        bar.id = 'dases-theme-bar';
        bar.className = 'dases-theme-bar no-print';
        bar.title = 'Change DASES Color Theme';

        const label = document.createElement('span');
        label.style.fontSize = '0.74rem';
        label.style.fontWeight = '800';
        label.style.color = 'var(--text-muted, #64748B)';
        label.style.letterSpacing = '0.3px';
        label.innerText = 'THEME';
        bar.appendChild(label);

        const currentTheme = localStorage.getItem('dases_active_theme') || 'indigo';

        THEMES.forEach(theme => {
            const swatch = document.createElement('button');
            swatch.type = 'button';
            swatch.className = `theme-swatch ${theme.swatch} ${theme.id === currentTheme ? 'active' : ''}`;
            swatch.dataset.themeId = theme.id;
            swatch.title = theme.name;
            swatch.setAttribute('aria-label', theme.name);

            swatch.addEventListener('click', (e) => {
                e.stopPropagation();
                applyTheme(theme.id, true);
            });

            bar.appendChild(swatch);
        });

        document.body.appendChild(bar);
    }

    /* ==============================================================
       INITIALIZE ALL
       ============================================================== */
    function initAll() {
        initThemeEngine();
        createOrbyMascot();
        initCanvas();
        checkConfetti();
        initCopy();
        initCounters();
        initRipples();
        initSpotlight();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

})();
