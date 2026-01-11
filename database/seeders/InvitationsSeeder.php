<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Invitation;
use App\Models\InvitationCouple;
use App\Models\InvitationEvent;
use App\Models\InvitationEventSection;
use App\Models\InvitationGalleryItem;
use App\Models\InvitationGiftAccount;
use App\Models\InvitationGiftSection;
use App\Models\InvitationGuestbookEntry;
use App\Models\InvitationMap;
use App\Models\InvitationMusic;
use App\Models\InvitationPerson;
use App\Models\InvitationRsvp;
use App\Models\InvitationRsvpResponse;
use App\Models\InvitationWishSection;
use App\Models\InvitationWishSample;
use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InvitationsSeeder extends Seeder
{
    public function run(): void
    {
        $templateId = Template::query()
            ->where('code', 'template-1')
            ->value('id');

        if (! $templateId) {
            return;
        }

        $records = [
            ['slug' => 'alin-aldi', 'status' => 'published', 'title' => 'Alin & Aldi Wedding'],
            ['slug' => 'rani-rizky', 'status' => 'published', 'title' => 'Rani & Rizky Wedding'],
            ['slug' => 'preview-sample', 'status' => 'draft', 'title' => 'Preview Sample Wedding'],
        ];

        foreach ($records as $record) {
            $invitation = Invitation::query()->updateOrCreate(
                ['slug' => $record['slug']],
                [
                    'template_id' => $templateId,
                    'title' => $record['title'],
                    'timezone' => 'Asia/Jakarta',
                    'locale' => 'id_ID',
                    'status' => $record['status'],
                ]
            );

            $coupleImage = Asset::factory()->create([
                'invitation_id' => $invitation->id,
                'category' => 'section_image',
                'kind' => 'image',
                'storage' => 'url',
                'url' => "https://picsum.photos/seed/{$invitation->slug}-couple/1200/800",
                'alt_text' => 'Couple image',
            ]);

            $bridePhoto = Asset::factory()->create([
                'invitation_id' => $invitation->id,
                'category' => 'section_image',
                'kind' => 'image',
                'storage' => 'url',
                'url' => "https://picsum.photos/seed/{$invitation->slug}-bride/900/900",
                'alt_text' => 'Bride photo',
            ]);

            $groomPhoto = Asset::factory()->create([
                'invitation_id' => $invitation->id,
                'category' => 'section_image',
                'kind' => 'image',
                'storage' => 'url',
                'url' => "https://picsum.photos/seed/{$invitation->slug}-groom/900/900",
                'alt_text' => 'Groom photo',
            ]);

            $musicAudio = Asset::factory()->audioUrl()->create([
                'invitation_id' => $invitation->id,
            ]);

            InvitationCouple::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'couple_tagline' => 'The Wedding of',
                    'couple_name_1' => Str::title(explode('-', $invitation->slug)[0]),
                    'couple_name_2' => Str::title(explode('-', $invitation->slug)[1] ?? 'Partner'),
                    'wedding_date_display' => 'Saturday, 20 January 2026',
                    'couple_image_asset_id' => $coupleImage->id,
                ]
            );

            InvitationEventSection::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'section_title' => 'Events',
                    'default_location_url' => 'https://maps.google.com/?q=Jakarta',
                ]
            );

            InvitationMap::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'map_section_title' => 'Location',
                    'map_address' => 'Jakarta, Indonesia',
                    'map_location_url' => 'https://maps.google.com/?q=Jakarta',
                    'map_embed_src' => 'https://www.google.com/maps?q=Jakarta&output=embed',
                ]
            );

            InvitationRsvp::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'rsvp_title' => 'RSVP',
                    'rsvp_subtitle' => 'Confirm your attendance',
                    'rsvp_message' => 'Please confirm your attendance.',
                    'rsvp_hosts' => 'Keluarga Besar Mempelai',
                ]
            );

            InvitationGiftSection::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'gift_title' => 'Wedding Gift',
                    'gift_subtitle' => 'Your blessing means a lot to us.',
                ]
            );

            InvitationWishSection::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'wish_title' => 'Wishes',
                ]
            );

            InvitationMusic::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'audio_asset_id' => $musicAudio->id,
                    'autoplay' => true,
                    'loop_audio' => true,
                ]
            );

            InvitationPerson::query()->updateOrCreate(
                ['invitation_id' => $invitation->id, 'role' => 'bride'],
                [
                    'name' => 'Alin',
                    'title' => 'First Daughter',
                    'father_name' => 'Bride Father',
                    'mother_name' => 'Bride Mother',
                    'instagram_handle' => '@bride',
                    'photo_asset_id' => $bridePhoto->id,
                ]
            );

            InvitationPerson::query()->updateOrCreate(
                ['invitation_id' => $invitation->id, 'role' => 'groom'],
                [
                    'name' => 'Aldi',
                    'title' => 'First Son',
                    'father_name' => 'Groom Father',
                    'mother_name' => 'Groom Mother',
                    'instagram_handle' => '@groom',
                    'photo_asset_id' => $groomPhoto->id,
                ]
            );

            InvitationEvent::query()->updateOrCreate(
                ['invitation_id' => $invitation->id, 'sort_order' => 1],
                [
                    'title' => 'Ceremony',
                    'event_date_display' => 'Saturday, 20 January 2026',
                    'event_time_display' => '08:00 - 10:00',
                    'location_text' => 'Main Mosque, Jakarta',
                    'location_url' => 'https://maps.google.com/?q=Masjid+Jakarta',
                ]
            );

            InvitationEvent::query()->updateOrCreate(
                ['invitation_id' => $invitation->id, 'sort_order' => 2],
                [
                    'title' => 'Reception',
                    'event_date_display' => 'Saturday, 20 January 2026',
                    'event_time_display' => '11:00 - 14:00',
                    'location_text' => 'Ballroom, Jakarta',
                    'location_url' => 'https://maps.google.com/?q=Gedung+Jakarta',
                ]
            );

            InvitationGalleryItem::query()
                ->where('invitation_id', $invitation->id)
                ->delete();

            for ($i = 1; $i <= 6; $i++) {
                $img = Asset::factory()->create([
                    'invitation_id' => $invitation->id,
                    'category' => 'gallery_image',
                    'kind' => 'image',
                    'storage' => 'url',
                    'url' => "https://picsum.photos/seed/{$invitation->slug}-gallery-{$i}/1200/800",
                    'alt_text' => 'Gallery image',
                ]);

                InvitationGalleryItem::query()->create([
                    'invitation_id' => $invitation->id,
                    'image_asset_id' => $img->id,
                    'sort_order' => $i,
                ]);
            }

            InvitationGiftAccount::query()
                ->where('invitation_id', $invitation->id)
                ->delete();

            $qr1 = Asset::factory()->qrUrl()->create([
                'invitation_id' => $invitation->id,
            ]);
            $qr2 = Asset::factory()->qrUrl()->create([
                'invitation_id' => $invitation->id,
            ]);

            InvitationGiftAccount::query()->create([
                'invitation_id' => $invitation->id,
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_holder' => 'Alin',
                'qr_asset_id' => $qr1->id,
                'sort_order' => 1,
            ]);

            InvitationGiftAccount::query()->create([
                'invitation_id' => $invitation->id,
                'bank_name' => 'Mandiri',
                'account_number' => '9876543210',
                'account_holder' => 'Aldi',
                'qr_asset_id' => $qr2->id,
                'sort_order' => 2,
            ]);

            InvitationWishSample::query()
                ->where('invitation_id', $invitation->id)
                ->delete();

            InvitationWishSample::query()->create([
                'invitation_id' => $invitation->id,
                'name' => 'Friend',
                'address' => 'Jakarta',
                'message' => 'Wishing you a lifetime of love and happiness.',
                'sort_order' => 1,
            ]);

            InvitationWishSample::query()->create([
                'invitation_id' => $invitation->id,
                'name' => 'Family',
                'address' => 'Bandung',
                'message' => 'May your marriage be filled with joy and peace.',
                'sort_order' => 2,
            ]);

            InvitationGuestbookEntry::query()
                ->where('invitation_id', $invitation->id)
                ->delete();

            for ($i = 1; $i <= 5; $i++) {
                InvitationGuestbookEntry::query()->create([
                    'invitation_id' => $invitation->id,
                    'guest_name' => "Guest {$i}",
                    'guest_address' => 'Indonesia',
                    'message' => "Message {$i}: Best wishes on your big day!",
                    'attendance' => 'yes',
                ]);
            }

            InvitationRsvpResponse::query()
                ->where('invitation_id', $invitation->id)
                ->delete();

            InvitationRsvpResponse::query()->create([
                'invitation_id' => $invitation->id,
                'guest_name' => 'Budi',
                'phone' => '08123456789',
                'attendance' => 'yes',
                'pax' => 2,
                'note' => 'See you there!',
            ]);

            InvitationRsvpResponse::query()->create([
                'invitation_id' => $invitation->id,
                'guest_name' => 'Sari',
                'phone' => '08987654321',
                'attendance' => 'maybe',
                'pax' => 1,
                'note' => 'Will confirm later.',
            ]);
        }
    }
}