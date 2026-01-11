<?php

use App\Http\Controllers\InvitationInteractionController;
use App\Http\Controllers\InvitationPreviewController;
use App\Http\Controllers\InvitationPublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/inv/{slug}', [InvitationPublicController::class, 'show'])
    ->name('invitation.show');

Route::get('/preview/inv/{slug}', [InvitationPreviewController::class, 'show'])
    ->name('invitation.preview')
    ->middleware(['auth']);

Route::post('/inv/{slug}/rsvp', [InvitationInteractionController::class, 'storeRsvp'])
    ->name('invitation.rsvp.store')
    ->middleware(['throttle:20,1']);

Route::post('/inv/{slug}/guestbook', [InvitationInteractionController::class, 'storeGuestbook'])
    ->name('invitation.guestbook.store')
    ->middleware(['throttle:20,1']);
