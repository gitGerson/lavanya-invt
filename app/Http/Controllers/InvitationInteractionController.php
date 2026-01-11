<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuestbookRequest;
use App\Http\Requests\StoreRsvpRequest;
use App\Models\Invitation;
use App\Models\InvitationGuestbookEntry;
use App\Models\InvitationRsvpResponse;
use Illuminate\Http\RedirectResponse;

class InvitationInteractionController extends Controller
{
    protected function findPublishedInvitation(string $slug): Invitation
    {
        return Invitation::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();
    }

    public function storeGuestbook(StoreGuestbookRequest $request, string $slug): RedirectResponse
    {
        $invitation = $this->findPublishedInvitation($slug);

        InvitationGuestbookEntry::create([
            'invitation_id' => $invitation->id,
            'guest_name' => $request->string('guest_name')->toString(),
            'guest_address' => $request->string('guest_address')->toString() ?: null,
            'message' => $request->string('message')->toString(),
            'attendance' => $request->input('attendance'),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        return back()->with('success', 'Terima kasih! Ucapanmu sudah terkirim.');
    }

    public function storeRsvp(StoreRsvpRequest $request, string $slug): RedirectResponse
    {
        $invitation = $this->findPublishedInvitation($slug);

        InvitationRsvpResponse::create([
            'invitation_id' => $invitation->id,
            'guest_name' => $request->string('guest_name')->toString(),
            'phone' => $request->input('phone'),
            'attendance' => $request->input('attendance', 'yes'),
            'pax' => (int) $request->input('pax', 1),
            'note' => $request->input('note'),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        return back()->with('success', 'RSVP kamu sudah tersimpan. Terima kasih!');
    }
}
