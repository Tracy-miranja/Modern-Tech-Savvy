@php
    use App\Models\Business;
    $business = Business::findBySlug(session('active_business_slug'));
@endphp
<div class="app-sidebar" id="sidebar">
    <div class="main-sidebar-header">
        <a href="{{ route('business.index', $currentBusiness->slug) }}" class="header-logo">
            <img class="main-logo" src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}" style="width: 60px; height: auto;">
        <img class="dark-logo" src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}" style="width: 60px; height: auto;">
        </a>
        <span class="sidebar__menu-label">rest Works</span>
    </div>
    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="sidebar-left" id="sidebar-left"></div>
            <ul class="main-menu" style="padding-top: 10px">
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

                <!-- krest Admins -->
                <li class="slide {{ request()->routeIs('business.platform-admins.*') ? 'active' : '' }}">
                    <a href="{{ route('business.platform-admins.index', $currentBusiness->slug) }}"
                        class="sidebar__menu-item {{ request()->routeIs('business.platform-admins.*') ? 'active' : '' }}">
                        <div class="side-menu__icon"><i class="fa-solid fa-user-shield"></i></div>
                        <span class="sidebar__menu-label">krest Admins</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-right" id="sidebar-right"></div>
        </nav>
    </div>
</div>

<div class="app__offcanvas-overlay"></div>
