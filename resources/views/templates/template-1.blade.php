<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invitation->title ?? 'Invitation' }}</title>
    @vite(['resources/css/app.css'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    @php
        $bride = $bride ?? $invitation->people->firstWhere('role', 'bride');
        $groom = $groom ?? $invitation->people->firstWhere('role', 'groom');
        $defaultBg = 'https://api.our-wedding.link/uploads/8cde97a0-cc1d-11ee-8a12-1d71291003ab.jpg';
        $heroImage = $invitation->couple?->coupleImage?->publicUrl()
            ?? $invitation->galleryItems->first()?->image?->publicUrl()
            ?? $defaultBg;
        $heroTitle = $invitation->title
            ?? trim(($invitation->couple?->couple_name_1 ?? '') . ' & ' . ($invitation->couple?->couple_name_2 ?? ''));
        $heroDescription = $invitation->couple?->wedding_date_display;
        $gallerySlides = $invitation->galleryItems
            ->map(function ($item, $index) use ($invitation) {
                $src = $item->image?->publicUrl();
                if (! $src) {
                    return null;
                }

                return [
                    'imgSrc' => $src,
                    'imgAlt' => $item->image?->alt_text
                        ?? ($invitation->title ? $invitation->title . ' photo' : 'Gallery ' . ($index + 1)),
                ];
            })
            ->filter()
            ->values()
            ->all();
        if (empty($gallerySlides) && $heroImage) {
            $gallerySlides[] = [
                'imgSrc' => $heroImage,
                'imgAlt' => $heroTitle ?: 'Gallery',
            ];
        }
        $heroSlides = array_map(function ($slide) use ($heroTitle, $heroDescription) {
            return array_merge($slide, [
                'title' => $heroTitle,
                'description' => $heroDescription,
            ]);
        }, $gallerySlides);
        if (empty($heroSlides) && $heroImage) {
            $heroSlides = [[
                'imgSrc' => $heroImage,
                'imgAlt' => ($invitation->couple?->couple_name_1 ?? 'Couple') . ' & ' . ($invitation->couple?->couple_name_2 ?? ''),
                'title' => $heroTitle,
                'description' => $heroDescription,
            ]];
        }
        $eventSectionTitle = $invitation->eventSection?->section_title ?? 'Events';
        $wishItems = $invitation->wishSamples->isNotEmpty()
            ? $invitation->wishSamples
            : $invitation->guestbookEntries;
        $hasMusic = (bool) $invitation->music?->audio?->publicUrl();
        $brideSlides = [];
        if ($bride?->photo?->publicUrl()) {
            $brideSlides[] = [
                'imgSrc' => $bride->photo->publicUrl(),
                'imgAlt' => $bride?->name ?? 'Bride',
            ];
        }
        $groomSlides = [];
        if ($groom?->photo?->publicUrl()) {
            $groomSlides[] = [
                'imgSrc' => $groom->photo->publicUrl(),
                'imgAlt' => $groom?->name ?? 'Groom',
            ];
        }
    @endphp

    <style>
        body {
            font-family: 'Cormorant Garamond', serif;
        }

        [x-cloak] {
            display: none;
        }

        .image-frame-overlay {
            position: absolute;
            inset: 0;
            background-image: url('{{ asset('images/frame.png') }}');
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
            pointer-events: none;
        }

        .detail-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border-radius: 12px;
            padding: 12px 14px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
        }

        .bg-flip-y {
            position: relative;
        }

        .bg-flip-y::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('{{ $heroImage }}');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            transform: scaleY(-1);
            z-index: 0;
        }

        .left-nav {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border-radius: 9999px;
            padding: 10px 8px;
            box-shadow: 0 12px 30px rgba(90, 60, 40, 0.18);
        }

        .left-nav button {
            height: 36px;
            width: 36px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #8b6f5a;
            background: rgba(255, 255, 255, 0.8);
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
            position: relative;
        }

        .left-nav button:hover {
            background: #f3e7dc;
            color: #5a3c2a;
            transform: translateY(-1px);
        }

        .left-nav button.active {
            background: #7a5a45;
            color: #fff;
        }

        .left-nav .nav-label {
            position: absolute;
            right: 44px;
            top: 50%;
            transform: translateY(-50%);
            background: #7a5a45;
            color: #fff;
            font-size: 11px;
            line-height: 1;
            padding: 6px 10px;
            border-radius: 9999px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
            white-space: nowrap;
        }

        .left-nav button.active .nav-label {
            opacity: 1;
            transform: translateY(-50%) translateX(-4px);
        }

        @media (max-width: 767px) {
            .left-nav {
                display: none;
            }
        }

        .mobile-envelope-bg {
            background: rgba(20, 14, 12, 0.1);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .envelope {
            width: 100%;
            max-width: 340px;
            background: rgba(245, 242, 238, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 18px;
            box-shadow: 0 22px 50px rgba(20, 12, 10, 0.35);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            overflow: hidden;
        }


        .envelope-body {
            padding: 18px 20px 22px;
            text-align: center;
            color: #7a5a45;
        }

        .right-column {
            scroll-behavior: smooth;
        }
    </style>
</head>

<body x-data="{ invitationOpen: false, openInvitation() { this.invitationOpen = true; window.dispatchEvent(new Event('play-music')); } }">
    <div x-cloak x-show="!invitationOpen" class="mobile-envelope-bg fixed inset-0 z-50 flex items-center justify-center px-6">
        <div class="envelope">
            <div class="envelope-body">
                <p class="text-sm font-semibold">You are invited</p>
                <p class="mt-1 text-xs text-[#a18a78]">Tap to open the invitation</p>
                <button type="button"
                    class="mt-4 inline-flex items-center justify-center rounded-full bg-[#7a5a45] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#5a3c2a]"
                    x-on:click="openInvitation()">
                    Open Invitation
                </button>
            </div>
        </div>
    </div>
    <div x-transition.opacity.duration.400ms
        x-bind:class="{ 'blur-md pointer-events-none select-none': !invitationOpen }"
        class="grid grid-cols-1 lg:h-screen lg:grid-cols-12 lg:overflow-hidden">
        <!-- hero-carousel 5/12 -->
        <section class="left-column relative lg:col-span-8 lg:h-screen lg:overflow-y-auto" x-data="{ musicOn: false, toggleMusic() { const audio = this.$refs.bgAudio; if (!audio) return; this.musicOn = !this.musicOn; this.musicOn ? audio.play() : audio.pause(); } }" @if($hasMusic) x-on:play-music.window="const audio = $refs?.bgAudio; if (!audio) return; audio.play(); musicOn = true;" @endif>
            @if ($hasMusic)
                <audio x-ref="bgAudio" data-bg-audio @if($invitation->music?->autoplay) autoplay @endif @if($invitation->music?->loop_audio) loop @endif>
                    <source src="{{ $invitation->music->audio->publicUrl() }}" type="audio/mpeg">
                </audio>
                <button type="button"
                    class="absolute right-5 top-5 z-30 inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 text-xs font-semibold text-[#7a5a45] shadow-md transition hover:bg-white"
                    x-on:click="toggleMusic()">
                    <span x-text="musicOn ? 'Music On' : 'Music Off'"></span>
                </button>
            @endif
            <div x-data="{            
                            // Sets the time between each slides in milliseconds
                            autoplayIntervalTime: 4000,
                            slides: @js($heroSlides),            
                            currentSlideIndex: 1,
                            isPaused: false,
                            autoplayInterval: null,
                            previous() {                
                                if (this.currentSlideIndex > 1) {                    
                                    this.currentSlideIndex = this.currentSlideIndex - 1                
                                } else {   
                                    // If it's the first slide, go to the last slide           
                                    this.currentSlideIndex = this.slides.length                
                                }            
                            },            
                            next() {                
                                if (this.currentSlideIndex < this.slides.length) {                    
                                    this.currentSlideIndex = this.currentSlideIndex + 1                
                                } else {                 
                                    // If it's the last slide, go to the first slide    
                                    this.currentSlideIndex = 1                
                                }            
                            },    
                            autoplay() {
                                this.autoplayInterval = setInterval(() => {
                                    if (! this.isPaused) {
                                        this.next()
                                    }
                                }, this.autoplayIntervalTime)
                            },
                            // Updates interval time   
                            setAutoplayInterval(newIntervalTime) {
                                clearInterval(this.autoplayInterval)
                                this.autoplayIntervalTime = newIntervalTime
                                this.autoplay()
                            },    
                        }" x-init="autoplay" class="relative w-full overflow-hidden">

                <!-- slides -->
                <!-- Change min-h-[50svh] to your preferred height size -->
                <div class="relative min-h-[100svh] w-full">
                    <template x-for="(slide, index) in slides">
                        <div x-cloak x-show="currentSlideIndex == index + 1" class="absolute inset-0"
                            x-transition.opacity.duration.1000ms>

                            <!-- Title and description -->
                            <div
                                class="lg:px-32 lg:py-14 absolute inset-0 z-10 flex flex-col items-center justify-end gap-2 bg-linear-to-t from-surface-dark/85 to-transparent px-16 py-12 text-center">
                                <h3 class="w-full lg:w-[80%] text-balance text-2xl lg:text-3xl font-bold text-on-surface-dark-strong"
                                    x-text="slide.title"
                                    x-bind:aria-describedby="'slide' + (index + 1) + 'Description'"></h3>
                                <p class="lg:w-1/2 w-full text-pretty text-sm text-on-surface-dark"
                                    x-text="slide.description" x-bind:id="'slide' + (index + 1) + 'Description'"></p>
                            </div>

                            <img class="absolute w-full h-full inset-0 object-cover text-on-surface dark:text-on-surface-dark"
                                x-bind:src="slide.imgSrc" x-bind:alt="slide.imgAlt" />
                        </div>
                    </template>
                </div>

                <!-- Pause/Play Button -->
                <button type="button"
                    class="absolute bottom-5 right-5 z-20 rounded-full text-on-surface-dark opacity-50 transition hover:opacity-80 focus-visible:opacity-80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-dark active:outline-offset-0"
                    aria-label="pause carousel"
                    x-on:click="(isPaused = !isPaused), setAutoplayInterval(autoplayIntervalTime)"
                    x-bind:aria-pressed="isPaused">
                    <svg x-cloak x-show="isPaused" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true" class="size-7">
                        <path fill-rule="evenodd"
                            d="M2 10a8 8 0 1 1 16 0 8 8 0 0 1-16 0Zm6.39-2.908a.75.75 0 0 1 .766.027l3.5 2.25a.75.75 0 0 1 0 1.262l-3.5 2.25A.75.75 0 0 1 8 12.25v-4.5a.75.75 0 0 1 .39-.658Z"
                            clip-rule="evenodd">
                    </svg>
                    <svg x-cloak x-show="!isPaused" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true" class="size-7">
                        <path fill-rule="evenodd"
                            d="M2 10a8 8 0 1 1 16 0 8 8 0 0 1-16 0Zm5-2.25A.75.75 0 0 1 7.75 7h.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-.5a.75.75 0 0 1-.75-.75v-4.5Zm4 0a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-.5a.75.75 0 0 1-.75-.75v-4.5Z"
                            clip-rule="evenodd">
                    </svg>
                </button>

                <!-- indicators -->
                <div class="absolute rounded-radius bottom-3 md:bottom-5 left-1/2 z-20 flex -translate-x-1/2 gap-4 md:gap-3 px-1.5 py-1 md:px-2"
                    role="group" aria-label="slides">
                    <template x-for="(slide, index) in slides">
                        <button class="size-2 rounded-full transition"
                            x-on:click="(currentSlideIndex = index + 1), setAutoplayInterval(autoplayIntervalTime)"
                            x-bind:class="[currentSlideIndex === index + 1 ? 'bg-on-surface-dark' : 'bg-on-surface-dark/50']"
                            x-bind:aria-label="'slide ' + (index + 1)"></button>
                    </template>
                </div>
            </div>
            <nav class="left-nav absolute right-4 top-1/2 z-30 flex -translate-y-1/2 flex-col gap-3">
                <button type="button" data-scroll-target="section-couple" data-label="Couple" aria-label="Couple">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M12 21.35 10.55 20.03C5.4 15.36 2 12.28 2 8.5 2 6 4 4 6.5 4c1.74 0 3.41 1 4.22 2.5C11.09 5 12.76 4 14.5 4 17 4 19 6 19 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                    </svg>
                    <span class="nav-label">Couple</span>
                </button>
                <button type="button" data-scroll-target="section-details" data-label="Details" aria-label="Details">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-4 0-8 2-8 6v2h16v-2c0-4-4-6-8-6z" />
                    </svg>
                    <span class="nav-label">Details</span>
                </button>
                <button type="button" data-scroll-target="section-gallery" data-label="Gallery" aria-label="Gallery">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm2 3a2 2 0 1 0 2 2 2 2 0 0 0-2-2zm2 8h12l-4-6-3 4-2-3-3 5z" />
                    </svg>
                    <span class="nav-label">Gallery</span>
                </button>
                <button type="button" data-scroll-target="section-event" data-label="Event" aria-label="Event">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M7 2h2v3H7V2zm8 0h2v3h-2V2zM5 6h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zm0 6h14v8H5v-8z" />
                    </svg>
                    <span class="nav-label">Event</span>
                </button>
                <button type="button" data-scroll-target="section-maps" data-label="Maps" aria-label="Maps">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 14.5 9 2.5 2.5 0 0 1 12 11.5z" />
                    </svg>
                    <span class="nav-label">Maps</span>
                </button>
                <button type="button" data-scroll-target="section-rsvp" data-label="RSVP" aria-label="RSVP">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M9 16.17 4.83 12 3.41 13.41 9 19l12-12-1.41-1.41z" />
                    </svg>
                    <span class="nav-label">RSVP</span>
                </button>
                <button type="button" data-scroll-target="section-gift" data-label="Gift" aria-label="Gift">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M20 7h-3.18A3 3 0 0 0 12 5a3 3 0 0 0-4.82 2H4v4h16V7zm-9 0a1 1 0 0 1-1-1 1 1 0 0 1 2 0 1 1 0 0 1-1 1zm6-1a1 1 0 0 1-2 0 1 1 0 0 1 2 0zM4 13h7v8H6a2 2 0 0 1-2-2v-6zm9 0h7v6a2 2 0 0 1-2 2h-5v-8z" />
                    </svg>
                    <span class="nav-label">Gift</span>
                </button>
                <button type="button" data-scroll-target="section-wish" data-label="Wish" aria-label="Wish">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M20 2H4a2 2 0 0 0-2 2v14l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z" />
                    </svg>
                    <span class="nav-label">Wish</span>
                </button>
            </nav>
        </section>

        <!-- side section 5/12 -->
        <section class="right-column lg:col-span-4 lg:h-screen lg:overflow-y-auto" style="background-color : #f0e9e6">
            @if(session('success'))
                <div class="mx-6 mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mx-6 mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                    <ul class="list-disc pl-4">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <!-- Main Couple Section -->
            <div id="section-couple" class="relative min-h-[100svh] bg-center bg-cover"
                style="background-image: url('{{ $heroImage }}');">
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
                <div class="flex min-h-[100svh] items-start justify-center px-8 pb-12 pt-15">
                    <div class="w-full max-w-xs space-y-5 text-center">
                        <div class="text-amber-800">
                            <h1 class="mt-1 text-2xl font-medium">
                                <span>{{ $invitation->couple?->couple_tagline ?? 'The Wedding of' }}</span>
                            </h1>
                            <h2 class="text-4xl font-semibold">
                                <span>{{ $invitation->couple?->couple_name_1 ?? 'Nama Mempelai' }}</span>
                                <span class="mx-1">&amp;</span>
                                <span>{{ $invitation->couple?->couple_name_2 ?? 'Nama Mempelai' }}</span>
                            </h2>
                            <h4 class="text-xl font-medium">
                                <span>{{ $invitation->couple?->wedding_date_display ?? '' }}</span>
                            </h4>
                        </div>
                        <div class="relative overflow-hidden rounded-2xl bg-white/70 shadow-md">
                            <div class="relative aspect-[3/4] w-full">
                                <img class="absolute inset-0 h-full w-full object-cover"
                                    src="https://api.our-wedding.link/uploads/6a6ba320-f882-11ee-b17f-470510f0efd9.jpg"
                                    alt="Main couple frame" />
                            </div>
                        </div>
                        <a class="inline-flex items-center justify-center rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                            href="#quotes-section">Next section</a>
                    </div>
                </div>
            </div>
            <!-- Section Seperator -->
            <div class="relative min-h-[10svh] bg-center bg-cover"
                style="background-image: url('https://cdn-uploads.owlink.id/contenful/asdsa.png');">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-6 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-6 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
            </div>
            <!-- Quotes Section -->
            <div id="quotes-section" class="min-h-[30svh] px-8 py-12 flex items-center justify-center">
                <div class="w-full max-w-xl space-y-6 text-center">
                    <blockquote class="text-3xl font-semibold text-slate-800">"We are most alive when we love."
                    </blockquote>
                </div>
            </div>
            <!-- Section Seperator -->
            <div class="relative min-h-[10svh] bg-center bg-cover"
                style="background-image: url('https://cdn-uploads.owlink.id/contenful/asdsa.png');">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-6 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-6 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
            </div>
            <!-- Couple Detail Section -->
            <div id="section-details" class="relative min-h-[100svh] bg-center bg-cover px-8 py-12 flex items-center justify-center"
                style="background-image: url('{{ $heroImage }}');">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
                <div class="pointer-events-none absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                <div class="relative z-10 w-full max-w-xl space-y-6 text-center">
                    <div class="grid grid-cols-1 gap-10 sm:grid-cols-2">
                        <div class="space-y-3 text-center">
                            <div x-data="{
                                autoplayIntervalTime: 3500,
                                slides: @js($brideSlides),
                                currentSlideIndex: 1,
                                isPaused: false,
                                autoplayInterval: null,
                                next() {
                                    if (this.slides.length === 0) {
                                        return
                                    }
                                    if (this.currentSlideIndex < this.slides.length) {
                                        this.currentSlideIndex = this.currentSlideIndex + 1
                                    } else {
                                        this.currentSlideIndex = 1
                                    }
                                },
                                autoplay() {
                                    this.autoplayInterval = setInterval(() => {
                                        if (! this.isPaused) {
                                            this.next()
                                        }
                                    }, this.autoplayIntervalTime)
                                },
                            }" x-init="autoplay" class="relative overflow-hidden rounded-2xl">
                                <div class="relative aspect-[3/4] w-full p-12">
                                    <template x-for="(slide, index) in slides">
                                        <div x-cloak x-show="currentSlideIndex == index + 1" class="absolute inset-13"
                                            x-transition.opacity.duration.700ms>
                                            <img class="absolute inset-0 z-0 h-full w-full object-cover rounded-md"
                                                x-bind:src="slide.imgSrc" x-bind:alt="slide.imgAlt" />
                                        </div>
                                    </template>
                                    <div class="image-frame-overlay z-10" aria-hidden="true"></div>
                                </div>
                            </div>
                            <div class="text-slate-900">
                                <h3 class="text-2xl font-extrabold">
                                    <span>{{ $bride?->name ?? 'Nama Mempelai' }}</span>
                                </h3>
                                <p class="mt-1 text-lg text-slate-700">
                                    <span>{{ $bride?->title ?? 'Putri dari' }}</span>
                                </p>
                                <p class="text-lg text-slate-700">
                                    <span>{{ $bride?->father_name ?? '' }}</span>
                                    @if($bride?->father_name || $bride?->mother_name)
                                        <span class="mx-1">&amp;</span>
                                    @endif
                                    <span>{{ $bride?->mother_name ?? '' }}</span>
                                </p>
                                @if ($bride?->instagram_handle)
                                    <p class="mt-2 text-lg text-slate-700">
                                        <a class="underline underline-offset-4 hover:text-slate-900"
                                            href="https://instagram.com/{{ ltrim($bride->instagram_handle, '@') }}">
                                            {{ $bride->instagram_handle }}
                                        </a>
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="space-y-3 text-center">
                            <div x-data="{
                                autoplayIntervalTime: 3200,
                                slides: @js($groomSlides),
                                currentSlideIndex: 1,
                                isPaused: false,
                                autoplayInterval: null,
                                next() {
                                    if (this.slides.length === 0) {
                                        return
                                    }
                                    if (this.currentSlideIndex < this.slides.length) {
                                        this.currentSlideIndex = this.currentSlideIndex + 1
                                    } else {
                                        this.currentSlideIndex = 1
                                    }
                                },
                                autoplay() {
                                    this.autoplayInterval = setInterval(() => {
                                        if (! this.isPaused) {
                                            this.next()
                                        }
                                    }, this.autoplayIntervalTime)
                                },
                            }" x-init="autoplay" class="relative overflow-hidden rounded-2xl">
                                <div class="relative aspect-[3/4] w-full p-12">
                                    <template x-for="(slide, index) in slides">
                                        <div x-cloak x-show="currentSlideIndex == index + 1" class="absolute inset-13"
                                            x-transition.opacity.duration.700ms>
                                            <img class="absolute inset-0 z-0 h-full w-full object-cover rounded-md"
                                                x-bind:src="slide.imgSrc" x-bind:alt="slide.imgAlt" />
                                        </div>
                                    </template>
                                    <div class="image-frame-overlay z-10" aria-hidden="true"></div>
                                </div>
                            </div>
                            <div class="text-slate-900">
                                <h3 class="text-2xl font-extrabold">
                                    <span>{{ $groom?->name ?? 'Nama Mempelai' }}</span>
                                </h3>
                                <p class="mt-1 text-lg font-extrabold text-slate-700">
                                    <span>{{ $groom?->title ?? 'Putra dari' }}</span>
                                </p>
                                <p class="text-lg text-slate-700">
                                    <span>{{ $groom?->father_name ?? '' }}</span>
                                    @if($groom?->father_name || $groom?->mother_name)
                                        <span class="mx-1">&amp;</span>
                                    @endif
                                    <span>{{ $groom?->mother_name ?? '' }}</span>
                                </p>
                                @if ($groom?->instagram_handle)
                                    <p class="mt-2 text-sm text-slate-700">
                                        <a class="underline underline-offset-4 hover:text-slate-900"
                                            href="https://instagram.com/{{ ltrim($groom->instagram_handle, '@') }}">
                                            {{ $groom->instagram_handle }}
                                        </a>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Section Seperator -->
            <div class="relative min-h-[10svh] bg-center bg-cover"
                style="background-image: url('https://cdn-uploads.owlink.id/contenful/asdsa.png');">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-6 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-6 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
            </div>
            <!-- Gallery Section -->
            <div id="section-gallery" class="min-h-[100svh] px-8 py-12 flex items-center justify-center">
                <div class="w-full max-w-md space-y-6 text-center">
                    <blockquote class="text-2xl font-semibold text-slate-800">"Momen Yang Berharga"</blockquote>
                    <blockquote class="text-xl font-semibold text-slate-800">"Menciptakan kenangan adalah hadiah yang
                        tak ternilai harganya. Kenangan akan bertahan seumur hidup; benda-benda hanya dalam waktu
                        singkat."</blockquote>
                    <div x-data="{            
                                    // Sets the time between each slides in milliseconds
                                    autoplayIntervalTime: 4000,
                                    slides: @js($gallerySlides),            
                                    currentSlideIndex: 1,
                                    isPaused: false,
                                    autoplayInterval: null,
                                    previous() {                
                                        if (this.slides.length === 0) {
                                            return
                                        }
                                        if (this.currentSlideIndex > 1) {                    
                                            this.currentSlideIndex = this.currentSlideIndex - 1                
                                        } else {   
                                            // If it's the first slide, go to the last slide           
                                            this.currentSlideIndex = this.slides.length                
                                        }            
                                    },            
                                    next() {                
                                        if (this.slides.length === 0) {
                                            return
                                        }
                                        if (this.currentSlideIndex < this.slides.length) {                    
                                            this.currentSlideIndex = this.currentSlideIndex + 1                
                                        } else {                 
                                            // If it's the last slide, go to the first slide    
                                            this.currentSlideIndex = 1                
                                        }            
                                    },    
                                    autoplay() {
                                        this.autoplayInterval = setInterval(() => {
                                            if (! this.isPaused) {
                                                this.next()
                                            }
                                        }, this.autoplayIntervalTime)
                                    },
                                    // Updates interval time   
                                    setAutoplayInterval(newIntervalTime) {
                                        clearInterval(this.autoplayInterval)
                                        this.autoplayIntervalTime = newIntervalTime
                                        this.autoplay()
                                    },    
                                }" x-init="autoplay" class="relative w-full overflow-hidden">

                        <!-- slides -->
                        <!-- Change min-h-[50svh] to your preferred height size -->
                        <div class="relative min-h-[50svh] w-full">
                            <template x-for="(slide, index) in slides">
                                <div x-cloak x-show="currentSlideIndex == index + 1" class="absolute inset-0"
                                    x-transition.opacity.duration.1000ms>

                                    <!-- Title and description -->
                                    <div
                                        class="lg:px-32 lg:py-14 absolute inset-0 z-10 flex flex-col items-center justify-end gap-2 bg-linear-to-t from-surface-dark/85 to-transparent px-16 py-12 text-center">
                                        <h3 class="w-full lg:w-[80%] text-balance text-2xl lg:text-3xl font-bold text-on-surface-dark-strong"
                                            x-text="slide.title"
                                            x-bind:aria-describedby="'slide' + (index + 1) + 'Description'"></h3>
                                        <p class="lg:w-1/2 w-full text-pretty text-sm text-on-surface-dark"
                                            x-text="slide.description"
                                            x-bind:id="'slide' + (index + 1) + 'Description'"></p>
                                    </div>

                                    <img class="absolute w-full h-full inset-0 object-cover text-on-surface dark:text-on-surface-dark"
                                        x-bind:src="slide.imgSrc" x-bind:alt="slide.imgAlt" />
                                </div>
                            </template>
                        </div>

                        <!-- indicators -->
                        <div class="absolute rounded-radius bottom-3 md:bottom-5 left-1/2 z-20 flex -translate-x-1/2 gap-4 md:gap-3 px-1.5 py-1 md:px-2"
                            role="group" aria-label="slides">
                            <template x-for="(slide, index) in slides">
                                <button class="size-2 rounded-full transition"
                                    x-on:click="(currentSlideIndex = index + 1), setAutoplayInterval(autoplayIntervalTime)"
                                    x-bind:class="[currentSlideIndex === index + 1 ? 'bg-on-surface-dark' : 'bg-on-surface-dark/50']"
                                    x-bind:aria-label="'slide ' + (index + 1)"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Section Seperator -->
            <div class="relative min-h-[10svh] bg-center bg-cover"
                style="background-image: url('https://cdn-uploads.owlink.id/contenful/asdsa.png');">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-6 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-6 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
            </div>
            <!-- Event Section -->
            <div id="section-event" class="min-h-[100svh] bg-center bg-cover px-8 py-12 flex items-center justify-center"
                style="background-image: url('{{ $heroImage }}');">
                <div class="w-full max-w-md text-center">
                    <div
                        class="rounded-[28px] border border-white/60 bg-white/50 px-6 py-10 text-amber-900 shadow-[0_12px_30px_rgba(160,120,90,0.2)] backdrop-blur-md">
                        <h2 class="text-2xl font-semibold">{{ $eventSectionTitle }}</h2>
                        <div class="mx-auto mt-3 h-px w-24 bg-[#cdb9a6]"></div>

                        @forelse ($invitation->events as $event)
                            <div class="mt-6 space-y-3">
                                <h3 class="text-xl font-semibold">{{ $event->title ?? 'Event' }}</h3>
                                @if ($event->event_date_display)
                                    <p class="text-xs uppercase tracking-[0.3em] text-amber-800">
                                        {{ $event->event_date_display }}
                                    </p>
                                @endif
                                @if ($event->event_time_display)
                                    <p class="text-sm text-amber-800">{{ $event->event_time_display }}</p>
                                @endif
                                @if ($event->location_text)
                                    <p class="text-sm text-amber-800">{{ $event->location_text }}</p>
                                @endif
                                @php
                                    $eventLocationUrl = $event->location_url ?: $invitation->eventSection?->default_location_url;
                                @endphp
                                @if ($eventLocationUrl)
                                    <a class="inline-flex items-center justify-center rounded-full border border-[#cdb9a6] px-5 py-2 text-sm font-semibold text-amber-900 transition hover:bg-[#efe4d7]"
                                        href="{{ $eventLocationUrl }}" target="_blank" rel="noopener">
                                        Lihat lokasi
                                    </a>
                                @endif
                            </div>

                            @if (! $loop->last)
                                <div class="mx-auto my-6 h-px w-32 bg-[#cdb9a6]"></div>
                            @endif
                        @empty
                            <p class="mt-6 text-sm text-amber-800">Event details coming soon.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <!-- Section Seperator -->
            <div class="relative min-h-[10svh] bg-center bg-cover"
                style="background-image: url('https://cdn-uploads.owlink.id/contenful/asdsa.png');">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-6 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-6 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
            </div>
            <!-- Maps Section -->
            <div id="section-maps" class="relative min-h-[100svh] bg-center bg-cover px-8 py-12 flex items-center justify-center"
                style="background-image: url('{{ $heroImage }}');">
                <div class="pointer-events-none absolute inset-0 bg-white/30 backdrop-blur-sm"></div>
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
                <div class="relative z-10 w-full max-w-xl text-center text-amber-900">
                    <h2 class="text-2xl font-semibold">{{ $invitation->map?->map_section_title ?? 'Maps' }}</h2>
                    @if ($invitation->map?->map_embed_src)
                        <div
                            class="mt-6 overflow-hidden rounded-[24px] border border-[#d6c6b8] bg-white/60 shadow-[0_12px_30px_rgba(160,120,90,0.2)] backdrop-blur-md">
                            <iframe
                                src="{{ $invitation->map->map_embed_src }}"
                                class="h-[32rem] w-full" style="border: 0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    @endif
                    @if ($invitation->map?->map_address)
                        <p class="mt-6 text-sm text-amber-800">
                            {{ $invitation->map->map_address }}
                        </p>
                    @endif
                    @if ($invitation->map?->map_location_url)
                        <a class="mt-4 inline-flex items-center justify-center rounded-full border border-[#cdb9a6] px-5 py-2 text-sm font-semibold text-amber-900 transition hover:bg-[#efe4d7]"
                            href="{{ $invitation->map->map_location_url }}" target="_blank" rel="noopener">Lihat lokasi</a>
                    @endif
                </div>
            </div>
            <!-- RSVP Section -->
            <div id="section-rsvp" x-data="{ open: false }"
                class="relative min-h-[100svh] bg-center bg-cover px-8 py-12 flex items-center justify-center"
                style="background-image: url('{{ $heroImage }}');">
                <div class="pointer-events-none absolute inset-0 bg-white/30 backdrop-blur-sm"></div>
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
                <div class="relative z-10 w-full max-w-xl text-center text-amber-900">
                    <div
                        class="rounded-[32px] border border-[#d6c6b8] bg-[#f7f0ea] px-6 py-10 shadow-[0_12px_30px_rgba(160,120,90,0.2)]">
                        <h2 class="text-2xl font-semibold">{{ $invitation->rsvp?->rsvp_title ?? 'RSVP' }}</h2>
                        @if ($invitation->rsvp?->rsvp_subtitle)
                            <p class="mt-2 text-sm text-amber-800">
                                {{ $invitation->rsvp->rsvp_subtitle }}
                            </p>
                        @endif
                        <div class="mx-auto mt-4 h-px w-32 bg-[#cdb9a6]"></div>
                        <button type="button"
                            class="mt-6 inline-flex items-center justify-center rounded-md border border-[#cdb9a6] bg-white/70 px-5 py-3 text-sm font-semibold text-amber-900 transition hover:bg-white"
                            x-on:click="open = true">
                            Konfirmasi kehadiran
                        </button>
                        @if ($invitation->rsvp?->rsvp_message)
                            <p class="mt-6 text-sm text-amber-800">
                                {{ $invitation->rsvp->rsvp_message }}
                            </p>
                        @endif
                        @if ($invitation->rsvp?->rsvp_hosts)
                            <div class="mt-6 space-y-2 text-sm text-amber-900">
                                {!! nl2br(e($invitation->rsvp->rsvp_hosts)) !!}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- RSVP Modal -->
                <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center px-6"
                    x-transition.opacity>
                    <div class="absolute inset-0 bg-black/40" x-on:click="open = false"></div>
                    <div class="relative w-full max-w-sm rounded-2xl border border-white/60 bg-white/70 p-6 shadow-[0_20px_50px_rgba(0,0,0,0.25)] backdrop-blur-md"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-slate-900">Konfirmasi kehadiran</h3>
                            <button type="button" class="text-slate-500 hover:text-slate-700" x-on:click="open = false">
                                &times;
                            </button>
                        </div>
                        <form class="mt-4 space-y-4 text-left" method="POST"
                            action="{{ route('invitation.rsvp.store', $invitation->slug) }}">
                            @csrf
                            <input type="text" name="guest_name" placeholder="Nama lengkap" required
                                class="w-full rounded-md border border-slate-300 bg-white/80 px-3 py-2 text-sm text-slate-900 focus:border-amber-400 focus:outline-none">
                            <input type="text" name="phone" placeholder="No. HP (opsional)"
                                class="w-full rounded-md border border-slate-300 bg-white/80 px-3 py-2 text-sm text-slate-900 focus:border-amber-400 focus:outline-none">
                            <div class="space-y-2 text-sm text-slate-700">
                                <p class="font-semibold">Konfirmasi kehadiran</p>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="attendance" value="yes" required
                                        class="h-4 w-4 border-slate-300 text-amber-600">
                                    Iya, saya akan datang
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="attendance" value="no" required
                                        class="h-4 w-4 border-slate-300 text-amber-600">
                                    Maaf, Sepertinya tidak bisa
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="attendance" value="maybe" required
                                        class="h-4 w-4 border-slate-300 text-amber-600">
                                    Mungkin
                                </label>
                            </div>
                            <input type="number" name="pax" min="1" max="10" value="1" required
                                class="w-full rounded-md border border-slate-300 bg-white/80 px-3 py-2 text-sm text-slate-900 focus:border-amber-400 focus:outline-none">
                            <textarea name="note" rows="2" placeholder="Catatan (opsional)"
                                class="w-full rounded-md border border-slate-300 bg-white/80 px-3 py-2 text-sm text-slate-900 focus:border-amber-400 focus:outline-none"></textarea>
                            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden">
                            <button type="submit"
                                class="w-full rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                                Kirim respon
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Section Seperator -->
            <div class="relative min-h-[10svh] bg-center bg-cover"
                style="background-image: url('https://cdn-uploads.owlink.id/contenful/asdsa.png');">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-6 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-6 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
            </div>
            <!-- Gift Section -->
            <div id="section-gift" class="bg-flip-y min-h-[100svh] px-8 py-12 flex items-center justify-center">
                <div class="pointer-events-none absolute inset-0 bg-white/30 backdrop-blur-sm"></div>
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
                <div class="relative z-10 w-full max-w-xl text-center text-amber-900">
                    <div
                        class="rounded-[28px] border border-[#cdb9a6] bg-[#f7f0ea] px-6 py-10 shadow-[0_12px_30px_rgba(160,120,90,0.2)]">
                        <h2 class="text-2xl font-semibold">{{ $invitation->giftSection?->gift_title ?? 'Kirim Hadiah' }}</h2>
                        <div class="mx-auto mt-3 h-px w-24 bg-[#cdb9a6]"></div>
                        @if ($invitation->giftSection?->gift_subtitle)
                            <p class="mt-4 text-sm text-amber-800">
                                {{ $invitation->giftSection->gift_subtitle }}
                            </p>
                        @endif

                        <div class="mt-6 space-y-4 text-left">
                            @forelse ($invitation->giftAccounts as $account)
                                <div class="rounded-2xl border border-[#d6c6b8] bg-white/60 px-5 py-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-base font-semibold">{{ $account->bank_name ?? 'Bank' }}</h3>
                                        @if ($account->account_number)
                                            <button type="button"
                                                class="text-xs font-semibold text-amber-900 underline underline-offset-4"
                                                data-copy value="{{ $account->account_number }}">Copy</button>
                                        @endif
                                    </div>
                                    @if ($account->account_number)
                                        <p class="mt-2 text-sm font-semibold">{{ $account->account_number }}</p>
                                    @endif
                                    @if ($account->account_holder)
                                        <p class="text-sm text-amber-800">{{ $account->account_holder }}</p>
                                    @endif
                                    @if ($account->qr?->publicUrl())
                                        <div class="mt-4 overflow-hidden rounded-xl border border-[#d6c6b8]">
                                            <img src="{{ $account->qr->publicUrl() }}"
                                                alt="{{ $account->bank_name ?? 'QR' }}" class="w-full">
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-amber-800">Gift details coming soon.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <!-- Wish Section -->
            <div id="section-wish" class="relative min-h-[100svh] bg-center bg-cover px-8 py-12 flex items-center justify-center"
                style="background-image: url('{{ $heroImage }}');">
                <div class="pointer-events-none absolute inset-0 bg-black/30 backdrop-blur-sm"></div>
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
                <div class="relative z-10 w-full max-w-xl text-white">
                    <h2 class="text-center text-2xl font-semibold">
                        {{ $invitation->wishSection?->wish_title ?? 'Ucapan & Doa' }}
                    </h2>

                    <div class="mt-6 space-y-4">
                        @forelse ($wishItems as $wish)
                            @php
                                $name = $wish->name ?? $wish->guest_name ?? 'Guest';
                                $initials = collect(explode(' ', $name))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                    ->implode('');
                                $address = $wish->address ?? $wish->guest_address;
                                $message = $wish->message;
                            @endphp
                            <div class="flex gap-4">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full border border-white/50 text-xs font-semibold">
                                    {{ $initials ?: 'GG' }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">{{ $name }}</p>
                                    @if ($address)
                                        <p class="text-xs text-white/80">{{ $address }}</p>
                                    @endif
                                    @if ($message)
                                        <p class="mt-1 text-xs text-white/90">{{ $message }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-white/80">Ucapan akan segera hadir.</p>
                        @endforelse
                    </div>

                    <div class="mt-8 rounded-2xl border border-white/40 bg-white/15 p-5">
                        <p class="text-sm font-semibold">Kirim ucapan:</p>
                        <form class="mt-4 space-y-3" method="POST"
                            action="{{ route('invitation.guestbook.store', $invitation->slug) }}">
                            @csrf
                            <input type="text" name="guest_name" placeholder="Nama lengkap" required
                                class="w-full rounded-md border border-white/30 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/70 focus:border-white/60 focus:outline-none">
                            <input type="text" name="guest_address" placeholder="Alamat (opsional)"
                                class="w-full rounded-md border border-white/30 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/70 focus:border-white/60 focus:outline-none">
                            <textarea name="message" rows="3" placeholder="contoh: Selamat untuk acaranya" required
                                class="w-full rounded-md border border-white/30 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/70 focus:border-white/60 focus:outline-none"></textarea>
                            <select name="attendance"
                                class="w-full rounded-md border border-white/30 bg-white/10 px-3 py-2 text-sm text-white focus:border-white/60 focus:outline-none">
                                <option value="" class="text-slate-900">Kehadiran (opsional)</option>
                                <option value="yes" class="text-slate-900">Hadir</option>
                                <option value="no" class="text-slate-900">Tidak hadir</option>
                                <option value="maybe" class="text-slate-900">Mungkin</option>
                            </select>
                            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden">
                            <button type="submit"
                                class="w-full rounded-full border border-white/60 bg-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/30">
                                Kirim sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Section Seperator -->
            <div class="relative min-h-[10svh] bg-center bg-cover"
                style="background-image: url('https://cdn-uploads.owlink.id/contenful/asdsa.png');">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-6 bg-gradient-to-b from-[#f0e9e6] to-transparent">
                </div>
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-6 bg-gradient-to-t from-[#f0e9e6] to-transparent">
                </div>
            </div>
            <footer class="px-8 py-6 text-center text-xs text-slate-600">
                created with lavanyaenterprise.id
            </footer>
        </section>
    </div>

    <!-- Alpine Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>


    <!-- Alpine Core -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.3/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('click', (event) => {
            const target = event.target.closest('[data-copy]');
            if (!target) return;
            const value = target.getAttribute('value');
            if (!value) return;
            navigator.clipboard.writeText(value).then(() => {
                const original = target.textContent;
                target.textContent = 'Copied';
                setTimeout(() => {
                    target.textContent = original;
                }, 1200);
            });
        });
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-scroll-target]');
            if (!button) return;
            const targetId = button.getAttribute('data-scroll-target');
            if (!targetId) return;
            const rightColumn = document.querySelector('.right-column');
            if (!rightColumn) return;
            const target = rightColumn.querySelector(`#${targetId}`);
            if (!target) return;
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        document.addEventListener('DOMContentLoaded', () => {
            const rightColumn = document.querySelector('.right-column');
            const navButtons = Array.from(document.querySelectorAll('[data-scroll-target]'));
            if (!rightColumn || navButtons.length === 0) return;

            const sections = navButtons
                .map((button) => rightColumn.querySelector(`#${button.getAttribute('data-scroll-target')}`))
                .filter(Boolean);

            const setActive = (id) => {
                navButtons.forEach((button) => {
                    const isActive = button.getAttribute('data-scroll-target') === id;
                    button.classList.toggle('active', isActive);
                });
            };

            const observer = new IntersectionObserver(
                (entries) => {
                    const visible = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                    if (!visible) return;
                    setActive(visible.target.id);
                },
                { root: rightColumn, threshold: [0.4, 0.6, 0.8] }
            );

            sections.forEach((section) => observer.observe(section));
            setActive(sections[0].id);
        });
    </script>

</body>

</html>
