<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Website\InboxController;
use App\Http\Controllers\Website\WebsiteController;
use App\Http\Middleware\EnsureTeamMembership;
use App\Models\Team;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('website', [WebsiteController::class, 'show'])->name('website.show');
        Route::post('website', [WebsiteController::class, 'store'])->name('website.store');
        Route::patch('website/status', [WebsiteController::class, 'updateStatus'])->name('website.status');
        Route::get('website/inbox', [InboxController::class, 'index'])->name('website.inbox');
        Route::patch('website/inbox/{submission}', [InboxController::class, 'markAsRead'])->whereNumber('submission')->name('website.inbox.read');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';

Route::prefix('s')->group(fn () => Route::filamentCraftTenant(Team::class, name: 'website.public'));
