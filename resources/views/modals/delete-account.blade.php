<div class="modal fade" id="deleteAccountModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"> <i class="fa-solid fa-trash-alt me-2"></i> Delete My Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <form method="post" id="deleteAccountForm">
                    @csrf

                    <h5>{{ __('Are you sure you want to delete your account?') }}</h5>

                    <p class="mt-1">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                    </p>

                    <div class="form-group">
                        <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />
                        <x-text-input id="password" name="password" type="password" placeholder="{{ __('Password') }}"/>
                    </div>

                    <div class="">
                        <x-secondary-button data-bs-dismiss="modal"> {{ __('Cancel') }} </x-secondary-button>
                        <x-danger-button class="ms-3" onclick="deleteAccount(this)"> <i class="fa-solid fa-trash-alt me-2"></i> {{ __('Delete Account') }}</x-danger-button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
