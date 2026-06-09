<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Janova Suite is a restaurant and cafe operating system for dine-in service, kitchen workflow, cashier settlement, customer ordering, inventory, and owner visibility.">
    <title>Janova Suite | Restaurant And Cafe Operating System</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/marketing.css') }}?v=20260608b">
    <script src="{{ asset('js/marketing.js') }}?v=20260608b" defer></script>
</head>
<body>
<div class="marketing-shell">
    @include('marketing.partials.header')

    <main>
        <section class="hero">
            <div class="section-inner">
                <div class="hero-content" data-reveal>
                    <div class="eyebrow">Custom SaaS for cafes and restaurants</div>
                    <h1>Janova Restaurant And Cafe Suite</h1>
                    <p class="hero-copy">
                        A tailored operating system for owners who need one connected flow across branches,
                        tables, waiters, kitchen, cashier, customer ordering, inventory, and management visibility.
                    </p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="{{ route('marketing.contact') }}">Request A Fit Survey</a>
                        <a class="button button-secondary" href="#platform">Explore The Platform</a>
                    </div>
                    <div class="hero-proof" aria-label="Janova product highlights">
                        <div class="proof-item">
                            <strong>5 roles</strong>
                            <span>Owner, waiter, kitchen, cashier, customer</span>
                        </div>
                        <div class="proof-item">
                            <strong>Branch-ready</strong>
                            <span>Restaurant, branch, user, menu, table scoping</span>
                        </div>
                        <div class="proof-item">
                            <strong>Pilot-ready</strong>
                            <span>Best sold with implementation support</span>
                        </div>
                    </div>
                    <div class="motion-rail" aria-label="Live Janova workflow examples">
                        <div class="motion-track">
                            <span>Table opened</span>
                            <span>Ticket sent</span>
                            <span>Kitchen ready</span>
                            <span>Cashier queue</span>
                            <span>Receipt printed</span>
                            <span>Owner review</span>
                            <span>Table opened</span>
                            <span>Ticket sent</span>
                            <span>Kitchen ready</span>
                            <span>Cashier queue</span>
                            <span>Receipt printed</span>
                            <span>Owner review</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-soft" id="platform">
            <div class="section-inner section-split">
                <div class="section-heading" data-reveal>
                    <div class="section-kicker">Operating Principles</div>
                    <h2>Built around the real restaurant day.</h2>
                    <p class="lead">
                        Janova is positioned as a controlled beta or custom SaaS deployment for cafes,
                        casual dining, and local multi-branch operators that need custom workflow control.
                    </p>
                    <div class="workflow">
                        <div class="workflow-step">
                            <div>
                                <h3>Owner sets the foundation</h3>
                                <p>Branches, staff, tables, menus, products, recipes, suppliers, and inventory live in one admin workspace.</p>
                            </div>
                        </div>
                        <div class="workflow-step">
                            <div>
                                <h3>Service moves through roles</h3>
                                <p>Waiters open table orders, kitchen handles preparation states, and cashier receives settlement-ready bills.</p>
                            </div>
                        </div>
                        <div class="workflow-step">
                            <div>
                                <h3>Managers review the result</h3>
                                <p>Revenue, orders, product performance, stock pressure, receipts, and operational records stay visible.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="media-stack" aria-label="Janova product screens">
                    <div class="device-frame device-laptop device-one float-screen" data-tilt>
                        <img src="{{ asset('images/marketing/flutter-real-owner.png') }}" alt="Janova Flutter owner dashboard screen" loading="lazy">
                    </div>
                    <div class="device-frame device-tablet device-two float-screen float-delay" data-tilt>
                        <img src="{{ asset('images/marketing/flutter-real-waiter.png') }}" alt="Janova Flutter waiter table screen" loading="lazy">
                    </div>
                    <div class="device-frame device-phone device-three float-screen float-delay-long" data-tilt>
                        <img src="{{ asset('images/marketing/flutter-real-customer.png') }}" alt="Janova Flutter customer mobile home screen" loading="lazy">
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-inner">
                <div class="section-heading center" data-reveal>
                    <div class="section-kicker">Core Modules</div>
                    <h2>One suite for the roles that move the floor.</h2>
                    <p class="lead">An abbreviated view of the operating modules clients ask about first.</p>
                </div>

                <div class="module-grid">
                    <article class="panel" data-reveal>
                        <span class="panel-icon">01</span>
                        <h3>Owner/Admin Dashboard</h3>
                        <p>Manage restaurants, branches, users, roles, menus, products, orders, tables, customers, suppliers, recipes, and inventory.</p>
                    </article>
                    <article class="panel" data-reveal>
                        <span class="panel-icon">02</span>
                        <h3>Table Service</h3>
                        <p>Open table orders, add items and modifiers, send tickets to kitchen, move tables, and control changes or refunds.</p>
                    </article>
                    <article class="panel" data-reveal>
                        <span class="panel-icon">03</span>
                        <h3>Kitchen Workflow</h3>
                        <p>Track active kitchen items through queued, preparing, ready, served, and returned states with item-level history.</p>
                    </article>
                    <article class="panel" data-reveal>
                        <span class="panel-icon">04</span>
                        <h3>Cashier And Receipts</h3>
                        <p>Handle payment capture, selected item payments, receipt generation, refunds, and settlement-oriented operations.</p>
                    </article>
                    <article class="panel" data-reveal>
                        <span class="panel-icon">05</span>
                        <h3>Customer Ordering</h3>
                        <p>Let customers browse restaurants and menus, authenticate, see order history, and place pickup or takeaway orders.</p>
                    </article>
                    <article class="panel" data-reveal>
                        <span class="panel-icon">06</span>
                        <h3>Inventory And Recipes</h3>
                        <p>Receive purchases, transfer stock, count stock, adjust quantities, record wastage, and connect recipes to usage.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section section-dark" id="workflow">
            <div class="section-inner">
                <div class="section-heading center" data-reveal>
                    <div class="section-kicker">Mobile Staff Suite</div>
                    <h2>Real Flutter apps for each side of service.</h2>
                    <p class="lead">
                        These are live captures from the Flutter apps running against the local Janova API, with seeded restaurant data.
                    </p>
                </div>

                <div class="app-flow-map" aria-label="Janova role handoff">
                    <span>Owner</span>
                    <span>Waiter</span>
                    <span>Kitchen</span>
                    <span>Cashier</span>
                    <span>Customer</span>
                </div>

                <div class="role-showcase">
                    <article class="role-story" data-reveal>
                        <div class="role-copy">
                            <span class="role-number">01</span>
                            <h3>Owner app</h3>
                            <p>Branch health, sales, active floor, KDS backlog, cashier queue, loyalty, and stock alerts in one review surface.</p>
                            <div class="feature-chips">
                                <span>Branch dashboard</span>
                                <span>Revenue pulse</span>
                                <span>Stock alerts</span>
                                <span>Live floor</span>
                            </div>
                        </div>
                        <div class="role-visual">
                            <div class="device-frame role-device role-device-wide" data-tilt>
                                <img src="{{ asset('images/marketing/flutter-real-owner.png') }}" alt="Real Janova Flutter owner dashboard screen" loading="lazy">
                            </div>
                        </div>
                    </article>

                    <article class="role-story" data-reveal>
                        <div class="role-copy">
                            <span class="role-number">02</span>
                            <h3>Waiter app</h3>
                            <p>Tables, open checks, guest attachment, modifiers, kitchen handoff, and cashier handoff without jumping screens.</p>
                            <div class="feature-chips">
                                <span>Table map</span>
                                <span>Open checks</span>
                                <span>Send to kitchen</span>
                                <span>Move table</span>
                            </div>
                        </div>
                        <div class="role-visual">
                            <div class="device-frame role-device role-device-wide" data-tilt>
                                <img src="{{ asset('images/marketing/flutter-real-waiter.png') }}" alt="Real Janova Flutter waiter table management screen" loading="lazy">
                            </div>
                        </div>
                    </article>

                    <article class="role-story" data-reveal>
                        <div class="role-copy">
                            <span class="role-number">03</span>
                            <h3>Kitchen app</h3>
                            <p>Queued, preparing, ready, served, and returned lanes keep cooks aligned on what needs attention now.</p>
                            <div class="feature-chips">
                                <span>KDS lanes</span>
                                <span>Item states</span>
                                <span>Ticket cards</span>
                                <span>Returns</span>
                            </div>
                        </div>
                        <div class="role-visual">
                            <div class="device-frame role-device role-device-wide" data-tilt>
                                <img src="{{ asset('images/marketing/flutter-real-kitchen.png') }}" alt="Real Janova Flutter kitchen board screen" loading="lazy">
                            </div>
                        </div>
                    </article>

                    <article class="role-story" data-reveal>
                        <div class="role-copy">
                            <span class="role-number">04</span>
                            <h3>Cashier app</h3>
                            <p>Settlement-ready tickets, itemized receipt review, split tender drafting, quick amounts, and payment status.</p>
                            <div class="feature-chips">
                                <span>Cashier queue</span>
                                <span>Ticket review</span>
                                <span>Split tender</span>
                                <span>Receipts</span>
                            </div>
                        </div>
                        <div class="role-visual">
                            <div class="device-frame role-device role-device-wide" data-tilt>
                                <img src="{{ asset('images/marketing/flutter-real-cashier.png') }}" alt="Real Janova Flutter cashier payment screen" loading="lazy">
                            </div>
                        </div>
                    </article>

                    <article class="role-story role-story-customer" data-reveal>
                        <div class="role-copy">
                            <span class="role-number">05</span>
                            <h3>Customer app</h3>
                            <p>OTP access, restaurant browse, search, order history, loyalty points, rewards, and saved customer profile surfaces.</p>
                            <div class="feature-chips">
                                <span>OTP login</span>
                                <span>Restaurant browse</span>
                                <span>Order again</span>
                                <span>Rewards</span>
                            </div>
                        </div>
                        <div class="role-visual role-phone-visual">
                            <div class="device-frame device-phone role-phone" data-tilt>
                                <img src="{{ asset('images/marketing/flutter-real-customer.png') }}" alt="Real Janova Flutter customer mobile app screen" loading="lazy">
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="section section-soft" id="comparison">
            <div class="section-inner">
                <div class="section-heading center" data-reveal>
                    <div class="section-kicker">SaaS Website Comparison</div>
                    <h2>Clear positioning against mature restaurant POS platforms.</h2>
                    <p class="lead">
                        Modern restaurant SaaS sites usually sell packaged breadth: payments, loyalty, online ordering,
                        marketplace integrations, BI, and certified hardware. Janova should present a more practical custom-control offer.
                    </p>
                </div>

                <div class="comparison-grid">
                    <div class="panel comparison-card" data-reveal>
                        <strong>Generic packaged POS</strong>
                        <span>Best when the restaurant wants a ready-made bundle, standard integrations, mature payment packaging, and lower customization.</span>
                    </div>
                    <div class="panel comparison-card comparison-highlight" data-reveal>
                        <strong>Janova custom suite</strong>
                        <span>Best when owners need direct control over workflow, white-label ownership, local rules, custom roles, and branch-specific operations.</span>
                    </div>
                    <div class="panel comparison-card" data-reveal>
                        <strong>Honest sales promise</strong>
                        <span>Position Janova as pilot-ready and implementation-led, not as a full Foodics-style replacement until payment, printer, loyalty, and marketplace proof is field-tested.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-inner">
                <div class="section-heading center" data-reveal>
                    <div class="section-kicker">Pilot Package</div>
                    <h2>What a serious client evaluation should include.</h2>
                </div>
                <div class="pilot-grid">
                    <article class="panel" data-reveal>
                        <h3>Workflow mapping</h3>
                        <p>Document table service, kitchen handoff, cashier settlement, returns, stock movement, and owner reporting needs.</p>
                    </article>
                    <article class="panel" data-reveal>
                        <h3>Branch and device plan</h3>
                        <p>Confirm tablets, phones, printers, terminals, cash drawers, internet reliability, and fallback procedures before go-live.</p>
                    </article>
                    <article class="panel" data-reveal>
                        <h3>Validation checklist</h3>
                        <p>Run a complete day scenario from setup to order, kitchen, payment, receipt, stock update, and manager review.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section section-soft">
            <div class="section-inner">
                <div class="cta-band" data-reveal>
                    <div>
                        <h2 style="font-size: clamp(1.8rem, 3vw, 3rem);">Start with the fit survey.</h2>
                        <p>Share your operation details, current system, pain points, devices, timeline, and contact preferences so the admin team can qualify your requirements before a call.</p>
                    </div>
                    <a class="button button-primary" href="{{ route('marketing.contact') }}">Open Contact Survey</a>
                </div>
            </div>
        </section>
    </main>

    @include('marketing.partials.footer')
</div>
</body>
</html>
