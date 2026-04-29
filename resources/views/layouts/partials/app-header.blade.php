<div class="app__header__area">
    <div class="app__header-inner">
        <div class="app__header-left">
            <div class="">
                <a id="sidebar__active" class="app__header-toggle" href="javascript:void(0)">
                    <div class="bar-icon-2">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </a>
            </div>
            <div class="d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center me-3">
        <img src="{{ $currentBusiness->getImageUrl() }}" style="height: 20px" alt="">
        <h2 class="header__title ms-1">{{ $currentBusiness->company_name }}. </h2>
    </div>

                @if (auth()->user()->hasRole('business-admin, business-hr, admin') && (session('active_role') == 'business-admin' || session('active_role') == 'business-hr' || session('active_role') == 'admin'))

    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            Switch Business
        </a>
        <ul class="dropdown-menu">
            <!-- Back to Admin Button (only show if impersonating) -->
            @if (session('original_business_slug'))
            <li>
                <a class="dropdown-item" href="#"
                   onclick="event.preventDefault(); switchBackToAdmin()">
                    <i class="fa-solid fa-arrow-left"></i> Back to Admin
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            @endif

            @if ($managedBusinesses->isNotEmpty())
            @foreach ($managedBusinesses as $managed_business)
            <li>
                <a class="dropdown-item"
                   href="#"
                   onclick="event.preventDefault(); impersonateBusiness('{{ $managed_business->slug }}')">
                    {{ $managed_business->company_name }}
                </a>
            </li>
            @endforeach
            @else
            <li>
                <a class="dropdown-item" onclick="event.preventDefault()" href="#"> No managed businesses
                    found </a>
            </li>
            @endif
        </ul>
    </li>

    @endif
</div>
        </div>
        <div class="app__header-right">
            <div class="app__herader-input p-relative">
                <input type="search" id="search-field" name="search-field" placeholder="Search Here . . .">
                <button><i class="icon-magnifying-glass"></i></button>
            </div>
            <div class="nav-item p-relative">
                <a id="userportfolio" href="#">
                    <div class="user__portfolio">
                        <div class="user__portfolio-thumb">
                            @php
                            $employee = auth()->user()->employee;
                            $imageUrl = $employee?->getFirstMediaUrl('avatars');
                            @endphp

                            @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="User {{ auth()->user()->name }}"
                                class="rounded-circle border object-fit-cover" style="width: 80px; height: 80px;">
                            @else
                            <div class="user__initials d-flex align-items-center justify-content-center rounded-circle border bg-secondary text-white"
                                style="width: 80px; height: 80px; font-size: 32px;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            @endif
                        </div>

                        <div class="user__content">
                            <h5>{{ auth()->user()->name }}</h5>
                            <span class="status-dot online">online</span>
                        </div>
                    </div>
                </a>
                <div class="user__dropdown">
                    <ul>
                        <li>
                            <a href="{{ route('business.profile.index', $currentBusiness->slug) }}"> <i
                                    class="fa-solid fa-user-circle"></i> Profile</a>
                        </li>
                        <li>
                            <a href="" onclick="event.preventDefault(); logout(this)"> <i
                                    class="fa-solid fa-sign-out-alt"></i> Log Out</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="body__overlay"></div>
