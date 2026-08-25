<div class="row g-2">
    @forelse ($departments as $department)
        <div class="col-md-4">
            <x-department-card :department="$department" />
        </div>
    @empty

        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center" style="height: 200px; display: flex; justify-content: center; align-items: center;">
                    <h5 class="card-text">No departments set up at the moment.</h5>
                </div>
            </div>
        </div>
    @endforelse
</div>
