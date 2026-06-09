<header class="site-header">
    <div class="section-inner nav-bar">
        <a class="brand-link" href="{{ route('marketing.home') }}" aria-label="Janova home">
            <img src="{{ asset('images/janova-logo.svg') }}" alt="" width="42" height="42">
            <span class="brand-wordmark">
                Janova Suite
                <span>Restaurant and cafe operating system</span>
            </span>
        </a>

        <nav class="nav-links" aria-label="Main navigation">
            <a href="{{ route('marketing.home') }}#platform">Platform</a>
            <a href="{{ route('marketing.home') }}#workflow">Apps</a>
            <a href="{{ route('marketing.home') }}#comparison">Comparison</a>
            <a href="{{ route('marketing.contact') }}">Contact</a>
            <a class="button button-secondary" href="{{ route('marketing.app') }}">Admin App</a>
        </nav>
    </div>
</header>
