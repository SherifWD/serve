<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Janova Serve POS is a food and beverage operating system for cafes, restaurants, cloud kitchens, waiters, KDS, cashier, QR ordering, inventory, branches, revenue, expenses, and owner control.">
    <title>Janova Serve POS | Food And Beverage POS By Janova Technologies</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/marketing.css') }}?v=20260612a">
    <script src="{{ asset('js/marketing.js') }}?v=20260612a" defer></script>
</head>
<body>
@php
    $businessModes = [
        [
            'name' => 'Small cafe',
            'summary' => 'Cashier-first service, favorites, quick receipt, optional KDS, no forced tables.',
            'accent' => 'Speed',
        ],
        [
            'name' => 'Big cafe',
            'summary' => 'Cashier plus barista KDS, pickup names, queue screens, split tenders, station routing.',
            'accent' => 'Queues',
        ],
        [
            'name' => 'Small restaurant',
            'summary' => 'Simple table map, waiter order flow, kitchen handoff, cashier settlement, refunds.',
            'accent' => 'Tables',
        ],
        [
            'name' => 'Big restaurant',
            'summary' => 'Waiter sections, My Tables, seat and course ordering, table move, split bills, multiple kitchens.',
            'accent' => 'Control',
        ],
    ];

    $featureGroups = [
        [
            'title' => 'Setup And Admin',
            'items' => [
                'Restaurant, branch, type, currency, opening hours',
                'Business-mode presets with custom overrides',
                'Roles, permissions, employees, branch assignment',
                'Category, menu, ingredient, recipe, product setup',
                'Tables scoped by restaurant and branch',
                'Drawer, receipt printer, device setup',
            ],
        ],
        [
            'title' => 'Waiter And Floor',
            'items' => [
                'Table map and table status filters',
                'My Tables, unassigned, ready, cashier, returned',
                'Waiter name on table cards for large floors',
                'Product search, favorites, recent products',
                'Seat numbers, courses, duplicate item, notes',
                'Remove unsent items without return history',
            ],
        ],
        [
            'title' => 'Cashier Speed Mode',
            'items' => [
                'Counter sale and organized item list',
                'Favorites grid and fast quantity controls',
                'Quick cash amounts and split tenders',
                'Hold and resume tickets',
                'Recent orders, paid receipt, drawer shifts',
                'Customer name, phone, loyalty points',
            ],
        ],
        [
            'title' => 'Kitchen And KDS',
            'items' => [
                'Kitchen queue, preparing, ready, served lanes',
                'Station routing by product or category',
                'Barista, grill, cold, dessert stations',
                'Manual refresh and last updated status',
                'Returned item visibility and history',
                'Failed printer retry and kitchen tickets',
            ],
        ],
        [
            'title' => 'Manager Controls',
            'items' => [
                'Refund reason capture',
                'Void after kitchen start with manager approval',
                'Discount approval above configured limits',
                'Returned item report by waiter, item, reason',
                'Employee revenue by branch',
                'Owner-only and admin-global visibility',
            ],
        ],
        [
            'title' => 'Owner Finance',
            'items' => [
                'Revenue, expenses, and branch profitability',
                'Cash drawer open, close, and shift close',
                'Tips and service charge tracking',
                'Restaurant and branch filters',
                'Product, waiter, cashier, KDS performance',
                'Admin global reporting across all restaurants',
            ],
        ],
        [
            'title' => 'Customer Ordering',
            'items' => [
                'QR table ordering with waiter approval',
                'Customer-added item review',
                'Pickup and takeaway time slots',
                'Order history and loyalty profile',
                'Customer phone and name attachment',
                'Optional customer mobile experience',
            ],
        ],
        [
            'title' => 'Operational Reliability',
            'items' => [
                'Bring your own compatible hardware',
                'Receipt printers, kitchen printers, drawers',
                'Device health screen and branch setup checks',
                'Low-stock warning before ordering',
                'Manual refresh without forced auto reload',
                'Offline order queue roadmap module',
            ],
        ],
    ];

    $apps = [
        ['name' => 'Admin dashboard', 'image' => 'owner-dashboard.png', 'copy' => 'Global control for restaurants, branches, setup, menus, employees, devices, billing, revenue, expenses, and reports.'],
        ['name' => 'Owner app', 'image' => 'flutter-real-owner.png', 'copy' => 'Owner-only view for live revenue, expenses, branch health, active floor, stock alerts, and staff performance.'],
        ['name' => 'Waiter app', 'image' => 'flutter-real-waiter.png', 'copy' => 'Floor operation with My Tables, quick actions, item notes, order approval, table movement, and kitchen handoff.'],
        ['name' => 'Kitchen app', 'image' => 'flutter-real-kitchen.png', 'copy' => 'KDS lanes and stations for barista, grill, cold, dessert, returned items, and prep timing.'],
        ['name' => 'Cashier app', 'image' => 'flutter-real-cashier.png', 'copy' => 'Fast counter service, ticket settlement, split tenders, drawers, receipts, recent orders, and customer loyalty.'],
        ['name' => 'Customer app', 'image' => 'flutter-real-customer.png', 'copy' => 'QR or mobile ordering, pickup, takeaway, saved profile, loyalty, and order history.'],
    ];

    $plans = [
        [
            'name' => 'Starter Cafe',
            'for' => 'Drinks-only and small quick-service cafes',
            'prices' => [
                'egypt' => 'EGP 1,490/mo',
                'mena' => 'USD 49/mo',
                'international' => 'USD 59/mo',
            ],
            'note' => 'Software only, 1 branch, up to 3 active staff devices.',
            'features' => [
                'Cashier-first ordering',
                'Products, categories, modifiers',
                'Favorites and quick quantities',
                'Receipt printer and cash drawer setup',
                'Daily sales, expenses, basic reports',
                'Bring your own compatible hardware',
            ],
        ],
        [
            'name' => 'Cafe + KDS',
            'for' => 'Cafes with barista, pickup, and queue workflow',
            'prices' => [
                'egypt' => 'EGP 2,990/mo',
                'mena' => 'USD 99/mo',
                'international' => 'USD 129/mo',
            ],
            'note' => '1 branch, up to 6 active staff devices.',
            'features' => [
                'Everything in Starter Cafe',
                'Barista KDS and station routing',
                'Pickup names and recent orders',
                'Hold and resume tickets',
                'Customer profile and loyalty points',
                'Owner app revenue and expense view',
            ],
        ],
        [
            'name' => 'Restaurant Pro',
            'for' => 'Small and growing dine-in restaurants',
            'prices' => [
                'egypt' => 'EGP 4,990/mo',
                'mena' => 'USD 169/mo',
                'international' => 'USD 229/mo',
            ],
            'note' => '1 branch, up to 12 active staff devices.',
            'features' => [
                'Waiter, kitchen, cashier workflow',
                'Tables, My Tables, waiter ownership',
                'Split bills, returns, refund reasons',
                'Recipes, ingredients, low-stock warnings',
                'Manager approval rules',
                'Employee revenue and branch reports',
            ],
        ],
        [
            'name' => 'Multi-Branch HQ',
            'for' => 'Large restaurants, chains, and mixed concepts',
            'prices' => [
                'egypt' => 'From EGP 7,500/mo',
                'mena' => 'From USD 299/mo',
                'international' => 'From USD 399/mo',
            ],
            'note' => 'HQ pricing plus branch/device scope after survey.',
            'features' => [
                'Multiple branches and concepts',
                'Advanced roles and branch controls',
                'Multi-station KDS and queue screens',
                'Seat/course timing and table transfers',
                'Global admin reports and exports',
                'Custom integrations and rollout support',
            ],
        ],
    ];

    $implementation = [
        'Self-guided setup' => 'Included for simple cafes using existing hardware.',
        'Guided onboarding' => 'From EGP 4,500 or USD 150 for menu, branch, device, and team setup.',
        'Pro implementation' => 'From EGP 12,000 or USD 400 for restaurants with recipes, KDS, reports, and staff training.',
        'Custom rollout' => 'Quoted after survey for migrations, integrations, multi-branch deployment, and custom workflows.',
    ];
