<x-app-layout title="{{ $campaign->has_survey ? 'Edit Survey for' : 'Create Survey for' }} {{ $campaign->name }}">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-9 col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center justify-content-between text-dark">
                        <h5 class="mb-0">{{ $campaign->has_survey ? 'Edit Survey for' : 'Create Survey for' }}
                            {{ $campaign->name }}
                        </h5>
                        <a href="{{ route('business.crm.campaigns.view', ['business' => $currentBusiness->slug, 'campaign' => $campaign->id]) }}"
                            class="btn btn-secondary btn-sm">Back to Campaign</a>
                    </div>
                    <div class="card-body">
                        <form id="surveyCreatorForm"
                            data-action="{{ route('business.crm.campaigns.surveys.store', ['business' => $currentBusiness->slug, 'campaign' => $campaign->id]) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Survey Fields</label>
                                <div id="fieldsContainer" class="border p-3 rounded">

                                    <div id="noFieldsMessage" class="text-muted" style="display: none;">
                                        No survey fields defined. Add a new field below.
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Add New Field</label>
                                    <div class="input-group">
                                        <select class="form-select" id="newFieldType">
                                            <option value="text">Text</option>
                                            <option value="textarea">Textarea</option>
                                            <option value="star">Star Rating</option>
                                            <option value="multiple_choice">Multiple Choice</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-primary" id="addField">Add
                                            Field</button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" id="submitButton">
                                {{ $campaign->has_survey ? "Update Survey" : "Save Survey" }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
    $surveyConfig = $campaign->survey_config ?? [];
    logger()->debug('Survey Config for Campaign ' . $campaign->id, ['survey_config' => $surveyConfig]);
@endphp

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        const form = $('#surveyCreatorForm');
        const fieldsContainer = $('#fieldsContainer');
        const noFieldsMessage = $('#noFieldsMessage');
        let fieldCounter = 0;

        // Load existing survey configuration if available
        const existingConfig = @json($surveyConfig);
        console.log('Existing Config:', existingConfig);

        // Convert config to array of fields
        let fieldsArray = [];
        if (existingConfig.fields) {
            if (Array.isArray(existingConfig.fields)) {
                fieldsArray = existingConfig.fields;
            } else {
                fieldsArray = Object.values(existingConfig.fields);
            }
        } else if (Object.keys(existingConfig).length > 0 && !existingConfig.fields) {
            fieldsArray = Object.values(existingConfig);
        }
        console.log('Fields Array:', fieldsArray);

        if (!fieldsArray || fieldsArray.length === 0) {
            console.warn('No valid survey fields found in configuration.');
            noFieldsMessage.show();
        } else {
            noFieldsMessage.hide();
            fieldsArray.forEach(field => {
                if (!field.id || !field.type || !field.label) {
                    console.error('Invalid field data:', field);
                    return;
                }
                const fieldId = field.id;
                fieldCounter = Math.max(fieldCounter, parseInt(fieldId.replace('field_', '')) + 1);
                let fieldHtml = `
                    <div class="field-item mb-3 p-3 border rounded" data-id="${fieldId}">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6>${field.type.charAt(0).toUpperCase() + field.type.slice(1)}</h6>
                            <button type="button" class="btn btn-sm btn-danger remove-field">Remove</button>
                        </div>
                        <div class="mt-2">
                            <label class="form-label">Label</label>
                            <input type="text" class="form-control label-input" name="fields[${fieldId}][label]" value="${field.label.replace(/"/g, '&quot;')}" required>
                            <input type="hidden" name="fields[${fieldId}][type]" value="${field.type}">
                            <input type="hidden" name="fields[${fieldId}][id]" value="${fieldId}">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" name="fields[${fieldId}][required]" value="1" ${field.required ? 'checked' : ''}>
                                <label class="form-check-label">Required</label>
                            </div>
                `;

                if (field.type === 'multiple_choice' && field.options && Array.isArray(field.options) &&
                    field.options.length > 0) {
                    fieldHtml += `
                        <div class="mt-2 options-container">
                            <label class="form-label">Options</label>
                            <div class="options-list">
                    `;
                    field.options.forEach((option, index) => {
                        fieldHtml += `
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="fields[${fieldId}][options][]" value="${option.replace(/"/g, '&quot;')}" placeholder="Option ${index + 1}" required>
                                <button type="button" class="btn btn-sm btn-danger remove-option">Remove</button>
                            </div>
                        `;
                    });
                    fieldHtml += `
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary add-option mt-2">Add Option</button>
                        </div>
                    `;
                }

                fieldHtml += `</div></div>`;
                fieldsContainer.append(fieldHtml);
            });
        }

        $('#addField').on('click', function() {
            const type = $('#newFieldType').val();
            const fieldId = `field_${fieldCounter++}`;
            let fieldHtml = `
                <div class="field-item mb-3 p-3 border rounded" data-id="${fieldId}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6>${type.charAt(0).toUpperCase() + type.slice(1)}</h6>
                        <button type="button" class="btn btn-sm btn-danger remove-field">Remove</button>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Label</label>
                        <input type="text" class="form-control label-input" name="fields[${fieldId}][label]" required>
                        <input type="hidden" name="fields[${fieldId}][type]" value="${type}">
                        <input type="hidden" name="fields[${fieldId}][id]" value="${fieldId}">
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" name="fields[${fieldId}][required]" value="1">
                            <label class="form-check-label">Required</label>
                        </div>
            `;

            if (type === 'multiple_choice') {
                fieldHtml += `
                    <div class="mt-2 options-container">
                        <label class="form-label">Options</label>
                        <div class="options-list">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="fields[${fieldId}][options][]" placeholder="Option 1" required>
                                <button type="button" class="btn btn-sm btn-danger remove-option">Remove</button>
                            </div>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="fields[${fieldId}][options][]" placeholder="Option 2" required>
                                <button type="button" class="btn btn-sm btn-danger remove-option">Remove</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary add-option mt-2">Add Option</button>
                    </div>
                `;
            }

            fieldHtml += `</div></div>`;
            fieldsContainer.append(fieldHtml);
            noFieldsMessage.hide();
        });

        fieldsContainer.on('click', '.remove-field', function() {
            $(this).closest('.field-item').remove();
            if (fieldsContainer.find('.field-item').length === 0) {
                noFieldsMessage.show();
            }
        });

        fieldsContainer.on('click', '.add-option', function() {
            const fieldId = $(this).closest('.field-item').data('id');
            const optionHtml = `
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="fields[${fieldId}][options][]" placeholder="Option" required>
                    <button type="button" class="btn btn-sm btn-danger remove-option">Remove</button>
                </div>
            `;
            $(this).siblings('.options-list').append(optionHtml);
        });

        fieldsContainer.on('click', '.remove-option', function() {
            const optionsList = $(this).closest('.options-list');
            if (optionsList.find('.input-group').length > 2) {
                $(this).closest('.input-group').remove();
            } else {
                Swal.fire('Error', 'Multiple-choice questions must have at least 2 options.', 'error');
            }
        });

        form.on('submit', function(e) {
            e.preventDefault();
            const submitButton = $('#submitButton');
            submitButton.prop('disabled', true).text('Saving...');

            // Validate multiple-choice options and check for duplicate labels
            let isValid = true;
            const labels = [];
            $('.field-item').each(function() {
                const fieldId = $(this).data('id');
                const label = $(this).find(`input[name="fields[${fieldId}][label]"]`).val()
                    .trim();

                if (labels.includes(label)) {
                    isValid = false;
                    $(this).find('.label-input').addClass('is-invalid');
                    Swal.fire('Error',
                        `The label "${label}" is already used. Please choose a unique label.`,
                        'error');
                    return false;
                }
                labels.push(label);

                if ($(this).find('input[name*="[type]"]').val() === 'multiple_choice') {
                    const options = $(this).find('.options-list input');
                    if (options.length < 2) {
                        isValid = false;
                        Swal.fire('Error',
                            'Multiple-choice questions must have at least 2 options.',
                            'error');
                        return false;
                    }
                    options.each(function() {
                        if (!$(this).val().trim()) {
                            isValid = false;
                            $(this).addClass('is-invalid');
                        }
                    });
                }
            });

            if (!isValid) {
                submitButton.prop('disabled', false).text(
                    '{{ $campaign->has_survey ? "Update Survey" : "Save Survey" }}');
                return;
            }

            $.ajax({
                url: form.data('action'),
                method: 'POST',
                data: form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('input[name="_token"]').val()
                },
                success: function(response) {
                    const businessSlug = "{{ $currentBusiness->slug }}";
                    const campaignId = "{{ $campaign->id }}";
                    const redirectUrl =
                        `/business/${businessSlug}/crm/campaigns/${campaignId}`;
                    Swal.fire('Success', 'Survey saved successfully.', 'success').then(
                        () => {
                            window.location.href = redirectUrl;
                        });
                },
                error: function(xhr) {
                    submitButton.prop('disabled', false).text(
                        '{{ $campaign->has_survey ? "Update Survey" : "Save Survey" }}');
                    Swal.fire('Error', xhr.responseJSON?.message ||
                        'Failed to save survey.', 'error');
                }
            });
        });
    });
    </script>
</x-app-layout>