<header class="site-header">
    <div class="section-inner nav-bar">
        <a class="brand-link" href="{{ route('marketing.home') }}" aria-label="Janova home">
            <img src="{{ asset('images/janova-serve-pos-light.svg') }}" alt="" width="42" height="42">
            <span class="brand-wordmark">
                Janova Serve POS
                <span>Powered by Janova Technologies</span>
            </span>
        </a>

        <nav class="nav-links" aria-label="Main navigation">
            <a href="{{ route('marketing.home') }}#features">Features</a>
            <a href="{{ route('marketing.home') }}#modes">Modes</a>
            <a href="{{ route('marketing.home') }}#apps">Apps</a>
            <a href="{{ route('marketing.home') }}#pricing">Pricing</a>
            <a href="{{ route('marketing.contact') }}">Contact</a>
            <a class="button button-secondary" href="{{ route('marketing.app') }}">Admin App</a>
        </nav>
    </div>
</header>
