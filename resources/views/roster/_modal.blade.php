
<button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#rosterModal">
    <i class="fas fa-plus"></i> {{ isset($roster) ? 'Edit Roster' : 'Create New Roster' }}
</button>

<div class="modal fade" id="rosterModal" tabindex="-1" aria-labelledby="rosterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rosterModalLabel">{{ isset($roster) ? 'Edit Roster' : 'Create Roster' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form id="rostersForm" method="post">
                    @csrf
                    @if (isset($roster))
                        <input type="hidden" name="roster_slug" id="roster_slug" value="{{ $roster->slug }}">
                    @endif

                    <div class="mb-3">
                        <label for="roster_name" class="form-label">Roster Name</label>
                        <input type="text" class="form-control" id="roster_name" name="name" required
                               placeholder="e.g. May-June 2025 Roster"
                               value="{{ isset($roster) ? $roster->name : old('name') }}">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control date-picker" id="start_date" name="start_date" required
                                   value="{{ isset($roster) ? $roster->start_date->format('Y-m-d') : old('start_date') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control date-picker" id="end_date" name="end_date" required
                                   value="{{ isset($roster) ? $roster->end_date->format('Y-m-d') : old('end_date') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="draft" {{ isset($roster) && $roster->status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ isset($roster) && $roster->status === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="closed" {{ isset($roster) && $roster->status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>

                    <h6 class="mt-4 mb-3">Assignments</h6>
                    <div id="assignmentsContainer">

                        @include('partials._assignment_fields', ['roster' => $roster ?? null])
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <button type="button" class="btn btn-outline-secondary" id="addAssignment">
                            <i class="fas fa-plus"></i> Add Assignment
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitButton">
                            <i class="bi bi-check-circle"></i> {{ isset($roster) ? 'Update Roster' : 'Save Roster' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    $(document).ready(function () {
        $(".date-picker").flatpickr({ dateFormat: "Y-m-d" });

        let assignmentIndex = {{ isset($roster) ? $roster->assignments->count() : 1 }};

        $('#addAssignment').click(function () {
            const newRow = $('.assignment-row:first').clone();
            newRow.find('select, input, textarea').each(function () {
                const name = $(this).attr('name').replace(/\[\d+\]/, `[${assignmentIndex}]`);
                $(this).attr('name', name).val('');
            });
            newRow.find('.date-picker').flatpickr({ dateFormat: "Y-m-d" });
            $('#assignmentsContainer').append(newRow);
            assignmentIndex++;
        });

        $(document).on('click', '.remove-assignment', function () {
            if ($('.assignment-row').length > 1) {
                $(this).closest('.assignment-row').remove();
            } else {
                Swal.fire('Warning', 'At least one assignment is required.', 'warning');
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
    .assignment-row {
        transition: background-color 0.3s;
    }

    .assignment-row:hover {
        background-color: #f8f9fa;
    }

    .form-select,
    .form-control {
        border-radius: 0.375rem;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
    }
</style>
@endpush
