<x-app-layout title="Edit Survey">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4">
                        <h4 class="fw-semibold text-dark mb-4">Edit Survey</h4>
                        <form id="surveyForm" class="needs-validation" novalidate
                            action="{{ route('surveys.update', $survey->id) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="title" class="form-label fw-medium text-dark">Survey Title</label>
                                    <input type="text" name="title" id="title" class="form-control"
                                        value="{{ $survey->title }}" required>
                                    @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label fw-medium text-dark">Description
                                        (Optional)</label>
                                    <textarea name="description" id="description" class="form-control"
                                        rows="4">{{ $survey->description }}</textarea>
                                    @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="access_type" class="form-label fw-medium text-dark">Access Type</label>
                                    <select name="access_type" id="access_type" class="form-select" required>
                                        <option value="public" {{ $survey->access_type == 'public' ? 'selected' : '' }}>
                                            Public</option>
                                        <option value="private"
                                            {{ $survey->access_type == 'private' ? 'selected' : '' }}>Private</option>
                                        <option value="employee_only"
                                            {{ $survey->access_type == 'employee_only' ? 'selected' : '' }}>Employee
                                            Only</option>
                                        <option value="client_only"
                                            {{ $survey->access_type == 'client_only' ? 'selected' : '' }}>Client Only
                                        </option>
                                    </select>
                                    @error('access_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="status" class="form-label fw-medium text-dark">Status</label>
                                    <select name="status" id="status" class="form-select" required>
                                        <option value="draft" {{ $survey->status == 'draft' ? 'selected' : '' }}>Draft
                                        </option>
                                        <option value="active" {{ $survey->status == 'active' ? 'selected' : '' }}>
                                            Active</option>
                                        <option value="closed" {{ $survey->status == 'closed' ? 'selected' : '' }}>
                                            Closed</option>
                                    </select>
                                    @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="start_date" class="form-label fw-medium text-dark">Start Date
                                        (Optional)</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control"
                                        value="{{ optional($survey->start_date)->format('Y-m-d') }}">
                                    @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="end_date" class="form-label fw-medium text-dark">End Date
                                        (Optional)</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control"
                                        value="{{ optional($survey->end_date)->format('Y-m-d') }}">
                                    @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div id="questionsContainer" class="mt-4">
                                <h5 class="fw-semibold text-dark">Questions</h5>
                                @foreach ($survey->questions as $index => $question)
                                <div class="question-block mb-3">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-medium text-dark">Question Text</label>
                                            <input type="text" name="questions[{{ $index }}][question_text]"
                                                class="form-control" value="{{ $question->question_text }}" required>
                                            <input type="hidden" name="questions[{{ $index }}][id]"
                                                value="{{ $question->id }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-medium text-dark">Question Type</label>
                                            <select name="questions[{{ $index }}][question_type]"
                                                class="form-select question-type" required>
                                                <option value="text"
                                                    {{ $question->question_type == 'text' ? 'selected' : '' }}>Text
                                                </option>
                                                <option value="textarea"
                                                    {{ $question->question_type == 'textarea' ? 'selected' : '' }}>
                                                    Textarea</option>
                                                <option value="multiple_choice"
                                                    {{ $question->question_type == 'multiple_choice' ? 'selected' : '' }}>
                                                    Multiple Choice</option>
                                                <option value="rating"
                                                    {{ $question->question_type == 'rating' ? 'selected' : '' }}>Rating
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-check-label">
                                                <input type="checkbox" name="questions[{{ $index }}][is_required]"
                                                    value="1" class="form-check-input"
                                                    {{ $question->is_required ? 'checked' : '' }}>
                                                Required
                                            </label>
                                        </div>
                                        <div class="col-12 options-container"
                                            style="display: {{ $question->question_type == 'multiple_choice' ? 'block' : 'none' }};">
                                            <label class="form-label fw-medium text-dark">Options</label>
                                            <div class="options-list">
                                                @foreach ($question->options as $optIndex => $option)
                                                <div class="input-group mb-2">
                                                    <input type="text"
                                                        name="questions[{{ $index }}][options][{{ $optIndex }}][text]"
                                                        class="form-control" value="{{ $option->option_text }}">
                                                    <input type="hidden"
                                                        name="questions[{{ $index }}][options][{{ $optIndex }}][id]"
                                                        value="{{ $option->id }}">
                                                    <button type="button"
                                                        class="btn btn-outline-danger remove-option">Remove</button>
                                                </div>
                                                @endforeach
                                            </div>
                                            <button type="button" class="btn btn-outline-primary add-option">Add
                                                Option</button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger remove-question mt-2">Remove
                                        Question</button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-primary add-question mt-3">Add
                                Question</button>

                            <div class="mt-4">
                                <button type="button" class="btn btn-primary btn-modern" onclick="saveSurvey(this)">
                                    <i class="fa fa-save me-2"></i> Update Survey
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('surveyForm');
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    form.classList.add('was-validated');
                } else {
                    saveSurvey(form.querySelector('button[type="button"]'));
                }
            });

            // Add question
            document.querySelector('.add-question').addEventListener('click', () => {
                const questionsContainer = document.getElementById('questionsContainer');
                const questionCount = questionsContainer.querySelectorAll('.question-block').length;
                const questionBlock = document.createElement('div');
                questionBlock.className = 'question-block mb-3';
                questionBlock.innerHTML = `
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-medium text-dark">Question Text</label>
                                <input type="text" name="questions[${questionCount}][question_text]" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium text-dark">Question Type</label>
                                <select name="questions[${questionCount}][question_type]" class="form-select question-type" required>
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="multiple_choice">Multiple Choice</option>
                                    <option value="rating">Rating</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-check-label">
                                    <input type="checkbox" name="questions[${questionCount}][is_required]" value="1" class="form-check-input">
                                    Required
                                </label>
                            </div>
                            <div class="col-12 options-container" style="display: none;">
                                <label class="form-label fw-medium text-dark">Options</label>
                                <div class="options-list">
                                    <div class="input-group mb-2">
                                        <input type="text" name="questions[${questionCount}][options][0][text]" class="form-control">
                                        <button type="button" class="btn btn-outline-danger remove-option">Remove</button>
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" name="questions[${questionCount}][options][1][text]" class="form-control">
                                        <button type="button" class="btn btn-outline-danger remove-option">Remove</button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary add-option">Add Option</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-danger remove-question mt-2">Remove Question</button>
                    `;
                questionsContainer.appendChild(questionBlock);
                attachEventListeners(questionBlock);
            });

            // Attach event listeners to existing blocks
            document.querySelectorAll('.question-block').forEach(block => attachEventListeners(block));

            function attachEventListeners(block) {
                // Toggle options visibility based on question type
                const questionTypeSelect = block.querySelector('.question-type');
                const optionsContainer = block.querySelector('.options-container');
                questionTypeSelect.addEventListener('change', () => {
                    optionsContainer.style.display = questionTypeSelect.value === 'multiple_choice' ?
                        'block' : 'none';
                });

                // Add option
                block.querySelector('.add-option')?.addEventListener('click', () => {
                    const optionsList = block.querySelector('.options-list');
                    const questionIndex = block.querySelector('input[name*="[question_text]"]').name.match(
                        /questions\[(\d+)\]/)[1];
                    const optionCount = optionsList.querySelectorAll('.input-group').length;
                    const optionInput = document.createElement('div');
                    optionInput.className = 'input-group mb-2';
                    optionInput.innerHTML = `
                            <input type="text" name="questions[${questionIndex}][options][${optionCount}][text]" class="form-control">
                            <button type="button" class="btn btn-outline-danger remove-option">Remove</button>
                        `;
                    optionsList.appendChild(optionInput);
                    optionInput.querySelector('.remove-option').addEventListener('click', () => {
                        if (optionsList.querySelectorAll('.input-group').length > 2) {
                            optionInput.remove();
                        }
                    });
                });

                // Remove option
                block.querySelectorAll('.remove-option').forEach(button => {
                    button.addEventListener('click', () => {
                        const optionsList = block.querySelector('.options-list');
                        if (optionsList.querySelectorAll('.input-group').length > 2) {
                            button.closest('.input-group').remove();
                        }
                    });
                });

                // Remove question
                block.querySelector('.remove-question')?.addEventListener('click', () => {
                    if (document.querySelectorAll('.question-block').length > 1) {
                        block.remove();
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>