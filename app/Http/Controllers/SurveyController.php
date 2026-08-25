<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Business;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SurveyResponsesExport;

class SurveyController extends Controller
{
    use HandleTransactions;

    public function index(Request $request, $businessSlug)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        session(['active_business_slug' => $businessSlug]);
        $surveys = Survey::where('business_id', $business->id)->get();
        return view('surveys.index', compact('businessSlug', 'surveys'));
    }

    public function create(Request $request, $businessSlug)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        return view('surveys.create', compact('businessSlug'));
    }

    public function edit(Request $request, $businessSlug, $surveyId)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $survey = Survey::where('business_id', $business->id)
            ->where('id', $surveyId)
            ->with('questions.options')
            ->firstOrFail();
        return view('surveys.edit', compact('businessSlug', 'survey'));
    }

    public function show(Request $request, $businessSlug, $surveyId)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $survey = Survey::where('business_id', $business->id)
            ->where('id', $surveyId)
            ->with('questions.options')
            ->firstOrFail();
        return view('surveys.show', compact('businessSlug', 'survey'));
    }

    public function preview(Request $request, $businessSlug, $surveyId)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $survey = Survey::where('business_id', $business->id)
            ->where('id', $surveyId)
            ->with('questions.options')
            ->firstOrFail();
        return view('surveys.public.show', compact('survey', 'businessSlug'));
    }

    public function responses(Request $request, $businessSlug, $surveyId)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $survey = Survey::where('business_id', $business->id)
            ->where('id', $surveyId)
            ->with(['responses.answers', 'questions.options'])
            ->firstOrFail();
        return view('surveys.responses', compact('businessSlug', 'survey'));
    }

    public function export(Request $request, $businessSlug, $surveyId)
    {
        try {
            $business = Business::findBySlug($businessSlug);
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $survey = Survey::where('business_id', $business->id)
                ->where('id', $surveyId)
                ->firstOrFail();

            return Excel::download(new SurveyResponsesExport($survey), "survey_{$survey->id}_responses.xlsx");
        } catch (\Exception $e) {
            Log::error('Failed to export survey responses:', ['error' => $e->getMessage()]);
            return RequestResponse::badRequest('Failed to export responses.', [
                'errors' => [$e->getMessage()]
            ]);
        }
    }

    public function fetch(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'page' => 'nullable|integer|min:1',
            ]);

            $businessSlug = session('active_business_slug');
            if (!$businessSlug) {
                return RequestResponse::badRequest('Business slug not found in session.');
            }

            $business = Business::findBySlug($businessSlug);
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $surveys = Survey::where('business_id', $business->id)->get();
            $surveysTable = view('surveys._table', ['surveys' => $surveys, 'businessSlug' => $business->slug])->render();
            return RequestResponse::ok('Surveys fetched successfully.', [
                'html' => $surveysTable,
                'count' => $surveys->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch surveys:', ['error' => $e->getMessage()]);
            return RequestResponse::badRequest('Failed to fetch surveys.', [
                'errors' => [$e->getMessage()]
            ]);
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'access_type' => 'required|in:public,private,employee_only,client_only',
            'status' => 'required|in:draft,active,closed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:text,textarea,multiple_choice,rating',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.text' => 'required_if:questions.*.question_type,multiple_choice|string',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $request) {
            $business = Business::findBySlug($request->session()->get('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $survey = Survey::create([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'access_type' => $validatedData['access_type'],
                'status' => $validatedData['status'],
                'start_date' => $validatedData['start_date'],
                'end_date' => $validatedData['end_date'],
                'business_id' => $business->id,
                'created_by' => Auth::id(),
            ]);

            foreach ($validatedData['questions'] as $questionData) {
                $question = $survey->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'is_required' => $questionData['is_required'] ?? false,
                ]);

                if ($questionData['question_type'] === 'multiple_choice' && !empty($questionData['options'])) {
                    foreach ($questionData['options'] as $optionData) {
                        $question->options()->create([
                            'option_text' => $optionData['text'],
                        ]);
                    }
                }
            }

            return RequestResponse::created('Survey created successfully.', $survey->id);
        });
    }

    public function update(Request $request, $surveyId)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'access_type' => 'required|in:public,private,employee_only,client_only',
            'status' => 'required|in:draft,active,closed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|exists:survey_questions,id',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:text,textarea,multiple_choice,rating',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.id' => 'nullable|exists:survey_question_options,id',
            'questions.*.options.*.text' => 'required_if:questions.*.question_type,multiple_choice|string',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $surveyId, $request) {
            $business = Business::findBySlug($request->session()->get('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $survey = Survey::where('business_id', $business->id)
                ->where('id', $surveyId)
                ->firstOrFail();

            $survey->update([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'access_type' => $validatedData['access_type'],
                'status' => $validatedData['status'],
                'start_date' => $validatedData['start_date'],
                'end_date' => $validatedData['end_date'],
                'created_by' => Auth::id(),
            ]);

            $existingQuestionIds = [];
            foreach ($validatedData['questions'] as $questionData) {
                if (isset($questionData['id'])) {
                    $question = $survey->questions()->where('id', $questionData['id'])->first();
                    if ($question) {
                        $question->update([
                            'question_text' => $questionData['question_text'],
                            'question_type' => $questionData['question_type'],
                            'is_required' => $questionData['is_required'] ?? false,
                        ]);
                        $existingQuestionIds[] = $question->id;

                        if ($questionData['question_type'] === 'multiple_choice' && !empty($questionData['options'])) {
                            $existingOptionIds = [];
                            foreach ($questionData['options'] as $optionData) {
                                if (isset($optionData['id'])) {
                                    $option = $question->options()->where('id', $optionData['id'])->first();
                                    if ($option) {
                                        $option->update(['option_text' => $optionData['text']]);
                                        $existingOptionIds[] = $option->id;
                                    }
                                } else {
                                    $option = $question->options()->create(['option_text' => $optionData['text']]);
                                    $existingOptionIds[] = $option->id;
                                }
                            }
                            $question->options()->whereNotIn('id', $existingOptionIds)->delete();
                        } else {
                            $question->options()->delete();
                        }
                    }
                } else {
                    $question = $survey->questions()->create([
                        'question_text' => $questionData['question_text'],
                        'question_type' => $questionData['question_type'],
                        'is_required' => $questionData['is_required'] ?? false,
                    ]);
                    $existingQuestionIds[] = $question->id;

                    if ($questionData['question_type'] === 'multiple_choice' && !empty($questionData['options'])) {
                        foreach ($questionData['options'] as $optionData) {
                            $question->options()->create(['option_text' => $optionData['text']]);
                        }
                    }
                }
            }

            $survey->questions()->whereNotIn('id', $existingQuestionIds)->delete();

            return RequestResponse::ok('Survey updated successfully.');
        });
    }

    public function destroy(Request $request, $surveyId)
    {
        $validatedData = $request->validate([
            'id' => 'required|exists:surveys,id',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $surveyId) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $survey = Survey::where('business_id', $business->id)
                ->where('id', $surveyId)
                ->firstOrFail();

            if ($survey->id != $validatedData['id']) {
                return RequestResponse::badRequest('Survey ID mismatch.');
            }

            $survey->delete();

            return RequestResponse::ok('Survey deleted successfully.');
        });
    }
}
