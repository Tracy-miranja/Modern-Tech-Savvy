<div class="row g-2">
    @forelse ($shifts as $shift)
        <div class="col-md-4">
            <x-shift-card :shift="$shift" />
        </div>
    @empty

        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center" style="height: 200px; display: flex; justify-content: center; align-items: center;">
                    <h5 class="card-text">No shifts available at the moment.</h5>
                </div>
            </div>
        </div>
    @endforelse
</div>
