<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="fi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif

    @livewireStyles
    @filamentStyles

    {{ filament()->getTheme()->getHtml() }}
    {{ filament()->getFontHtml() }}
    {{ filament()->getMonoFontHtml() }}
    {{ filament()->getSerifFontHtml() }}

    <style>
        :root {
            --font-family: '{!! filament()->getFontFamily() !!}';
            --mono-font-family: '{!! filament()->getMonoFontFamily() !!}';
            --serif-font-family: '{!! filament()->getSerifFontFamily() !!}';
            --sidebar-width: {{ filament()->getSidebarWidth() }};
            --collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};
            --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};
        }

        .golden-theme {
            background:
                linear-gradient(160deg, rgba(255, 247, 225, 0.25) 0%, rgba(255, 243, 211, 0.50) 45%, rgba(251, 240, 207, 0.92) 100%),
                url('{{ asset('bg.png') }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #3a2b10;
        }

        .golden-title {
            color: #f5d585;
            letter-spacing: 0.08em;
        }

        .golden-outline {
            border: 1px solid rgba(212, 175, 55, 0.3);
            box-shadow: inset 0 0 0 1px rgba(255, 244, 212, 0.06);
        }

        .golden-theme .fi-sc-wizard,
        .golden-theme .fi-sc-wizard-header {
            background: linear-gradient(180deg, rgba(255, 252, 246, 0.98), rgba(252, 247, 235, 0.98));
            border: 1px solid rgba(212, 175, 55, 0.25);
            color: #4a3310;
        }

        .golden-theme .fi-sc-wizard-step,
        .golden-theme .fi-sc-wizard-footer {
            background: transparent;
        }

        .golden-theme .fi-sc-wizard-header {
            border: 0;
            box-shadow: none;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }

        .golden-theme .fi-sc-wizard-step > .fi-sc-wizard-step-content,
        .golden-theme .fi-sc-wizard .fi-section,
        .golden-theme .fi-section-content,
        .golden-theme .fi-section-header {
            background: linear-gradient(180deg, rgba(255, 252, 246, 0.98), rgba(252, 247, 235, 0.98));
            border-color: transparent;
            box-shadow: none;
        }

        .golden-theme .fi-sc-wizard-step > .fi-sc-wizard-step-content,
        .golden-theme .fi-sc-wizard .fi-section {
            border: 0;
            box-shadow: none;
        }

        .golden-theme .fi-section-header {
            color: #6b4a12;
        }

        .golden-theme .fi-sc-wizard-header-step-label,
        .golden-theme .fi-sc-wizard-header-step-number {
            color: #8b650f;
        }

        .golden-theme .fi-sc-wizard-header-step.fi-active .fi-sc-wizard-header-step-label,
        .golden-theme .fi-sc-wizard-header-step.fi-active .fi-sc-wizard-header-step-number {
            color: #b98010;
        }

        .golden-theme .fi-sc-wizard-header-step.fi-active {
            border: 0 !important;
        }

        .golden-theme .fi-sc-wizard-header-step-icon-ctn {
            box-shadow: inset 0 0 0 1px rgba(255, 215, 120, 0.2);
        }

        .golden-theme .fi-input-wrp,
        .golden-theme .fi-input,
        .golden-theme .fi-select-input,
        .golden-theme .fi-fo-field-wrp {
            background-color: rgba(255, 255, 255, 0.7);
        }

        .golden-theme .fi-input-wrp,
        .golden-theme .fi-fo-field-wrp {
            border: 1px solid rgba(212, 175, 55, 0.45);
            box-shadow: inset 0 0 0 1px rgba(255, 236, 190, 0.35);
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .golden-theme .fi-input,
        .golden-theme .fi-select-input {
            border-color: transparent;
            box-shadow: none;
        }

        .golden-theme .fi-input-wrp:focus-within,
        .golden-theme .fi-fo-field-wrp:focus-within {
            box-shadow:
                inset 0 0 0 1px rgba(255, 236, 190, 0.45),
                0 0 0 1px rgba(212, 175, 55, 0.35);
        }

        .golden-theme .fi-input::placeholder,
        .golden-theme .fi-select-input::placeholder,
        .golden-theme .fi-fo-field-wrp ::placeholder {
            color: rgba(107, 74, 18, 0.55);
        }

        .golden-theme .fi-input,
        .golden-theme .fi-select-input,
        .golden-theme .fi-fo-field-wrp,
        .golden-theme .fi-fo-field-wrp-label,
        .golden-theme .fi-fo-field-wrp-helper-text {
            color: #4a3310;
        }

        .golden-theme .fi-select-input option,
        .golden-theme select option {
            background-color: #fff8ea;
            color: #4a3310;
        }

        .golden-theme .fi-select-input-options-ctn,
        .golden-theme .fi-select-input-option-group {
            background-color: #fff8ea !important;
            border-color: rgba(212, 175, 55, 0.35) !important;
        }

        .golden-theme .fi-select-input-option {
            background-color: transparent !important;
            color: #4a3310 !important;
        }

        .golden-theme .fi-select-input-option.fi-selected,
        .golden-theme .fi-select-input-option.fi-focused {
            background-color: rgba(212, 175, 55, 0.2) !important;
            color: #4a3310 !important;
        }

        .golden-theme .fi-sc-wizard,
        .golden-theme .fi-sc-wizard * {
            color: #4a3310;
        }

        .golden-theme .fi-sc-wizard :where(.dark *) {
            color: #4a3310;
        }

        .golden-theme .fi-sc-wizard :where(.dark *) .fi-btn {
            color: #4a3310;
        }

        .golden-theme .fi-sc-wizard .fi-sc-wizard-header-step-icon-ctn,
        .golden-theme .fi-sc-wizard .fi-sc-wizard-header-step-icon-ctn * {
            color: #b98010;
        }

        .golden-theme .fi-sc-wizard-footer .fi-btn {
            border-color: rgba(212, 175, 55, 0.35);
        }

        .golden-theme .fi-btn,
        .golden-theme .fi-btn * {
            color: #4a3310;
        }

        .golden-theme .fi-btn.fi-color-gray {
            background-color: rgba(212, 205, 190, 0.85) !important;
            border-color: transparent !important;
            box-shadow: none !important;
        }

        .golden-theme .fi-btn.fi-color-gray,
        .golden-theme .fi-btn.fi-color-gray :where(*) {
            color: #4a3310 !important;
        }

        .golden-theme .fi-modal,
        .golden-theme .fi-modal-window,
        .golden-theme .fi-modal-content,
        .golden-theme .fi-modal-header,
        .golden-theme .fi-modal-footer {
            background: linear-gradient(180deg, rgba(255, 252, 246, 0.98), rgba(252, 247, 235, 0.98)) !important;
            color: #4a3310 !important;
            border-color: rgba(212, 175, 55, 0.25) !important;
        }

        .golden-theme .fi-modal-heading,
        .golden-theme .fi-modal-description,
        .golden-theme .fi-modal-body {
            color: #4a3310 !important;
        }

        .golden-theme .fi-modal-close-button,
        .golden-theme .fi-modal-close-button svg {
            color: #8b650f !important;
        }

        .golden-theme .fi-sc-wizard,
        .golden-theme .fi-section,
        .golden-theme .fi-section-content,
        .golden-theme .fi-section-header,
        .golden-theme .fi-input-wrp,
        .golden-theme .fi-input,
        .golden-theme .fi-select-input,
        .golden-theme .fi-fo-field-wrp,
        .golden-theme .fi-btn,
        .golden-theme .fi-sc-wizard-header-step,
        .golden-theme .fi-sc-wizard-header-step-icon-ctn {
            transition:
                color 200ms ease,
                background-color 200ms ease,
                border-color 200ms ease,
                box-shadow 200ms ease,
                transform 200ms ease,
                opacity 200ms ease;
        }

        .golden-theme .fi-sc-wizard.wizard-animate[data-direction='next'] {
            animation: wizard-card-next 650ms ease both;
        }

        .golden-theme .fi-sc-wizard.wizard-animate[data-direction='prev'] {
            animation: wizard-card-prev 650ms ease both;
        }

        @keyframes wizard-card-next {
            0% {
                opacity: 0;
                transform: translateX(24px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes wizard-card-prev {
            0% {
                opacity: 0;
                transform: translateX(-24px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .force-mobile {
            width: 100%;
            max-width: 690px;
        }

        .force-mobile .fi-sc-wizard-header {
            display: block !important;
            overflow-x: hidden !important;
        }

        .force-mobile .fi-sc-wizard-header-step {
            display: none !important;
        }

        .force-mobile .fi-sc-wizard-header-step.fi-active {
            display: flex !important;
        }

        .force-mobile .fi-sc-wizard-header-step-separator {
            display: none !important;
        }

        .force-mobile .fi-sc-wizard-header-step-text {
            width: auto !important;
            max-width: none !important;
        }

        .gold-snow {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 1;
        }

        .gold-snow::before,
        .gold-snow::after {
            content: "";
            position: absolute;
            inset: -200% -50% 0 -50%;
            background-image:
                radial-gradient(2.5px 2.5px at 20% 30%, rgba(212, 175, 55, 0.6), transparent 55%),
                radial-gradient(1.5px 1.5px at 60% 10%, rgba(255, 233, 164, 0.7), transparent 60%),
                radial-gradient(2px 2px at 40% 70%, rgba(190, 148, 34, 0.55), transparent 60%),
                radial-gradient(2.5px 2.5px at 80% 40%, rgba(255, 211, 102, 0.75), transparent 60%),
                radial-gradient(1.5px 1.5px at 15% 85%, rgba(255, 230, 164, 0.6), transparent 60%),
                radial-gradient(1px 1px at 70% 80%, rgba(255, 245, 210, 0.75), transparent 60%),
                radial-gradient(1px 1px at 35% 15%, rgba(210, 160, 40, 0.6), transparent 60%);
            opacity: 0.75;
            animation: gold-drift 14s linear infinite;
        }

        .gold-snow::after {
            opacity: 0.45;
            animation-duration: 20s;
            transform: translateY(-20%);
        }

        @keyframes gold-drift {
            0% {
                transform: translate3d(0, -15%, 0);
            }
            100% {
                transform: translate3d(0, 40%, 0);
            }
        }
    </style>
</head>
<body class="fi-body fi-panel-{{ filament()->getId() }} golden-theme">
    <div class="gold-snow"></div>
    <main class="min-h-screen flex items-center justify-center relative z-10">
        <div class="w-full px-4 py-10 flex justify-center">
            <div class="w-full max-w-6xl px-2">
                @livewire('invitation-welcome')
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const setupWizard = (wizardEl) => {
                if (!wizardEl || wizardEl.dataset.slideBound === 'true') {
                    return;
                }

                wizardEl.dataset.slideBound = 'true';

                const getActiveIndex = () => {
                    const steps = Array.from(wizardEl.querySelectorAll('.fi-sc-wizard-step'));
                    const activeIndex = steps.findIndex((step) => step.classList.contains('fi-active'));
                    return { steps, activeIndex };
                };

                const updateDirection = () => {
                    const { activeIndex } = getActiveIndex();
                    const prevIndex = Number(wizardEl.dataset.activeIndex ?? -1);

                if (activeIndex !== -1 && prevIndex !== -1 && activeIndex !== prevIndex) {
                    wizardEl.dataset.direction = activeIndex > prevIndex ? 'next' : 'prev';
                    wizardEl.classList.remove('wizard-animate');
                    void wizardEl.offsetWidth;
                    wizardEl.classList.add('wizard-animate');
                }

                    if (activeIndex !== -1) {
                        wizardEl.dataset.activeIndex = String(activeIndex);
                    }
                };

                const { steps } = getActiveIndex();
                steps.forEach((step) => {
                    const observer = new MutationObserver(updateDirection);
                    observer.observe(step, { attributes: true, attributeFilter: ['class'] });
                });

                updateDirection();
            };

            document.querySelectorAll('.fi-sc-wizard').forEach(setupWizard);

            const rootObserver = new MutationObserver(() => {
                document.querySelectorAll('.fi-sc-wizard').forEach(setupWizard);
            });

            rootObserver.observe(document.body, { childList: true, subtree: true });
        });
    </script>

    @livewireScripts
    @filamentScripts(withCore: true)
</body>
</html>
