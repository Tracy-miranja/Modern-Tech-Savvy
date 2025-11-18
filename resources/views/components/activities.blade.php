<ul class="timeline">
    @forelse($logs as $log)
        <li class="timeline__item d-flex gap-10">
            <div class="timeline__icon">
             <span class="d-flex align-items-center justify-content-center rounded-circle"
          style="width: 40px; height: 40px; background-color: #F4A12E; color: white;">
        <i class="fa-light fa-box"></i>
    </span>
            </div>
            <div class="timeline__content w-100">
                <div class="d-flex flex-wrap gap-10 align-items-center justify-content-between">
                    <h5 class="small">{{ $log->title }}</h5>
                    <span class="badge text-white px-3 py-2" style="background-color: #3AAE8D;">
    {{ $log->created_at->diffForHumans() }}
</span>

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
