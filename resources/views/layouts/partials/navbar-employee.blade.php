<div class="app-sidebar" id="sidebar">
    <div class="main-sidebar-header" style="height: 52px; padding: 6px 16px; overflow: hidden;">
        <a href="{{ route('myaccount.index', $currentBusiness->slug) }}" class="header-logo"
           style="height: 100%; display: flex; align-items: center; justify-content: center;">
            <img class="main-logo" src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}" style="max-height: 85%; width: auto; object-fit: contain;">
            <img class="dark-logo" src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}" style="max-height: 85%; width: auto; object-fit: contain;">
        </a>
    </div>
    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="sidebar-left" id="sidebar-left"></div>
            <ul class="main-menu" style="padding-top: 56px">
                <li class="sidebar__menu-category"><span class="category-name">Main</span></li>

                @include('layouts.partials.switch-role')

                <li class="slide {{ request()->routeIs('myaccount.index') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.index', $currentBusiness->slug) }}" class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-home"></i></div>
                        <span class="sidebar__menu-label">Dashboard</span>
                    </a>
                </li>

                {{-- Disabled for now - not ready for employees to use yet. --}}
                <li class="slide">
                    <a href="javascript:void(0)" class="sidebar__menu-item disabled" aria-disabled="true"
                        style="opacity: .5; pointer-events: none; cursor: not-allowed;" tabindex="-1">
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

                <li class="slide {{ request()->routeIs('myaccount.delegations.index') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.delegations.index', $currentBusiness->slug) }}"
                        class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-people-arrows"></i></div>
                        <span class="sidebar__menu-label">Cover Requests</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.my-team') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.my-team', $currentBusiness->slug) }}"
                        class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-sitemap"></i></div>
                        <span class="sidebar__menu-label">My Team</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.leave.calendar') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.leave.calendar', $currentBusiness->slug) }}"
                        class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-calendar-days"></i></div>
                        <span class="sidebar__menu-label">Leave Calendar</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.performance.*') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.performance.index', $currentBusiness->slug) }}"
                        class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-chart-line"></i></div>
                        <span class="sidebar__menu-label">My Performance</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.attendances.*') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.attendances.index', $currentBusiness->slug) }}"
                        class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-clock"></i></div>
                        <span class="sidebar__menu-label">Attendance</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.disciplinary.*') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.disciplinary.index', $currentBusiness->slug) }}" class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <span class="sidebar__menu-label">Disciplinary</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.projects.*') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.projects.index', $currentBusiness->slug) }}" class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-diagram-project"></i></div>
                        <span class="sidebar__menu-label">My Projects</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.assets.index') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.assets.index', $currentBusiness->slug) }}" class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-laptop"></i></div>
                        <span class="sidebar__menu-label">My Assets</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.learning.index') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.learning.index', $currentBusiness->slug) }}" class="sidebar__menu-item">
                        <div class="side-menu__icon"><i class="fa-solid fa-graduation-cap"></i></div>
                        <span class="sidebar__menu-label">My Learning</span>
                    </a>
                </li>

                <li class="slide {{ request()->routeIs('myaccount.p9.index') ? 'active' : '' }}">
                    <a href="{{ route('myaccount.p9.index', $currentBusiness->slug) }}" class="sidebar__menu-item">
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
                        @php $unreadCount = auth()->user()?->unreadNotifications->count() ?? 0; @endphp
                        @if ($unreadCount > 0)
                            <span class="badge bg-danger rounded-pill ms-1">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>
            <div class="sidebar-right" id="sidebar-right"></div>
        </nav>
    </div>
</div>

<div class="app__offcanvas-overlay"></div>