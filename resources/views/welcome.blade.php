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
                linear-gradient(160deg, rgba(255, 247, 225, 0.95) 0%, rgba(255, 243, 211, 0.92) 45%, rgba(251, 240, 207, 0.92) 100%),
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
            box-shadow: inset 0 0 0 1px rgba(255, 232, 184, 0.2);
            color: #4a3310;
        }

        .golden-theme .fi-sc-wizard-step,
        .golden-theme .fi-sc-wizard-footer {
            background: transparent;
        }

        .golden-theme .fi-sc-wizard-step > .fi-sc-wizard-step-content,
        .golden-theme .fi-sc-wizard .fi-section,
        .golden-theme .fi-section-content,
        .golden-theme .fi-section-header {
            background: linear-gradient(180deg, rgba(255, 252, 246, 0.98), rgba(252, 247, 235, 0.98));
            border-color: rgba(212, 175, 55, 0.25);
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

        .golden-theme .fi-sc-wizard-header-step-icon-ctn {
            box-shadow: inset 0 0 0 1px rgba(255, 215, 120, 0.2);
        }

        .golden-theme .fi-input-wrp,
        .golden-theme .fi-input,
        .golden-theme .fi-select-input,
        .golden-theme .fi-fo-field-wrp {
            background-color: rgba(255, 255, 255, 0.7);
            border-color: rgba(212, 175, 55, 0.25);
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

    @filamentScripts(withCore: true)
</body>
</html>
