<div class="app-sidebar" id="sidebar">
    <div class="main-sidebar-header">
        <a href="{{ route('myaccount.index', $currentBusiness->slug) }}" class="header-logo">
            <img class="main-logo" src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}">
            <img class="dark-logo" src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}">
        </a>
    </div>
    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="sidebar-left" id="sidebar-left"></div>
            <ul class="main-menu" style="padding-top: 70px">
                <li class="sidebar__menu-category"><span class="category-name">Main</span></li>

                @include('layouts.partials.switch-role')

                <li class="slide {{ request()->routeIs('myaccount.index') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.index', $currentBusiness->slug) }}" class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-home"></i></div>
                        <span class="sidebar__menu-label">Dashboard</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.profile') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.profile', $currentBusiness->slug) }}" class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-user"></i></div>
                        <span class="sidebar__menu-label">Profile Data</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.leave.requests.create') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.leave.requests.create', $currentBusiness->slug) }}"
                        class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-calendar-plus"></i></div>
                        <span class="sidebar__menu-label">Request a Leave</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.leave.requests.index') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.leave.requests.index', $currentBusiness->slug) }}"
                        class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-calendar-check"></i></div>
                        <span class="sidebar__menu-label">My Leave Requests</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.attendances.clock-in-out.index') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.attendances.clock-in-out.index', $currentBusiness->slug) }}"
                        class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-clock"></i></div>
                        <span class="sidebar__menu-label">Attendance</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.p9') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.p9', $currentBusiness->slug) }}" class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-file-alt"></i></div>
                        <span class="sidebar__menu-label">P9 Forms</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.payslips') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.payslips', $currentBusiness->slug) }}" class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                        <span class="sidebar__menu-label">Payment Slips</span>
                    </a>
                </li>

                <li class="sidebar__menu-category"><span class="category-name">Settings</span></li>

                <li class="slide {{ request()->routeIs('myaccount.account.settings') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.account.settings', $currentBusiness->slug) }}"
                        class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-user-cog"></i></div>
                        <span class="sidebar__menu-label">Account Settings</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.notifications') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.notifications', $currentBusiness->slug) }}" class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-bell"></i></div>
                        <span class="sidebar__menu-label">Notifications</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-right" id="sidebar-right"></div>
        </nav>
    </div>
</div>

<div class="app__offcanvas-overlay"></div>
