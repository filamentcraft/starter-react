<?php

namespace App\Http\Controllers\Website;

use App\Actions\Website\CreateWebsite;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\CreateWebsiteRequest;
use App\Http\Requests\Website\UpdateWebsiteStatusRequest;
use App\Models\Team;
use FilamentCraft\Blueprints\StarterFamily;
use FilamentCraft\Editor\EditorUrl;
use FilamentCraft\Enums\RegionName;
use FilamentCraft\Enums\SiteStatus;
use FilamentCraft\Enums\TemplateStatus;
use FilamentCraft\Models\Site;
use FilamentCraft\Models\Submission;
use FilamentCraft\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    /**
     * Show the team's website and its pages.
     */
    public function show(Team $currentTeam): Response
    {
        Gate::authorize('manageWebsite', $currentTeam);

        $site = $currentTeam->website();

        return Inertia::render('website/show', [
            'website' => $site ? $this->website($currentTeam, $site) : null,
            'families' => collect(StarterFamily::cases())->map(fn (StarterFamily $family) => [
                'value' => $family->value,
                'label' => $family->getLabel(),
                'description' => $family->description(),
            ]),
        ]);
    }

    /**
     * Create the team's website from a starter style family.
     */
    public function store(CreateWebsiteRequest $request, Team $currentTeam, CreateWebsite $createWebsite): RedirectResponse
    {
        Gate::authorize('manageWebsite', $currentTeam);

        $createWebsite->handle($currentTeam, $request->enum('family', StarterFamily::class));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Website created.')]);

        return to_route('website.show');
    }

    /**
     * Put the team's website online or take it offline.
     */
    public function updateStatus(UpdateWebsiteStatusRequest $request, Team $currentTeam): RedirectResponse
    {
        Gate::authorize('manageWebsite', $currentTeam);

        $site = $currentTeam->website();

        abort_if($site === null, 404);

        if ($request->boolean('live')) {
            $site->markLive();

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Website is live.')]);
        } else {
            $site->archive();

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Website is offline.')]);
        }

        return to_route('website.show');
    }

    /**
     * @return array<string, mixed>
     */
    private function website(Team $team, Site $site): array
    {
        $isLive = $site->status === SiteStatus::Live;

        return [
            'name' => $site->name,
            'status' => $site->status->value,
            'statusLabel' => $site->status->getLabel(),
            'isLive' => $isLive,
            'url' => route('website.public', ['tenantSlug' => $team->slug]),
            'unreadSubmissions' => Submission::query()->forSite($site)->unread()->count(),
            'regions' => collect([RegionName::Announcement, RegionName::Header, RegionName::Footer])
                ->filter(fn (RegionName $region) => in_array($region->value, config('filamentcraft.regions.enabled', []), true))
                ->map(fn (RegionName $region) => [
                    'name' => $region->getLabel(),
                    'editUrl' => EditorUrl::to($site, $region),
                ])
                ->values(),
            'pages' => Template::query()
                ->forSite($site->getKey())
                ->orderByRaw('id = ? desc', [$site->homepage_template_id])
                ->orderBy('name')
                ->get()
                ->map(function (Template $template) use ($site, $isLive): array {
                    $isHomepage = $template->getKey() === $site->homepage_template_id;

                    return [
                        'id' => $template->getKey(),
                        'name' => $template->name,
                        'path' => $isHomepage ? '/' : '/'.$template->slug,
                        'isHomepage' => $isHomepage,
                        'isPublished' => $template->status === TemplateStatus::Published,
                        'updatedAt' => $template->updated_at?->diffForHumans(),
                        'editUrl' => EditorUrl::to($template->setRelation('site', $site)),
                        'url' => $isLive ? $template->publicUrl() : null,
                    ];
                }),
        ];
    }
}
