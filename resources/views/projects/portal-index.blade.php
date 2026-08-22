<x-app-layout :title="$page">
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $page }}</h5>
                    <p class="text-muted small mb-0">Projects you manage or are a member of.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Dates</th>
                                    <th>Tasks</th>
                                    <th style="width:120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                <tr>
                                    <td>{{ $project->name }}</td>
                                    <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span></td>
                                    <td>
                                        @if($project->start_date || $project->end_date)
                                            {{ optional($project->start_date)->format('d M Y') ?? '—' }} - {{ optional($project->end_date)->format('d M Y') ?? '—' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $project->tasks_count }}</td>
                                    <td>
                                        <a href="{{ route('myaccount.projects.board', [$business->slug, $project->id]) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-kanban me-1"></i> Open Board
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">You are not on any projects yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
