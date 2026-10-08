<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Team;
use FilamentCraft\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    /**
     * List the form submissions sent from the team's website.
     */
    public function index(Team $currentTeam): Response
    {
        Gate::authorize('manageWebsite', $currentTeam);

        $site = $currentTeam->website();

        return Inertia::render('website/inbox', [
            'submissions' => $site === null ? [] : Submission::query()
                ->forSite($site)
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (Submission $submission) => [
                    'id' => $submission->getKey(),
                    'type' => $submission->type->value,
                    'typeLabel' => $submission->type->getLabel(),
                    'sender' => $submission->sender(),
                    'email' => $submission->email(),
                    'fields' => collect($submission->payload->getArrayCopy())
                        ->filter(fn (mixed $value) => is_scalar($value) && (string) $value !== '')
                        ->map(fn (mixed $value) => (string) $value),
                    'isRead' => $submission->isRead(),
                    'receivedAt' => $submission->created_at->diffForHumans(),
                ]),
        ]);
    }

    /**
     * Mark a submission as read.
     */
    public function markAsRead(Team $currentTeam, int $submission): RedirectResponse
    {
        Gate::authorize('manageWebsite', $currentTeam);

        $site = $currentTeam->website();

        abort_if($site === null, 404);

        Submission::query()->forSite($site)->findOrFail($submission)->markAsRead();

        return back();
    }
}
