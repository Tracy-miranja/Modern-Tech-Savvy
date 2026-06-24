<div class="modal fade" id="scheduleInterviewModal" tabindex="-1" aria-labelledby="scheduleInterviewModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleInterviewModalLabel">Schedule Interview for <span
                        id="applicant_name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleInterviewForm">
                    @csrf
                    <input type="hidden" name="application_id" id="application_id_input">
                    <div class="mb-3">
                        <label for="interview_date" class="form-label">Interview Date</label>
                        <input type="date" class="form-control" name="interview_date" id="interview_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="interview_time" class="form-label">Interview Time</label>
                        <input type="time" class="form-control" name="interview_time" id="interview_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" id="location" required>
                    </div>
                    <div class="mb-3">
                        <label for="interviewer_id" class="form-label">Interviewer</label>
                        <select name="interviewer_id" id="interviewer_id" class="form-control" required>
                            <option value="">-- Select Interviewer --</option>
                            @foreach(\App\Models\User::whereHas('roles', function($q) { $q->where('name',
                            'business-admin'); })->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Interview Type</label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="phone">Phone</option>
                            <option value="video">Video</option>
                            <option value="in-person">In-Person</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="meeting_link" class="form-label">Meeting Link (if applicable)</label>
                        <input type="url" class="form-control" name="meeting_link" id="meeting_link">
                    </div>
                    <button type="button" class="btn btn-primary" onclick="scheduleInterview(this)">Schedule</button>
                </form>
            </div>
        </div>
    </div>
</div>
