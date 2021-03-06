<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Job;
use App\Helpers\Api;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\JobApplication;
use App\Models\StudentProfileView;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Exceptions\AuthenticationError;
use App\Exceptions\InvalidFormDataError;
use App\Models\StudentHire;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StudentApplicationSubmitted;
use App\Notifications\StudentJobOffer;
use App\Notifications\StudentJobOfferUpdated;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Students extends Controller
{
    /**
     * Get profile overview for a student.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function profileOverview(Request $request): array
    {
        // Statistical data.
        $noJobsAppliedFor = Job::whereHas('applications', function (Builder $applications) use($request) {
            $applications->where('student_id', $request->user()->id);
        })->count();

        $noJobOffersReceived = $request->user()->userable->hires()->count();

        $noJobsAppliedDone = Job::whereHas('applications', function (Builder $applications) use($request) {
            $applications->where('student_id', $request->user()->id)->where('status', 'accepted');
        })->count() + $request->user()->userable->hires()->where('student_hires.status', 'accepted')->count();


        $now = Carbon::now();

        // Total views for the year.
        $totalViews = $request->user()->userable->views()
            ->whereYear('student_profile_views.created_at', '=', $now->year)
            ->count();

        // View data for the month.
        $monthData = [ 'total' => $totalViews ];
        $weekData = [];

        foreach (range(1, 12) as $i) {
            $monthData[$i] = $request->user()->userable->views()
                ->whereYear('student_profile_views.created_at', '=', $now->year)
                ->whereMonth('student_profile_views.created_at', '=', $i)
                ->count();

            if ($i == $now->month) {
                $weekData['total'] = $monthData[$i];

                // Get statistics for the month.
                foreach (range(0, 3) as $j) {
                    $weekData[$j + 1] = $request->user()->userable->views()
                        ->whereYear('student_profile_views.created_at', '=', $now->year)
                        ->whereMonth('student_profile_views.created_at', '=', $i)
                        ->whereBetween('student_profile_views.created_at', [
                            Carbon::now()->firstOfMonth()->addDays($j * 7),
                            Carbon::now()->firstOfMonth()->addDays(($j + 1) * 7)
                        ])->count();
                }

                // If the month has more than 4 weeks.
                if ($now->daysInMonth > 28) {
                    $weekData[5] = $request->user()->userable->views()
                        ->whereYear('student_profile_views.created_at', '=', $now->year)
                        ->whereMonth('student_profile_views.created_at', '=', $i)
                        ->whereBetween('student_profile_views.created_at', [
                            Carbon::now()->firstOfMonth()->addDays(4 * 7),
                            Carbon::now()->firstOfMonth()->addDays(5 * 7)
                        ])->count();
                }
            }
        }


        // Response
        return [
            'success' => true,
            'payload' => [
                'data' => [
                    'jobs_applied_for' => $noJobsAppliedFor,
                    'job_offers_received' => $noJobOffersReceived,
                    'jobs_done' => $noJobsAppliedDone,
                    'visits' => [
                        'total' => $totalViews,
                        'weekly' => $weekData,
                        'monthly' => $monthData
                    ],
                ]
            ]
        ];
    }

    /**
     * update profile visits for a student.
     *
     * @param \Illuminate\Http\Request $request
     * @param User $student
     *
     * @return void
     */
    public function updateVisits(Request $request, User $student): array
    {
        if ($student->userable_type != Student::class) {
            throw new NotFoundHttpException('The resource you requested for was not found.');
        }

        // Add a viewed record to the database.
        $view = new StudentProfileView();
        $view->organization_id = $request->user()->id;
        $view->student_id = $student->id;
        $view->save();

        // Response
        return [
            'success' => true,
            'payload' => []
        ];
    }

    /**
     * Apply for a job.
     *
     * @param \Illuminate\Http\Request $request
     * @param Job $job
     *
     * @return array
     */
    public function applyForJob(Request $request, Job $job): array
    {
        // Check if the user can apply for the job.
        if (!$request->user()->can('apply', $job)) {
            throw new AuthenticationError('You can not apply for the job.');
        }

        // Validate input.
        $validator = Validator::make($request->json()->all(), [
            'proposal' => 'required',
            'previous_experience' => 'required',
            'cover_letter' => 'required',
        ]);

        if ($validator->fails()) { // Validation fails.
            throw new InvalidFormDataError(Api::getFirstValidationError($validator));
        }

        // Apply for the job.
        $application = new JobApplication();
        $application->proposal = $request->json('proposal');
        $application->previous_experience = $request->json('previous_experience');
        $application->cover_letter = $request->json('cover_letter');
        $application->student_id = $request->user()->id;
        $job->applications()->save($application);

        // Send notifications to the organization and student.
        Notification::send([$request->user(), $job->organization], new StudentApplicationSubmitted($application));

        // Response.
        return [
            'success' => true,
            'payload' => [
                'data' => $application->refresh()->load(['job', 'job.organization', 'job.skills'])
            ]
        ];
    }

    /**
     * Send a job offer to a student.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $student
     *
     * @return array
     */
    public function hireStudent(Request $request, User $student): array
    {
        if ($student->verified != true) {
            throw new AuthenticationError('The student is yet to be verified.');
        } else if ($student->userable_type != Student::class) {
            throw new NotFoundHttpException('The resource you requested for was not found.');
        } else if (!$student->userable->available_for_job) {
            throw new AuthenticationError('The student is not available to take any jobs currently.');
        }

        // Send the job offer.
        $hire = new StudentHire();
        $hire->organization_id = $request->user()->id;
        $hire->student_id = $student->id;
        $hire->save();

        // Send notifications to the organization and student.
        Notification::send([$request->user(), $student], new StudentJobOffer($hire));

        // Response.
        return [
            'success' => true,
            'payload' => [
                'data' => $hire->refresh()->load(['student', 'organization'])
            ]
        ];
    }

    /**
     * Update the value of a job offer from a company.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\StudentHire $offer
     *
     * @return array
     */
    public function updateJobOffer(Request $request, StudentHire $offer): array
    {
        if (!$request->user()->can('update', $offer)) {
            throw new AuthenticationError('You can not modify this job offer.');
        } else if ($offer->status != 'pending') {
            throw new AuthenticationError("This job offer has already been {$offer->status}.");
        }

        // Validate input.
        $validator = Validator::make($request->json()->all(), [
            'status' => 'required|in:accepted,rejected',
        ], [
            'status.in' => 'Invalid value for status.',
        ]);

        if ($validator->fails()) { // Validation fails.
            throw new InvalidFormDataError(Api::getFirstValidationError($validator));
        }

        // Update the job application status.
        $offer->status = $request->json('status');
        $offer->save();

        // Send notifications to the organization and student.
        Notification::send([$request->user(), $offer->organization], new StudentJobOfferUpdated($offer));

        // Response.
        return [
            'success' => true,
            'payload' => [
                'data' => $offer->refresh()->load(['student', 'organization'])
            ]
        ];
    }
}
