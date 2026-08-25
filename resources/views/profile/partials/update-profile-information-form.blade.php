<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title">{{ __('Profile Information') }}</h5>
        <p>
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </div>
    <div class="card-body mb-0">
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>

        <form method="post" id="profileForm">
            @csrf

            <div class="form-group">
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" type="text" class="form-control" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            </div>

            <div class="form-group">
                <x-input-label for="phone0" :value="__('Phone')" />
                <input id="phone0" name="phone" type="text" class="phone-input-control" :value="old('phone', $user->phone)" required autofocus autocomplete="phone" />
                <x-text-input id="code0" name="code" type="text" hidden :value="old('name', $user->code)" required autofocus autocomplete="code" />
                <x-text-input id="country0" name="country" type="text" hidden :value="old('name', $user->country)" required autofocus autocomplete="country" />
            </div>

            <div class="form-group">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" name="email" type="email" class="form-control" :value="old('email', $user->email)" required autocomplete="username" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-3">
                        <p class="text-danger">
                            {{ __('Your email address is unverified.') }}
                        </p>

                        <button onclick="resendVerification(this)" type="button" class="btn btn-sm btn-secondary">
                            <i class="fa-solid fa-paper-plane me-2"></i> {{ __('Click here to re-send the verification email.') }}
                        </button>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-success">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="">
                <x-primary-button onclick="updateProfile(this)" class="w-100"> <i class="bi bi-check-circle me-2"></i> {{ __('Update Profile') }}</x-primary-button>
            </div>
        </form>

    </div>
</div>
