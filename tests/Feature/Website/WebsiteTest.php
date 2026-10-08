<?php

use App\Actions\Website\CreateWebsite;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use FilamentCraft\Blueprints\StarterFamily;
use FilamentCraft\Editor\EditorUrl;
use FilamentCraft\Editor\Pages\EditorPage;
use FilamentCraft\Enums\SiteStatus;
use FilamentCraft\Models\Submission;
use Inertia\Testing\AssertableInertia as Assert;

function teamWithWebsite(?User $owner = null): Team
{
    $team = ($owner ?? User::factory()->create())->currentTeam;

    app(CreateWebsite::class)->handle($team, StarterFamily::Modern);

    return $team;
}

function editorUrl(Team $team): string
{
    return EditorUrl::to($team->website());
}

test('owners see the website builder prompt before a website exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('website.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('website/show')
            ->where('website', null)
            ->has('families', count(StarterFamily::cases())));
});

test('owners can create the team website from a style family', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('website.store'), ['family' => StarterFamily::Editorial->value])
        ->assertRedirect(route('website.show'));

    $site = $user->currentTeam->website();

    expect($site)->not->toBeNull()
        ->and($site->status)->toBe(SiteStatus::Live)
        ->and($site->homepage_template_id)->not->toBeNull();

    $this->get(route('website.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('website.isLive', true)
            ->where('website.pages.0.isHomepage', true)
            ->where('website.pages.0.editUrl', editorUrl($user->currentTeam)));
});

test('creating a website twice keeps the first one', function () {
    $team = teamWithWebsite();

    $this->actingAs($team->owner())
        ->post(route('website.store'), ['family' => StarterFamily::Bold->value]);

    expect($team->sites()->count())->toBe(1);
});

test('the style family must be a known one', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('website.store'), ['family' => 'neon'])
        ->assertSessionHasErrors('family');
});

test('owners can take the website offline and back online', function () {
    $team = teamWithWebsite();

    $this->actingAs($team->owner())->patch(route('website.status'), ['live' => false]);

    expect($team->website()->status)->toBe(SiteStatus::Archived);
    $this->get(route('website.public', ['tenantSlug' => $team->slug]))->assertNotFound();

    $this->patch(route('website.status'), ['live' => true]);

    expect($team->website()->status)->toBe(SiteStatus::Live);
    $this->get(route('website.public', ['tenantSlug' => $team->slug]))->assertOk();
});

test('the live website is public at its team path', function () {
    $team = teamWithWebsite();

    $this->get(route('website.public', ['tenantSlug' => $team->slug]))
        ->assertOk()
        ->assertSee($team->name);
});

test('members without the website permission cannot manage it', function () {
    $team = teamWithWebsite();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $this->actingAs($member)->get(route('website.show', ['current_team' => $team->slug]))->assertForbidden();
    $this->patch(route('website.status', ['current_team' => $team->slug]), ['live' => false])->assertForbidden();
    $this->get(editorUrl($team))->assertNotFound();

    expect($team->website()->status)->toBe(SiteStatus::Live);
});

test('admins can open the editor', function () {
    $team = teamWithWebsite();
    $admin = User::factory()->create();
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);

    $this->actingAs($admin)->get(editorUrl($team))->assertOk();
});

test('the editor needs a signed in user', function () {
    $team = teamWithWebsite();

    $this->get(editorUrl($team))->assertRedirect(route('login'));
});

test('a team cannot open another team\'s page through its own editor url', function () {
    $mine = teamWithWebsite();
    $theirs = teamWithWebsite();

    $url = EditorPage::getUrl(['template' => $theirs->website()->homepage_template_id], panel: 'site-editor', tenant: $mine);

    $this->actingAs($mine->owner())->get($url)->assertNotFound();
    $this->get(editorUrl($theirs))->assertNotFound();
});

test('the builder panel exposes nothing but the editor', function () {
    $team = teamWithWebsite();

    $this->actingAs($team->owner());

    $this->get('/builder/'.$team->slug.'/sites')->assertNotFound();
    $this->get('/builder/'.$team->slug.'/filament-craft-dashboard')->assertNotFound();
    $this->get('/builder/'.$team->slug)->assertNotFound();
    $this->get('/builder')->assertNotFound();
});

test('the editor leads back to the team website page', function () {
    $team = teamWithWebsite();

    $this->actingAs($team->owner())
        ->get(editorUrl($team))
        ->assertOk()
        ->assertSee(route('website.show', ['current_team' => $team->slug]), escape: false);
});

test('renaming a team keeps its website and editor addresses', function () {
    $team = teamWithWebsite();
    $publicUrl = route('website.public', ['tenantSlug' => $team->slug]);
    $editorUrl = editorUrl($team);

    $this->actingAs($team->owner())
        ->patch(route('teams.update', $team), ['name' => 'Renamed Studio'])
        ->assertRedirect(route('teams.edit', ['team' => $team->slug]));

    expect($team->fresh()->name)->toBe('Renamed Studio')
        ->and($team->fresh()->slug)->toBe($team->slug);

    $this->get($publicUrl)->assertOk();
    $this->get($editorUrl)->assertOk();
});

test('the inbox lists and marks the team\'s submissions', function () {
    $team = teamWithWebsite();
    $submission = Submission::factory()->for($team->website())->create([
        'payload' => ['name' => 'Ada', 'email' => 'ada@example.com', 'message' => 'Hello'],
    ]);

    $this->actingAs($team->owner())
        ->get(route('website.inbox'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('website/inbox')
            ->where('submissions.0.sender', 'Ada')
            ->where('submissions.0.isRead', false));

    $this->patch(route('website.inbox.read', ['submission' => $submission->getKey()]));

    expect($submission->fresh()->isRead())->toBeTrue();
});

test('submissions of another team are out of reach', function () {
    $mine = teamWithWebsite();
    $theirs = teamWithWebsite();
    $submission = Submission::factory()->for($theirs->website())->create();

    $this->actingAs($mine->owner())
        ->patch(route('website.inbox.read', ['current_team' => $mine->slug, 'submission' => $submission->getKey()]))
        ->assertNotFound();
});
