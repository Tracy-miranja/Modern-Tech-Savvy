<ul class="timeline">
    @forelse($logs as $log)
        <li class="timeline__item d-flex gap-10">
            <div class="timeline__icon">
                <span>
                    <i class="fa-light fa-box"></i>
                </span>
            </div>
            <div class="timeline__content w-100">
                <div class="d-flex flex-wrap gap-10 align-items-center justify-content-between">
                    <h5 class="small">{{ $log->title }}</h5>
                    <span class="bd-badge bg-success">{{ $log->created_at->diffForHumans() }}</span>
                </div>
                <p>{{ $log->description }}</p>
            </div>
        </li>
    @empty
        <li class="timeline__item d-flex gap-10">
            <div class="timeline__content w-100 text-center">
                <p>No recent activity.</p>
            </div>
        </li>
    @endforelse
</ul>
