<x-app-layout title="Survey Responses">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card shadow-sm mb-5 border-0 rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-semibold text-dark">Responses for: {{ $survey->title }}</h4>
                            <div>
                                <a href="{{ route('business.surveys.export', [$businessSlug, $survey->id]) }}"
                                    class="btn btn-success btn-modern">
                                    <i class="fa fa-download me-2"></i> Export Responses
                                </a>
                                <a href="{{ route('business.surveys.index', $businessSlug) }}"
                                    class="btn btn-secondary btn-modern">
                                    <i class="fa fa-arrow-left me-2"></i> Back to Surveys
                                </a>
                            </div>
                        </div>

                        @if($survey->responses->isEmpty())
                        <div class="alert alert-info text-center">
                            <i class="fa fa-info-circle me-2"></i> No responses have been submitted yet.
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-dark fw-semibold">Response ID</th>
                                        <th scope="col" class="text-dark fw-semibold">Submitted At</th>
                                        <th scope="col" class="text-dark fw-semibold">User</th>
                                        <th scope="col" class="text-dark fw-semibold">Anonymous</th>
                                        @foreach($survey->questions as $question)
                                        <th scope="col" class="text-dark fw-semibold">{{ $question->question_text }}
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($survey->responses as $response)
                                    <tr>
                                        <td>{{ $response->id }}</td>
                                        <td>{{ $response->submitted_at->format('Y-m-d H:i:s') }}</td>
                                        <td>{{ $response->user_id ? ($response->is_anonymous ? 'Anonymous' : $response->user->name) : 'Guest' }}
                                        </td>
                                        <td>{{ $response->is_anonymous ? 'Yes' : 'No' }}</td>
                                        @foreach($survey->questions as $question)
                                        <td>
                                            @php
                                            $answer = $response->answers->where('survey_question_id',
                                            $question->id)->first();
@endphp
                                            {{ $answer ? ($answer->option ? $answer->option->option_text : ($answer->answer_text ?? 'N/A')) : 'N/A' }}
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .btn-modern {
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
    @endpush
</x-app-layout>