@endphp

<div class="marketing-shell">
    @include('marketing.partials.header')

    <main class="landing-page">
        <section class="landing-hero">
            <div class="hero-screen-wall" aria-hidden="true">
                <img src="{{ asset('images/marketing/owner-dashboard.png') }}" alt="">
                <img src="{{ asset('images/marketing/waiter-order-composer.png') }}" alt="">
                <img src="{{ asset('images/marketing/kitchen-board.png') }}" alt="">
            </div>
            <div class="section-inner landing-hero-inner">
                <div class="landing-hero-copy" data-reveal>
                    <div class="eyebrow">Powered and owned by Janova Technologies</div>
                    <h1>Janova Serve POS</h1>
                    <p class="hero-copy">
                        A food and beverage operating system for cafes, restaurants, cloud kitchens, and multi-branch groups.
                        Run waiter, cashier, kitchen, customer ordering, inventory, revenue, expenses, and owner control from one workflow.
                    </p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="#pricing">See Pricing</a>
                        <a class="button button-secondary" href="{{ route('marketing.contact') }}">Request Custom Survey</a>
                    </div>
                    <div class="hero-proof hero-proof-compact" aria-label="Janova Serve POS highlights">
                        <div class="proof-item">
                            <strong>No hardware lock-in</strong>
                            <span>Use compatible tablets, printers, drawers, and screens you already own.</span>
                        </div>
                        <div class="proof-item">
                            <strong>Mode-based setup</strong>
                            <span>Small cafe, big cafe, small restaurant, or big restaurant without unnecessary complexity.</span>
                        </div>
                        <div class="proof-item">
                            <strong>Branch scoped</strong>
                            <span>Restaurant, branch, staff, menu, table, revenue, and expenses stay correctly separated.</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section feature-strip" id="features">
            <div class="section-inner">
                <div class="section-heading center" data-reveal>
                    <div class="section-kicker">Complete F&B Platform</div>
                    <h2>Every role gets the controls it needs, without forcing big-restaurant complexity on small shops.</h2>
                    <p class="lead">Short, practical modules covering the daily cycle from setup to order, kitchen, settlement, reporting, and owner review.</p>
                </div>

                <div class="feature-matrix">
                    @foreach ($featureGroups as $group)
                        <article class="feature-panel" data-reveal>
                            <h3>{{ $group['title'] }}</h3>
                            <ul>
                                @foreach ($group['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section section-soft" id="modes">
            <div class="section-inner">
                <div class="section-heading" data-reveal>
                    <div class="section-kicker">Business Mode Setup</div>
                    <h2>Start simple. Switch on complexity only when the operation needs it.</h2>
                    <p class="lead">Owner and admin setup can shape each branch by type, service style, KDS usage, table workflow, waiter ownership, currency, and operating hours.</p>
                </div>

                <div class="mode-grid">
                    @foreach ($businessModes as $mode)
                        <article class="mode-card" data-reveal>
                            <span>{{ $mode['accent'] }}</span>
                            <h3>{{ $mode['name'] }}</h3>
                            <p>{{ $mode['summary'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section app-showcase-section" id="apps">
            <div class="section-inner">
                <div class="section-heading center" data-reveal>
                    <div class="section-kicker">Real Apps And Dashboard</div>
                    <h2>Flutter staff apps plus a dashboard for admin and owner control.</h2>
                    <p class="lead">The interface is built around the people doing the work: owner, waiter, kitchen, cashier, customer, and admin.</p>
                </div>

                <div class="app-showcase-grid">
                    @foreach ($apps as $app)
                        <article class="app-showcase-card" data-reveal>
                            <div class="app-shot" data-tilt>
                                <img src="{{ asset('images/marketing/' . $app['image']) }}" alt="{{ $app['name'] }} screen" loading="lazy">
                            </div>
                            <div>
                                <h3>{{ $app['name'] }}</h3>
                                <p>{{ $app['copy'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section section-dark pricing-section" id="pricing" data-pricing-region="egypt">
            <div class="section-inner">
                <div class="pricing-head" data-reveal>
                    <div class="section-heading">
                        <div class="section-kicker">Market-Positioned Pricing</div>
                        <h2>Software pricing for Egypt, MENA, and international launches.</h2>
                        <p class="lead">
                            Benchmarked against public restaurant POS pricing, then adjusted for Janova's strongest edge:
                            software-first deployment on compatible hardware you already own.
                        </p>
                    </div>
                    <div class="pricing-tabs" role="tablist" aria-label="Pricing region">
                        <button class="pricing-tab is-active" type="button" data-region-target="egypt">Egypt</button>
                        <button class="pricing-tab" type="button" data-region-target="mena">MENA</button>
                        <button class="pricing-tab" type="button" data-region-target="international">International</button>
                    </div>
                </div>

                <div class="pricing-grid">
                    @foreach ($plans as $index => $plan)
                        <article class="pricing-card {{ $index === 2 ? 'pricing-card-featured' : '' }}" data-reveal>
                            @if ($index === 2)
                                <div class="plan-badge">Best for restaurants</div>
                            @endif
                            <h3>{{ $plan['name'] }}</h3>
                            <p class="plan-for">{{ $plan['for'] }}</p>
                            <div class="plan-price">
                                @foreach ($plan['prices'] as $region => $price)
                                    <strong data-region-price="{{ $region }}" @if ($region !== 'egypt') hidden @endif>{{ $price }}</strong>
                                @endforeach
                                <span>{{ $plan['note'] }}</span>
                            </div>
                            <ul>
                                @foreach ($plan['features'] as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <a class="button {{ $index === 2 ? 'button-primary' : 'button-light' }}" href="{{ route('marketing.contact') }}?plan={{ urlencode($plan['name']) }}">Request This Plan</a>
                        </article>
                    @endforeach
                </div>

                <div class="pricing-footnote" data-reveal>
                    <p>
                        Prices are recommended public starting points for SaaS software. Payment processing, SMS/WhatsApp,
                        fiscal integrations, special hardware, data migration, and custom integrations can be quoted separately.
                    </p>
                </div>
            </div>
        </section>

        <section class="section section-soft">
            <div class="section-inner">
                <div class="implementation-grid">
                    <div class="section-heading" data-reveal>
                        <div class="section-kicker">Implementation Options</div>
                        <h2>Sell the software clearly, then quote the rollout honestly.</h2>
                        <p class="lead">Small cafes can start quickly. Restaurants and chains should pay for proper setup, training, recipes, printer testing, and workflow validation.</p>
                    </div>
                    <div class="implementation-list">
                        @foreach ($implementation as $title => $copy)
                            <div class="implementation-item" data-reveal>
                                <strong>{{ $title }}</strong>
                                <span>{{ $copy }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="section cta-section" id="custom">
            <div class="section-inner">
                <div class="cta-band landing-cta" data-reveal>
                    <div>
                        <div class="section-kicker">Custom And Multi-Branch Plans</div>
                        <h2>Need a rollout for a chain, franchise, hotel outlet, cloud kitchen, or mixed cafe and restaurant group?</h2>
                        <p>
                            Fill the survey with branch count, current POS, devices, modules, migration needs, timeline, and budget.
                            The inquiry is stored for dashboard review and emailed to <strong>pos@janovatech.com</strong>.
                        </p>
                    </div>
                    <a class="button button-primary" href="{{ route('marketing.contact') }}">Open Full Survey</a>
                </div>
            </div>
        </section>
    </main>

    @include('marketing.partials.footer')
</div>
</body>
</html>
