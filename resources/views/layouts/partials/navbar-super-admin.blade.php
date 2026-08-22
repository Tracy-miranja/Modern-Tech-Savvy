@php
    use App\Models\Business;
    $business = Business::findBySlug(session('active_business_slug'));
@endphp
<div class="app-sidebar" id="sidebar">
    <div class="main-sidebar-header" style="height: 52px; padding: 6px 16px; overflow: hidden;">
        <a href="{{ route('business.index', $currentBusiness->slug) }}" class="header-logo"
           style="height: 100%; display: flex; align-items: center; justify-content: center;">
            <img class="main-logo" src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}" style="max-height: 85%; width: auto; object-fit: contain;">
            <img class="dark-logo" src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}" style="max-height: 85%; width: auto; object-fit: contain;">
        </a>
    </div>
    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="sidebar-left" id="sidebar-left"></div>
            <ul class="main-menu" style="padding-top: 4px">
                <li class="sidebar__menu-category"><span class="category-name">Platform</span></li>

                @include('layouts.partials.switch-role')

                <!-- Dashboard -->
                <li class="slide {{ request()->routeIs('business.index') ? 'active' : '' }}">
                    <a href="{{ route('business.index', $currentBusiness->slug) }}"
                        class="sidebar__menu-item {{ request()->routeIs('business.index') ? 'active' : '' }}">
                        <div class="side-menu__icon"><i class="fa-solid fa-home"></i></div>
                        <span class="sidebar__menu-label">Dashboard</span>
                    </a>
                </li>

                <!-- Clients -->
                <li class="slide {{ request()->routeIs('business.clients.*') ? 'active' : '' }}">
                    <a href="{{ route('business.clients.index', $currentBusiness->slug) }}"
                        class="sidebar__menu-item {{ request()->routeIs('business.clients.*') ? 'active' : '' }}">
                        <div class="side-menu__icon"><i class="fa-solid fa-building-user"></i></div>
                        <span class="sidebar__menu-label">Clients</span>
                    </a>
                </li>

                <!-- Platform Admins -->
                <li class="slide {{ request()->routeIs('business.platform-admins.*') ? 'active' : '' }}">
                    <a href="{{ route('business.platform-admins.index', $currentBusiness->slug) }}"
                        class="sidebar__menu-item {{ request()->routeIs('business.platform-admins.*') ? 'active' : '' }}">
                        <div class="side-menu__icon"><i class="fa-solid fa-user-shield"></i></div>
                        <span class="sidebar__menu-label">Platform Admins</span>
                    </a>
                </li>

                <!-- System Health -->
                <li class="slide {{ request()->routeIs('business.system-health.*') ? 'active' : '' }}">
                    <a href="{{ route('business.system-health.index', $currentBusiness->slug) }}"
                        class="sidebar__menu-item {{ request()->routeIs('business.system-health.*') ? 'active' : '' }}">
                        <div class="side-menu__icon"><i class="fa-solid fa-heart-pulse"></i></div>
                        <span class="sidebar__menu-label">System Health</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-right" id="sidebar-right"></div>
        </nav>
    </div>
</div>

<div class="app__offcanvas-overlay"></div>
