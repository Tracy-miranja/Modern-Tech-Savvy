<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Notifications</h5>
                        @if ($notifications->contains(fn ($n) => is_null($n->read_at)))
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="markAllReadBtn">
                                Mark all as read
                            </button>
                        @endif
                    </div>

                    @if ($notifications->isEmpty())
                        <p class="text-muted mb-0">You have no notifications yet.</p>
                    @else
                        <div class="list-group" id="notificationsList">
                            @foreach ($notifications as $notification)
                                @php
                                    $isUnread = is_null($notification->read_at);
                                    $data = $notification->data ?? [];
                                    $message = $data['message'] ?? (str($notification->type)->afterLast('\\')->headline()->toString());
                                    $link = isset($data['reference_number'])
                                        ? route('myaccount.leave.show', ['business' => $business->slug, 'leave' => $data['reference_number']])
                                        : null;
@endphp
                                <div class="list-group-item {{ $isUnread ? 'bg-light' : '' }} d-flex justify-content-between align-items-start"
                                     data-notification-id="{{ $notification->id }}">
                                    <div>
                                        <div class="{{ $isUnread ? 'fw-semibold' : '' }}">
                                            {{ $message }}
                                        </div>
                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                        @if ($link)
                                            <div><a href="{{ $link }}" class="small">View details</a></div>
                                        @endif
                                    </div>
                                    @if ($isUnread)
                                        <button type="button" class="btn btn-link btn-sm mark-read-btn">Mark as read</button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const readUrlTemplate = @json(route('myaccount.notifications.read', ['business' => $business->slug, 'notification' => '__ID__']));
        const readAllUrl = @json(route('myaccount.notifications.read-all', ['business' => $business->slug]));

        document.querySelectorAll('.mark-read-btn').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const item = btn.closest('[data-notification-id]');
                const id = item.dataset.notificationId;
                const url = readUrlTemplate.replace('__ID__', id);

                await fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                });

                item.classList.remove('bg-light');
                item.querySelector('.fw-semibold')?.classList.remove('fw-semibold');
                btn.remove();
            });
        });

        document.getElementById('markAllReadBtn')?.addEventListener('click', async function () {
            await fetch(readAllUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            window.location.reload();
        });
    })();
    </script>
</x-app-layout>
