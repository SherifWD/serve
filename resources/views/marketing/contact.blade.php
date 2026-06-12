<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Tell Janova about your restaurant or cafe needs and request a follow-up.">
    <title>Contact Janova | Restaurant Fit Survey</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/marketing.css') }}?v=20260612a">
</head>
<body>
<div class="marketing-shell">
    @include('marketing.partials.header')

    <main>
        <section class="contact-hero">
            <div class="section-inner">
                <div class="eyebrow">Customer requirements survey</div>
                <h1>Tell us what your restaurant needs before we schedule the call.</h1>
                <p>
                    This form captures contact details, current operations, desired modules, devices, timeline,
                    and pain points so the Janova Technologies team can review your needs and contact you with the right next step.
                </p>
            </div>
        </section>

        <section class="section section-soft">
            <div class="section-inner survey-layout">
                <aside class="survey-aside">
                    <div class="section-heading">
                        <div class="section-kicker">What The Admin Receives</div>
                        <h2 style="font-size: clamp(2rem, 3.2vw, 3.2rem);">A useful lead profile, not just a name.</h2>
                        <p class="lead">Every submission is stored as a marketing inquiry for admin review and emailed to pos@janovatech.com.</p>
                    </div>
                    <div class="aside-list">
                        <div>Business size, type, location, branch count, and staff count.</div>
                        <div>Current POS or manual process and the channels you sell through.</div>
                        <div>Required modules such as waiter, KDS, cashier, inventory, customer ordering, and reporting.</div>
                        <div>Contact preferences and best time to follow up.</div>
                    </div>
                </aside>

                <form class="survey-form" method="POST" action="{{ route('marketing.contact.store') }}">
                    @csrf

                    @if (session('status'))
                        <div class="notice notice-success">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="notice notice-error">Please check the highlighted fields and submit again.</div>
                    @endif

                    <section class="form-section">
                        <h2>Contact Details</h2>
                        <div class="form-grid">
                            <div class="field">
                                <label for="full_name">Full name</label>
                                <input id="full_name" name="full_name" value="{{ old('full_name') }}" autocomplete="name" required>
                                @error('full_name') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="business_name">Restaurant or cafe name</label>
                                <input id="business_name" name="business_name" value="{{ old('business_name') }}" required>
                                @error('business_name') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="role">Your role</label>
                                <input id="role" name="role" value="{{ old('role') }}" placeholder="Owner, manager, operations lead">
                                @error('role') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="city">City</label>
                                <input id="city" name="city" value="{{ old('city') }}" autocomplete="address-level2">
                                @error('city') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                                @error('email') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="phone">Phone or WhatsApp number</label>
                                <input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" required>
                                @error('phone') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field full">
                                <label for="website">Website or social page</label>
                                <input id="website" name="website" value="{{ old('website') }}" placeholder="Website, Instagram, or ordering page">
                                @error('website') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h2>Operation Profile</h2>
                        <div class="form-grid">
                            <div class="field">
                                <label for="business_type">Business type</label>
                                <select id="business_type" name="business_type" required>
                                    <option value="">Select type</option>
                                    @foreach ([
                                        'cafe' => 'Cafe',
                                        'restaurant' => 'Restaurant',
                                        'cloud-kitchen' => 'Cloud kitchen',
                                        'bakery' => 'Bakery',
                                        'multi-concept' => 'Multi-concept group',
                                        'other' => 'Other',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('business_type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('business_type') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="current_system">Current system</label>
                                <input id="current_system" name="current_system" value="{{ old('current_system') }}" placeholder="Manual, Excel, Foodics, POS name">
                                @error('current_system') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="branch_count">Number of branches</label>
                                <input id="branch_count" type="number" min="1" max="500" name="branch_count" value="{{ old('branch_count') }}">
                                @error('branch_count') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="staff_count">Approximate staff count</label>
                                <input id="staff_count" type="number" min="1" max="5000" name="staff_count" value="{{ old('staff_count') }}">
                                @error('staff_count') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        @php
                            $oldChannels = old('order_channels', []);
                            $oldInterests = old('interest_areas', []);
                            $oldDevices = old('devices', []);
                        @endphp
                        <h2>Needs And Modules</h2>
                        <div class="form-grid">
                            <div class="field full">
                                <div class="choice-title">Order channels</div>
                                <div class="choice-grid">
                                    @foreach (['dine-in' => 'Dine-in tables', 'takeaway' => 'Takeaway', 'pickup' => 'Pickup', 'delivery' => 'Delivery', 'qr' => 'QR menu', 'online' => 'Online ordering'] as $value => $label)
                                        <label class="choice">
                                            <input type="checkbox" name="order_channels[]" value="{{ $value }}" @checked(in_array($value, $oldChannels, true))>
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('order_channels') <span class="form-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field full">
                                <div class="choice-title">Interested modules</div>
                                <div class="choice-grid">
                                    @foreach (['owner-dashboard' => 'Owner dashboard', 'waiter' => 'Waiter app', 'kds' => 'Kitchen display', 'cashier' => 'Cashier and receipts', 'customer-ordering' => 'Customer ordering', 'inventory' => 'Inventory and recipes', 'billing' => 'SaaS billing', 'hardware' => 'Printers and terminals'] as $value => $label)
                                        <label class="choice">
                                            <input type="checkbox" name="interest_areas[]" value="{{ $value }}" @checked(in_array($value, $oldInterests, true))>
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('interest_areas') <span class="form-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field full">
                                <div class="choice-title">Devices currently used or planned</div>
                                <div class="choice-grid">
                                    @foreach (['ios' => 'iPad or iPhone', 'android' => 'Android tablets or phones', 'windows' => 'Windows POS', 'printer' => 'Receipt printers', 'kitchen-printer' => 'Kitchen printers', 'terminal' => 'Payment terminals'] as $value => $label)
                                        <label class="choice">
                                            <input type="checkbox" name="devices[]" value="{{ $value }}" @checked(in_array($value, $oldDevices, true))>
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('devices') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h2>Project Fit</h2>
                        <div class="form-grid">
                            <div class="field">
                                <label for="timeline">Timeline</label>
                                <select id="timeline" name="timeline" required>
                                    <option value="">Select timeline</option>
                                    @foreach ([
                                        'this-month' => 'This month',
                                        '1-3-months' => '1 to 3 months',
                                        '3-6-months' => '3 to 6 months',
                                        'researching' => 'Still researching',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('timeline') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('timeline') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="budget_range">Expected budget range</label>
                                <input id="budget_range" name="budget_range" value="{{ old('budget_range') }}" placeholder="Monthly or implementation budget">
                                @error('budget_range') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field full">
                                <label for="pain_points">Main problems you want to solve</label>
                                <textarea id="pain_points" name="pain_points" required>{{ old('pain_points') }}</textarea>
                                @error('pain_points') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field full">
                                <label for="success_notes">What would make this project successful?</label>
                                <textarea id="success_notes" name="success_notes">{{ old('success_notes') }}</textarea>
                                @error('success_notes') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <h2>Follow Up</h2>
                        <div class="form-grid">
                            <div class="field">
                                <label for="preferred_contact_method">Preferred contact method</label>
                                <select id="preferred_contact_method" name="preferred_contact_method" required>
                                    <option value="">Select method</option>
                                    @foreach (['phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'email' => 'Email'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('preferred_contact_method') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('preferred_contact_method') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label for="best_contact_time">Best contact time</label>
                                <input id="best_contact_time" name="best_contact_time" value="{{ old('best_contact_time') }}" placeholder="Morning, afternoon, evening, specific days">
                                @error('best_contact_time') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field full">
                                <label class="consent-row">
                                    <input type="checkbox" name="consent_to_contact" value="1" @checked(old('consent_to_contact')) required>
                                    <span>I agree that Janova can store this inquiry and contact me about my restaurant or cafe requirements.</span>
                                </label>
                                @error('consent_to_contact') <span class="form-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div style="margin-top: 22px;">
                            <button class="button button-dark" type="submit">Submit Requirements Survey</button>
                        </div>
                    </section>
                </form>
            </div>
        </section>
    </main>

    @include('marketing.partials.footer')
</div>
</body>
</html>
