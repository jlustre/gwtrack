<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>GWTrack.com | Your Greatway to Growth Leadership Platform</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#050505] font-sans text-white antialiased" x-data="{ mobileMenuOpen: false, scrolled: false, heroIndex: 0, heroCount: 6 }" x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 50 }); setInterval(() => { heroIndex = (heroIndex + 1) % heroCount }, 12000)">
        @include('layouts.partials.page-chrome')
        @php
            $loginUrl = Route::has('login') ? route('login') : '#';
            $dashboardUrl = Route::has('dashboard') ? route('dashboard') : $loginUrl;
            $primaryUrl = auth()->check() ? $dashboardUrl : $loginUrl;
            $primaryLabel = auth()->check() ? 'Open Dashboard' : 'Member Login';
            $heroSlideImage = static function (int $number): string {
                $jpg = "images/landing/slide{$number}.jpg";
                $svg = "images/landing/slide{$number}.svg";

                return file_exists(public_path($jpg)) ? $jpg : $svg;
            };
            $heroSlides = [
                [
                    'image' => $heroSlideImage(1),
                    'eyebrow' => 'Leadership Starts With Clarity',
                    'headline' => 'Develop Teams With Executive Precision.',
                    'body' => 'Bring sponsors, mentors, and new associates into one premium growth system built for Your Greatway to Growth.',
                    'align' => 'items-center justify-start text-left',
                    'panel' => 'max-w-2xl md:ml-0',
                    'object' => 'object-center',
                ],
                [
                    'image' => $heroSlideImage(2),
                    'eyebrow' => 'Mentorship With Standards',
                    'headline' => 'Coach Every Associate With Confidence.',
                    'body' => 'Track sponsor relationships, mentor conversations, apprenticeship progress, and next steps without losing momentum.',
                    'align' => 'items-center justify-start text-left',
                    'panel' => 'max-w-xl md:ml-0',
                    'object' => 'object-center',
                ],
                [
                    'image' => $heroSlideImage(3),
                    'eyebrow' => 'The Rank Path Is Visible',
                    'headline' => 'Turn Advancement Into A Guided Journey.',
                    'body' => 'From Training Associate to National Executive Vice Chairman, GWTrack makes milestones, FYO compensation, training, and leadership readiness clear.',
                    'align' => 'items-center justify-start text-left',
                    'panel' => 'max-w-xl md:ml-0',
                    'object' => 'object-center',
                ],
                [
                    'image' => $heroSlideImage(4),
                    'eyebrow' => 'Command Center For Growth',
                    'headline' => 'See The Team. Know The Next Move.',
                    'body' => 'A private dashboard for onboarding, licensing, rank progress, events, announcements, and team visibility.',
                    'align' => 'items-center justify-start text-left',
                    'panel' => 'max-w-xl md:ml-0',
                    'object' => 'object-center',
                ],
                [
                    'image' => $heroSlideImage(5),
                    'eyebrow' => 'Recognition Builds Culture',
                    'headline' => 'Celebrate Progress And Build Legacy.',
                    'body' => 'Highlight achievements, mentor wins, certification movement, and leadership milestones across your organization.',
                    'align' => 'items-center justify-start text-left',
                    'panel' => 'max-w-xl md:ml-0',
                    'object' => 'object-center',
                ],
                [
                    'image' => $heroSlideImage(6),
                    'eyebrow' => 'Your Greatway to Growth',
                    'headline' => 'Developing Leaders. Building Legacies.',
                    'body' => 'Unify training, resources, coaching, and performance tracking in one executive portal for serious builders.',
                    'align' => 'items-end justify-start text-left pb-24 md:pb-28',
                    'panel' => 'max-w-lg md:ml-0',
                    'object' => 'object-center',
                ],
            ];
        @endphp

        <style>
            [x-cloak] { display: none !important; }
            .gold-text-gradient {
                background: linear-gradient(135deg, #84BB41 0%, #f5d76e 50%, #74A539 100%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .gold-bg-gradient {
                background: linear-gradient(95deg, #74A539, #84BB41, #f5d76e);
            }
            .gold-border-glow {
                border: 1px solid rgba(132, 187, 65, 0.5);
                box-shadow: 0 0 12px rgba(132, 187, 65, 0.2);
            }
            .glass-card {
                background: rgba(17, 17, 17, 0.72);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(132, 187, 65, 0.2);
            }
            .card-hover {
                transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
            }
            .card-hover:hover {
                transform: translateY(-5px);
                border-color: rgba(132, 187, 65, 0.45);
                box-shadow: 0 20px 30px -18px rgba(132, 187, 65, 0.35);
            }
        </style>

        <nav class="fixed left-0 top-0 z-50 w-full transition-all duration-300" :class="scrolled ? 'border-b border-gw-accent/30 bg-black/95 shadow-2xl' : 'bg-black/45 backdrop-blur-sm'">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-1.5 md:px-8 md:py-2">
                <a href="#home" class="text-2xl font-extrabold leading-none tracking-normal md:text-3xl">
                    <span class="gold-text-gradient">GWTrack</span><span class="text-white">.com</span>
                    <span class="mt-0 block text-[0.6rem] font-bold uppercase leading-none tracking-[0.2em] text-gw-accent/80">Your Greatway to Growth</span>
                </a>

                <div class="hidden items-center gap-7 text-sm font-semibold md:flex">
                    <a href="#home" class="transition hover:text-gw-accent">Home</a>
                    <a href="#who-we-are" class="transition hover:text-gw-accent">Who We Are</a>
                    <a href="#features" class="transition hover:text-gw-accent">Features</a>
                    <a href="#leadership-path" class="transition hover:text-gw-accent">Leadership Path</a>
                    <a href="#testimonials" class="transition hover:text-gw-accent">Testimonials</a>
                    <a href="#contact" class="transition hover:text-gw-accent">Contact</a>
                    <a href="{{ $primaryUrl }}" class="rounded-full bg-gradient-to-r from-gw-accent-dark to-gw-accent px-5 py-1.5 font-bold text-black shadow-lg transition hover:scale-105">
                        {{ $primaryLabel }}
                    </a>
                </div>

                <button type="button" class="md:hidden" x-on:click="mobileMenuOpen = ! mobileMenuOpen">
                    <span class="sr-only">Toggle navigation</span>
                    <span x-show="! mobileMenuOpen" class="block space-y-1.5">
                        <span class="block h-0.5 w-6 bg-white"></span>
                        <span class="block h-0.5 w-6 bg-white"></span>
                        <span class="block h-0.5 w-6 bg-white"></span>
                    </span>
                    <span x-show="mobileMenuOpen" x-cloak class="text-2xl font-bold text-gw-accent">x</span>
                </button>
            </div>

            <div x-show="mobileMenuOpen" x-transition x-cloak class="border-t border-gw-accent/30 bg-black/95 px-5 py-5 backdrop-blur-md md:hidden">
                <div class="flex flex-col gap-4 text-base font-semibold">
                    <a href="#home" x-on:click="mobileMenuOpen = false" class="hover:text-gw-accent">Home</a>
                    <a href="#who-we-are" x-on:click="mobileMenuOpen = false" class="hover:text-gw-accent">Who We Are</a>
                    <a href="#features" x-on:click="mobileMenuOpen = false" class="hover:text-gw-accent">Features</a>
                    <a href="#leadership-path" x-on:click="mobileMenuOpen = false" class="hover:text-gw-accent">Leadership Path</a>
                    <a href="#testimonials" x-on:click="mobileMenuOpen = false" class="hover:text-gw-accent">Testimonials</a>
                    <a href="#contact" x-on:click="mobileMenuOpen = false" class="hover:text-gw-accent">Contact</a>
                    <a href="{{ $primaryUrl }}" class="rounded-full bg-gradient-to-r from-gw-accent-dark to-gw-accent px-5 py-3 text-center font-bold text-black">{{ $primaryLabel }}</a>
                </div>
            </div>
        </nav>

        <main id="home">
            <section class="relative flex min-h-screen items-center overflow-hidden pt-24">
                @foreach ($heroSlides as $index => $slide)
                    <div class="absolute inset-0 transition-opacity duration-700" x-show="heroIndex === {{ $index }}" @if ($index > 0) x-cloak @endif>
                        <img src="{{ asset($slide['image']) }}" alt="{{ $slide['headline'] }}" class="h-full w-full object-cover {{ $slide['object'] }} opacity-95" loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                        <div class="absolute inset-0 z-10 bg-gradient-to-r from-black/90 via-black/58 to-black/18"></div>
                        <div class="absolute inset-0 z-10 bg-gradient-to-t from-black/80 via-transparent to-black/30"></div>

                        <div class="absolute inset-0 z-20 flex {{ $slide['align'] }} px-6 pt-24 md:px-12">
                            <div class="mx-auto w-full max-w-7xl">
                                <div class="{{ $slide['panel'] }} rounded-3xl border border-gw-accent/20 bg-black/22 p-6 backdrop-blur-[2px] md:p-8">
                                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-gw-accent md:text-sm">{{ $slide['eyebrow'] }}</p>
                                    <h1 class="mt-4 text-4xl font-extrabold leading-tight md:text-6xl">
                                        <span class="gold-text-gradient">{{ $slide['headline'] }}</span>
                                    </h1>
                                    <p class="mt-5 max-w-2xl text-base leading-7 text-gray-200 md:text-xl md:leading-8">
                                        {{ $slide['body'] }}
                                    </p>
                                    <div class="mt-8 flex flex-wrap gap-4">
                                        <a href="{{ $primaryUrl }}" class="gold-bg-gradient rounded-full px-7 py-3 text-base font-extrabold text-black shadow-xl transition hover:scale-105">{{ $primaryLabel }}</a>
                                        <a href="#contact" class="rounded-full border border-gw-accent px-7 py-3 text-base font-semibold text-gw-accent transition hover:bg-gw-accent/10">Request Invitation</a>
                                    </div>
                                    @if ($index === 0)
                                        <div class="mt-10 grid grid-cols-2 gap-3 md:grid-cols-4">
                                            @foreach (['Licensing', 'Apprenticeship', 'Mentorship', 'Rank Path'] as $item)
                                                <div class="glass-card rounded-xl p-3 text-center text-xs font-semibold md:text-sm">
                                                    <span class="font-bold text-gw-accent">OK</span> {{ $item }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="absolute bottom-8 left-0 right-0 z-20 flex justify-center gap-2">
                    <template x-for="i in heroCount" :key="i">
                        <button type="button" class="h-2.5 rounded-full transition-all" :class="heroIndex === i - 1 ? 'w-7 bg-gw-accent' : 'w-2.5 bg-gray-500'" x-on:click="heroIndex = i - 1"></button>
                    </template>
                </div>

                <button
                    type="button"
                    aria-label="Previous hero slide"
                    class="absolute left-4 top-1/2 z-30 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-gw-accent/40 bg-black/50 text-2xl font-bold text-gw-accent shadow-xl backdrop-blur transition hover:bg-gw-accent hover:text-black focus:outline-none focus:ring-2 focus:ring-gw-accent md:left-8"
                    x-on:click="heroIndex = (heroIndex - 1 + heroCount) % heroCount"
                >
                    &lsaquo;
                </button>

                <button
                    type="button"
                    aria-label="Next hero slide"
                    class="absolute right-4 top-1/2 z-30 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-gw-accent/40 bg-black/50 text-2xl font-bold text-gw-accent shadow-xl backdrop-blur transition hover:bg-gw-accent hover:text-black focus:outline-none focus:ring-2 focus:ring-gw-accent md:right-8"
                    x-on:click="heroIndex = (heroIndex + 1) % heroCount"
                >
                    &rsaquo;
                </button>
            </section>

            <section id="who-we-are" class="mx-auto max-w-7xl px-6 py-24">
                <div class="text-center">
                    <h2 class="text-4xl font-bold md:text-5xl"><span class="gold-text-gradient">Built For Leaders.</span> Designed For Growth.</h2>
                    <div class="gold-bg-gradient mx-auto mt-4 h-1 w-24"></div>
                </div>

                <div class="mt-16 grid items-center gap-12 md:grid-cols-2">
                    <div>
                        <p class="text-lg leading-8 text-gray-300">
                            GWTrack brings the Greatway standard into one private operating system: new recruits see what to complete next, mentors see who needs attention, and leaders can spot progress across the team without digging through scattered messages.
                        </p>
                        <div class="mt-8 space-y-4">
                            @foreach (['New recruits move from invitation to onboarding with sponsor accountability.', 'Training Associates track licensing, training, and apprenticeship milestones.', 'Leaders get visibility into rank progress, FYO compensation, and team momentum.'] as $point)
                                <div class="flex items-center gap-3">
                                    <div class="h-3 w-3 rounded-full bg-gw-accent"></div>
                                    <span class="text-gray-200">{{ $point }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative rounded-2xl bg-[#111111] p-6 gold-border-glow">
                        <div class="grid gap-3 text-center text-sm font-semibold sm:grid-cols-3">
                            @foreach ([['1', 'Invite'], ['2', 'Onboard'], ['3', 'License'], ['4', 'Apprentice'], ['5', 'Advance'], ['6', 'Lead']] as [$number, $label])
                                <div class="rounded-xl border border-gw-accent/20 bg-black/45 p-4">
                                    <div class="text-2xl font-black text-gw-accent">{{ $number }}</div>
                                    <div>{{ $label }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section id="features" class="bg-[#0a0a0a] py-24">
                <div class="mx-auto max-w-7xl px-6">
                    <div class="mb-16 text-center">
                        <h2 class="text-4xl font-bold md:text-5xl">Everything Needed <span class="gold-text-gradient">To Build A Stronger Team</span></h2>
                    </div>

                    <div class="grid gap-7 md:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            ['Training Center', 'Structured learning, video lessons, product training, and leadership curriculum.'],
                            ['Licensing Tracker', 'Milestones for exam prep, license progress, appointments, and compliance steps.'],
                            ['Field Apprenticeship', 'Mentor assignments, progress tracking, sessions, notes, and real-world practice.'],
                            ['Certified Field Mentor', 'A dedicated system for mentor certification and apprentice management.'],
                            ['Rank Advancement', 'Producer and Builder promotion pathways with Greatway FYO compensation rates.'],
                            ['Team Growth Dashboard', 'Team visibility for sponsors, leaders, agency owners, and administrators.'],
                            ['Assessments', 'Quizzes and certification-style checks to validate learning.'],
                            ['Resource Library', 'Scripts, presentations, templates, documents, and field support material.'],
                        ] as $feature)
                            <div class="card-hover rounded-2xl border border-gw-accent/20 bg-[#111111] p-6">
                                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-gw-accent/15 text-sm font-black text-gw-accent">GW</div>
                                <h3 class="text-xl font-bold text-gw-accent">{{ $feature[0] }}</h3>
                                <p class="mt-3 text-sm leading-6 text-gray-400">{{ $feature[1] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="leadership-path" class="mx-auto max-w-6xl px-6 py-24">
                <div class="mb-16 text-center">
                    <h2 class="text-4xl font-bold md:text-5xl"><span class="gold-text-gradient">The Leadership Path</span> To National Executive Vice Chairman</h2>
                </div>

                <p class="mb-6 text-center text-sm font-semibold uppercase tracking-wide text-gw-accent">Producer track</p>
                <div class="relative flex flex-col items-center justify-between gap-4 md:flex-row">
                    <div class="absolute left-0 right-0 top-1/2 hidden h-0.5 bg-gradient-to-r from-transparent via-gw-accent to-transparent md:block"></div>
                    @foreach ([['TA', 'Training Associate'], ['ASC', 'Associate'], ['SA', 'Senior Associate'], ['MB', 'Managing Broker'], ['SMB', 'Senior MB'], ['AVP', 'Associate VP']] as [$code, $label])
                        <div class="z-10 w-full rounded-2xl border border-gw-accent/80 bg-black/85 p-5 text-center backdrop-blur md:w-36">
                            <div class="text-lg font-black text-gw-accent">{{ $code }}</div>
                            <div class="text-xs text-gray-300">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>

                <p class="mb-6 mt-12 text-center text-sm font-semibold uppercase tracking-wide text-gw-accent">Builder track · Field Leaders</p>
                <div class="relative flex flex-col items-center justify-between gap-4 md:flex-row">
                    <div class="absolute left-0 right-0 top-1/2 hidden h-0.5 bg-gradient-to-r from-transparent via-gw-accent to-transparent md:block"></div>
                    @foreach ([['SVP', 'Senior VP'], ['EVP', 'Executive VP'], ['EVC', 'Exec. Vice Chairman'], ['SEVC', 'Senior EVC']] as [$code, $label])
                        <div class="z-10 w-full rounded-2xl border border-gw-accent/80 bg-black/85 p-5 text-center backdrop-blur md:w-36">
                            <div class="text-lg font-black text-gw-accent">{{ $code }}</div>
                            <div class="text-xs text-gray-300">{{ $label }}</div>
                        </div>
                    @endforeach
                    <div class="z-10 w-full rounded-2xl bg-gradient-to-r from-gw-accent-dark to-gw-accent p-5 text-center shadow-xl md:w-36">
                        <div class="text-lg font-black text-black">NEVC</div>
                        <div class="text-xs font-bold text-black">National EVC</div>
                    </div>
                </div>

                <div class="mt-16 grid gap-6 text-center text-gray-300 md:grid-cols-4">
                    <div><span class="font-bold text-gw-accent">TA</span> - new agent foundations at 80% FYO</div>
                    <div><span class="font-bold text-gw-accent">ASC+</span> - personal production and NBAs</div>
                    <div><span class="font-bold text-gw-accent">SVP</span> - Field Leader and generational overrides</div>
                    <div><span class="font-bold text-gw-accent">NEVC</span> - national Super Team leadership</div>
                </div>
            </section>

            <section class="bg-[#0a0a0a] py-24">
                <div class="mx-auto max-w-6xl px-6 text-center">
                    <h2 class="text-4xl font-bold"><span class="gold-text-gradient">Mentorship Creates Momentum</span></h2>
                    <div class="mt-12 grid items-center gap-12 md:grid-cols-2">
                        <div class="rounded-2xl bg-[#111111] p-8 gold-border-glow">
                            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-gw-accent/15 text-lg font-black text-gw-accent">CFM</div>
                            <h3 class="text-2xl font-bold">Certified Field Mentor System</h3>
                            <p class="mt-4 leading-7 text-gray-300">Every new associate can be guided by a Certified Field Mentor through field apprenticeship progress, mentor notes, sessions, reviews, and approval workflows.</p>
                        </div>
                        <div class="space-y-3 text-left">
                            @foreach (['Mentor assignment and accountability', 'Apprentice milestones and action plans', 'Progress reviews and advancement readiness'] as $item)
                                <div class="rounded-2xl border-l-4 border-gw-accent bg-[#111111] p-6">
                                    <p class="font-semibold text-gray-100">{{ $item }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-6 py-24">
                <div class="mb-12 text-center">
                    <h2 class="text-4xl font-bold">Platform <span class="gold-text-gradient">Preview</span></h2>
                </div>
                <div class="grid gap-6 md:grid-cols-3">
                    @foreach ([
                        ['Onboarding Progress', '65%', 'Complete profile, watch welcome module, confirm sponsor.'],
                        ['Licensing Status', 'Exam pending', 'Track life license, carrier appointments, and next action.'],
                        ['Team Performance', '+127%', 'Review downline growth and active member movement.'],
                        ['Rank Progress', 'ASC 82%', 'See remaining requirements and leadership review items.'],
                        ['Upcoming Training', 'Tomorrow', 'Leadership webinar and product certification reminders.'],
                        ['Mentor Feedback', 'On track', 'CFM notes and follow-up sessions stay attached to progress.'],
                    ] as $preview)
                        <div class="rounded-xl border border-gw-accent/20 bg-[#111111] p-5">
                            <div class="font-bold text-gw-accent">{{ $preview[0] }}</div>
                            <div class="mt-2 text-2xl font-black">{{ $preview[1] }}</div>
                            <p class="mt-2 text-sm leading-6 text-gray-400">{{ $preview[2] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="testimonials" class="bg-[#0a0a0a] py-24">
                <div class="mx-auto max-w-6xl px-6">
                    <div class="mb-12 text-center">
                        <h2 class="text-4xl font-bold">Built For <span class="gold-text-gradient">Focused Team Leadership</span></h2>
                    </div>
                    <div class="grid gap-6 md:grid-cols-3">
                        @foreach ([
                            ['Managing Broker', 'GWTrack gives leaders one executive view of who needs support and who is ready for the next step.'],
                            ['Certified Field Mentor', 'The apprenticeship structure keeps coaching consistent without losing the human touch.'],
                            ['Agency Owner', 'Invitation tracking, roles, rank, and team placement make growth easier to manage.'],
                        ] as $quote)
                            <div class="rounded-2xl border border-gw-accent/20 bg-[#111111] p-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-gw-accent/40 bg-gw-accent/10 text-xs font-black text-gw-accent">GW</div>
                                    <div>
                                        <p class="font-bold">{{ $quote[0] }}</p>
                                        <p class="text-xs text-gw-accent">Team leadership</p>
                                    </div>
                                </div>
                                <p class="mt-4 text-sm italic leading-6 text-gray-300">"{{ $quote[1] }}"</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="relative overflow-hidden py-24 text-center">
                <div class="absolute inset-0 bg-gradient-to-r from-gw-accent/10 via-transparent to-gw-accent/10"></div>
                <div class="relative z-10 mx-auto max-w-3xl px-6">
                    <h2 class="text-4xl font-bold md:text-5xl">Ready To Build <span class="gold-text-gradient">Your Greatway to Growth?</span></h2>
                    <p class="mt-4 text-lg text-gray-300">Developing Leaders. Building Legacies. GWTrack is a private portal, so ask your sponsor for an invitation link or sign in if you already have access.</p>
                    <div class="mt-10 flex flex-wrap justify-center gap-5">
                        <a href="{{ $primaryUrl }}" class="gold-bg-gradient rounded-full px-8 py-3 text-lg font-bold text-black transition hover:scale-105">{{ $primaryLabel }}</a>
                        <a href="#contact" class="rounded-full border border-gw-accent px-8 py-3 font-semibold text-gw-accent hover:bg-white/5">Invitation Required</a>
                    </div>
                </div>
            </section>
        </main>

        <footer id="contact" class="border-t border-gw-accent/20 bg-black/90 px-6 py-12">
            <div class="mx-auto grid max-w-7xl gap-8 md:grid-cols-4">
                <div>
                    <div class="gold-text-gradient text-2xl font-bold">GWTrack.com</div>
                    <p class="mt-2 text-sm font-semibold text-gw-accent">Your Greatway to Growth</p>
                    <p class="mt-2 text-sm leading-6 text-gray-500">Developing Leaders. Building Legacies.</p>
                </div>
                <div>
                    <h4 class="font-bold text-gw-accent">Quick Links</h4>
                    <ul class="mt-3 space-y-2 text-sm text-gray-400">
                        <li><a href="#home" class="hover:text-white">Home</a></li>
                        <li><a href="#features" class="hover:text-white">Features</a></li>
                        <li><a href="#leadership-path" class="hover:text-white">Leadership Path</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-gw-accent">Portal Areas</h4>
                    <ul class="mt-3 space-y-2 text-sm text-gray-400">
                        <li>Training Center</li>
                        <li>Licensing Tracker</li>
                        <li>Mentorship</li>
                        <li>Rank Advancement</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-gw-accent">Access</h4>
                    <p class="mt-3 text-sm leading-6 text-gray-400">Registration is by sponsor invitation only. Existing members can use the secure login page.</p>
                    <a href="{{ $loginUrl }}" class="mt-4 inline-flex rounded-full border border-gw-accent px-5 py-2 text-sm font-bold text-gw-accent hover:bg-gw-accent/10">Sign In</a>
                </div>
            </div>
            <div class="mx-auto mt-10 max-w-7xl border-t border-gw-accent/20 pt-6 text-center text-sm text-gray-500">&copy; 2026 GWTrack.com. All rights reserved.</div>
        </footer>

        <div x-data="{ show: false }" x-init="window.addEventListener('scroll', () => { show = window.scrollY > 500 })" x-show="show" x-transition x-cloak class="fixed bottom-8 right-6 z-50">
            <button type="button" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })" class="gold-bg-gradient flex h-12 w-12 items-center justify-center rounded-full text-xl font-bold text-black shadow-2xl transition hover:scale-110">^</button>
        </div>
    </body>
</html>
