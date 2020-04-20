<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Helpers\Api;
use Illuminate\Http\Request;

class Organizations extends Controller
{
    /**
     * Get all jobs for the organization.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function jobs(Request $request): array
    {
        $results = $request->user()->userable->jobs()->with('skills');

        if (!empty($request->get('page')) || !empty($request->get('perPage'))) { // If pagination is to be applied.
            $page = $request->get('page', 1);
            $perPage = $request->get('perPage', 10);

            /** @var Paginator */
            $results = $results->paginate($perPage, ['*'], 'results', $page);

            $payload = [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'prev_page' => ($results->currentPage() > 1) ? $results->lastPage() : null,
                'next_page' => $results->hasMorePages() ? ($results->currentPage() + 1) : null,
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
                'data' => $results->items(),
            ];
        } else { // If all are to be gotten at once.
            $payload = [
                'data' => $results->get(),
                'total' => $results->count(),
            ];
        }

        return [
            'success' => true,
            'payload' => $payload
        ];
    }

    /**
     * Close a job.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Job $job
     *
     * @return array
     */
    public function closeJob(Request $request, Job $job): array
    {
        if (!$request->user()->can('update', $job)) {
            return Api::generateErrorResponse(401, 'AuthenticationError', 'You can not modify the job.');
        }

        // Close the job.
        $job->status = 'closed';
        $job->save();

        return [
            'success' => true,
            'payload' => [
                'data' => $job->refresh()->id
            ]
        ];
    }
}