<li class="slide has-sub">
    <a href="javascript:void(0);" class="sidebar__menu-item" id="roleSwitchDropdown">
        <i class="fa-solid fa-angle-down side-menu__angle"></i>
        <div class="side-menu__icon"><i class="fa-solid fa-exchange-alt"></i></div>
        <span class="sidebar__menu-label">Switch Account</span>
    </a>
    <ul class="sidebar-menu child1">
        @foreach(auth()->user()->roles as $role)
            @php $isActive = session('active_role') === $role->name ? 'active' : '';@endphp
            <li class="slide">
                <a href="javascript:void(0);" class="sidebar__menu-item switch-role {{ $isActive }}" data-role="{{ $role->name }}">
                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                    @if($isActive) <span class="badge bg-primary me-2">Active</span> @endif
                </a>
            </li>
        @endforeach
    </ul>
</li>

<form id="switchRoleForm" action="{{ route('switch.role') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="role" id="selectedRole">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.switch-role').forEach(function(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault();

            const role = this.getAttribute('data-role');
            if (!role) {
                console.error('No role specified');
                alert('No role specified');
                return;
            }

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Switching...';
            this.style.pointerEvents = 'none';

            fetch('{{ route("switch.role") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ role: role })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.error || 'An error occurred while switching roles');
                    this.innerHTML = originalText;
                    this.style.pointerEvents = 'auto';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
                this.innerHTML = originalText;
                this.style.pointerEvents = 'auto';
            });
        });
    });
});
</script>
