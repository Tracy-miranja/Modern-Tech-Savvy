<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Cover Requests</h5>

                    @if ($delegations->isEmpty())
                        <p class="text-muted mb-0">Nobody has asked you to cover their duties yet.</p>
                    @else
                        <div class="list-group">
                            @foreach ($delegations as $delegation)
                                <div class="list-group-item" data-delegation-id="{{ $delegation->id }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-semibold">
                                                {{ optional($delegation->employee->user)->name ?? 'N/A' }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ optional($delegation->leaveRequest?->start_date)->format('M d, Y') }}
                                                &ndash;
                                                {{ optional($delegation->leaveRequest?->end_date)->format('M d, Y') }}
                                            </div>
                                            @if ($delegation->duties_delegated)
                                                <div class="mt-1 small">{{ $delegation->duties_delegated }}</div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            @if ($delegation->status === 'pending')
                                                <button type="button" class="btn btn-success btn-sm respond-btn" data-action="accept">Accept</button>
                                                <button type="button" class="btn btn-outline-danger btn-sm respond-btn" data-action="decline">Decline</button>
                                            @else
                                                <span class="badge {{ $delegation->status === 'accepted' ? 'bg-success' : 'bg-danger' }}">
                                                    {{ ucfirst($delegation->status) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const urlTemplate = @json(route('myaccount.delegations.accept', ['business' => $business->slug, 'delegation' => '__ID__']));

        document.querySelectorAll('.respond-btn').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const item = btn.closest('[data-delegation-id]');
                const id = item.dataset.delegationId;
                const action = btn.dataset.action; // 'accept' | 'decline'
                const url = urlTemplate.replace('__ID__', id).replace('/accept', `/${action}`);

                try {
                    const resp = await fetch(url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    });
                    const payload = await resp.json();
                    if (!resp.ok) {
                        toastr.error(payload.message || 'Could not respond to this request.');
                        return;
                    }
                    toastr.success(payload.message || 'Response recorded.');
                    window.location.reload();
                } catch (e) {
                    console.error(e);
                    toastr.error('Could not respond to this request.');
                }
            });
        });
    })();
    </script>
</x-app-layout>
