<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $campaign->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
    body {
        background-color: #f8f9fa;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .container {
        max-width: 600px;
    }

    .header {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .header img {
        max-height: 50px;
        margin-bottom: 0.75rem;
    }

    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: #004aad;
        color: white;
        border-radius: 10px 10px 0 0;
        padding: 1rem;
        text-align: center;
    }

    .card-body {
        padding: 1.5rem;
    }

    .btn-primary {
        background-color: #004aad;
        border: none;
        border-radius: 5px;
        padding: 0.5rem 1rem;
        transition: background-color 0.3s;
    }

    .btn-primary:hover {
        background-color: #003580;
    }

    .btn-outline-secondary {
        border-color: #6c757d;
        color: #6c757d;
        border-radius: 5px;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }

    .btn-outline-secondary:hover {
        background-color: #f1f3f5;
    }

    .form-label {
        font-weight: 500;
        color: #343a40;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    .form-control,
    .form-control:focus {
        border-radius: 5px;
        border: 1px solid #ced4da;
        font-size: 0.9rem;
        padding: 0.5rem;
    }

    .form-control:focus {
        border-color: #004aad;
        box-shadow: 0 0 0 0.2rem rgba(0, 74, 173, 0.25);
    }

    .alert {
        border-radius: 6px;
        background-color: #e9ecef;
        border: none;
        color: #343a40;
        font-size: 0.9rem;
        padding: 0.75rem;
        margin-bottom: 1rem;
    }

    .mb-3 {
        margin-bottom: 0.75rem !important;
    }

    .error-message {
        color: #dc3545;
        font-size: 0.85rem;
        margin-top: 0.25rem;
        display: none;
    }

    .success-container {
        display: none;
        text-align: center;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .success-container h3 {
        color: #004aad;
        margin-bottom: 1rem;
    }

    .success-container p {
        color: #343a40;
        font-size: 1rem;
    }

    .success-container .countdown {
        font-weight: bold;
        color: rgb(0, 105, 45);
    }

    .star-rating {
        display: flex;
        gap: 0.5rem;
        flex-direction: row-reverse;
        justify-content: flex-start;
        align-items: center;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        font-size: 1.5rem;
        color: #ccc;
        cursor: pointer;
        margin: 0;
        line-height: 1;
    }

    .star-rating input:checked~label,
    .star-rating label:hover,
    .star-rating label:hover~label {
        color: #f5b301;
    }

    .star-rating-container {
        display: flex;
        flex-direction: column;
    }

    @media (max-width: 576px) {
        .card-body {
            padding: 1rem;
        }

        .header img {
            max-height: 40px;
        }

        .form-control {
            font-size: 0.85rem;
        }

        .success-container {
            padding: 1.5rem;
        }

        .star-rating label {
            font-size: 1.2rem;
        }
    }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="header">
            <img src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}">
            <h2 class="fw-bold">{{ $campaign->name }}</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{{ $campaign->name }} Feedback</h4>
                    </div>
                    <div class="card-body" id="formContainer">
                        <div class="alert alert-info text-center" role="alert">
                            Thank you for visiting our {{ $campaign->name }} campaign! Please share your feedback to
                            help us improve our services. Your input is valuable and will take just a moment.
                        </div>
                        <form id="surveyForm"
                            data-action="{{ route('short.link.submit', ['slug' => $shortLink->slug]) }}">
                            @csrf
                            @forelse ($campaign->survey_config['fields'] ?? [] as $field)
                            <div class="mb-3">
                                <label for="{{ $field['id'] }}" class="form-label">
                                    {{ $field['label'] }}
                                    @if ($field['required'])
                                    <span class="text-danger">*</span>
                                    @endif
                                </label>
                                @if ($field['type'] === 'text')
                                <input type="text" class="form-control" id="{{ $field['id'] }}"
                                    name="{{ $field['id'] }}" {{ $field['required'] ? 'required' : '' }}>
                                @elseif ($field['type'] === 'textarea')
                                <textarea class="form-control" id="{{ $field['id'] }}" name="{{ $field['id'] }}"
                                    rows="3" {{ $field['required'] ? 'required' : '' }}></textarea>
                                @elseif ($field['type'] === 'star')
                                <div class="star-rating-container">
                                    <div class="star-rating">
                                        @for ($i = 5; $i >= 1; $i--)
                                        <input type="radio" name="{{ $field['id'] }}" id="{{ $field['id'] }}_{{ $i }}"
                                            value="{{ $i }}" {{ $field['required'] ? 'required' : '' }}>
                                        <label for="{{ $field['id'] }}_{{ $i }}"
                                            title="{{ $i }} star{{ $i > 1 ? 's' : '' }}">★</label>
                                        @endfor
                                    </div>
                                </div>
                                @elseif ($field['type'] === 'multiple_choice')
                                <select class="form-control" id="{{ $field['id'] }}" name="{{ $field['id'] }}"
                                    {{ $field['required'] ? 'required' : '' }}>
                                    <option value="">Select an option</option>
                                    @foreach ($field['options'] ?? [] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                                @endif
                                <div class="error-message" id="{{ $field['id'] }}-error"></div>
                            </div>
                            @empty
                            <p class="text-muted">No survey fields defined.</p>
                            @endforelse
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('short.link.skip', ['slug' => $shortLink->slug]) }}"
                                    class="btn btn-outline-secondary">Skip</a>
                                <button type="submit" class="btn btn-primary" id="submitButton">Submit Feedback</button>
                            </div>
                        </form>
                    </div>
                    <div class="success-container" id="successContainer">
                        <h3>Thank You!</h3>
                        <p>Your feedback for <strong>{{ $campaign->name }}</strong> has been submitted successfully.</p>
                        <p>Redirecting in <span class="countdown">3</span> seconds...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    $(document).ready(function() {
        const form = $('#surveyForm');
        const submitButton = $('#submitButton');
        const formContainer = $('#formContainer');
        const successContainer = $('#successContainer');
        const countdownSpan = successContainer.find('.countdown');

        form.on('submit', function(e) {
            e.preventDefault();
            $('.error-message').hide().text('');
            submitButton.prop('disabled', true).text('Submitting...');

            $.ajax({
                url: form.data('action'),
                method: 'POST',
                data: form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    formContainer.hide();
                    successContainer.show();
                    let countdown = 3;
                    countdownSpan.text(countdown);
                    const interval = setInterval(() => {
                        countdown--;
                        countdownSpan.text(countdown);
                        if (countdown <= 0) {
                            clearInterval(interval);
                            window.location.href = response.redirect_url;
                        }
                    }, 1000);
                },
                error: function(xhr) {
                    submitButton.prop('disabled', false).text('Submit Feedback');
                    let errorMessage = 'An unexpected error occurred. Please try again.';

                    switch (xhr.status) {
                        case 400:
                        case 422:
                            const errors = xhr.responseJSON?.errors || {};
                            $.each(errors, function(key, messages) {
                                $(`#${key}-error`).text(messages[0]).show();
                            });
                            errorMessage = 'Please correct the errors in the form.';
                            break;
                        case 409:
                            errorMessage = xhr.responseJSON?.message ||
                                'You have already submitted feedback for this campaign.';
                            break;
                        case 419:
                            errorMessage =
                                'Session expired. Please refresh the page and try again.';
                            break;
                        case 429:
                            errorMessage = 'Too many attempts. Please try again later.';
                            break;
                        case 403:
                            errorMessage = 'You are not authorized to submit this form.';
                            break;
                        case 500:
                            errorMessage =
                                'Server error. Please contact support if this persists.';
                            break;
                        default:
                            if (!navigator.onLine) {
                                errorMessage =
                                    'No internet connection. Please check your network and try again.';
                            }
                    }

                    toastr.error(errorMessage, 'Error');
                }
            });
        });
    });
    </script>
</body>

</html>
