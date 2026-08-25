
<div class="card">
    <div class="card-body mb-0">
        <h5>{{ __('Delete Account') }}</h5>

        <p class="mb-5">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>

        <x-danger-button data-bs-target="#deleteAccountModal" data-bs-toggle="modal"> <i class="bi bi-delete "me-2></i> {{ __('Delete Account') }}</x-danger-button>
    </div>
</div>

@include('modals.delete-account')
