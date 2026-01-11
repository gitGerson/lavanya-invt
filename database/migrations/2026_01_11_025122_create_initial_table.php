<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();   // e.g. template-1
            $table->string('name', 150);
            $table->string('version', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('template_id')
                ->constrained('templates')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('slug', 150)->unique();
            $table->string('title', 200)->nullable();

            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('locale', 16)->default('id_ID');

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invitation_id')
                ->nullable()
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->enum('kind', ['image', 'audio', 'video', 'other'])->default('image');

            $table->enum('category', [
                'section_image',
                'gallery_image',
                'background',
                'separator',
                'frame',
                'music',
                'other',
            ])->default('other');

            $table->enum('storage', ['url', 'local'])->default('url');

            $table->text('url')->nullable();       // when storage=url
            $table->string('disk', 50)->nullable(); // when storage=local (e.g. public)
            $table->string('path', 255)->nullable();// when storage=local

            $table->string('mime', 100)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index('invitation_id');
        });

        Schema::create('invitation_couple', function (Blueprint $table) {
            $table->foreignId('invitation_id')
                ->primary()
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('couple_tagline', 255)->nullable();
            $table->string('couple_name_1', 150)->nullable();
            $table->string('couple_name_2', 150)->nullable();
            $table->string('wedding_date_display', 150)->nullable();

            $table->foreignId('couple_image_asset_id')
                ->nullable()
                ->constrained('assets')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();
        });

        Schema::create('invitation_people', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invitation_id')
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->enum('role', ['bride', 'groom']);

            $table->string('name', 150)->nullable();
            $table->string('title', 255)->nullable();

            $table->string('father_name', 150)->nullable();
            $table->string('mother_name', 150)->nullable();

            $table->string('instagram_handle', 100)->nullable();

            $table->foreignId('photo_asset_id')
                ->nullable()
                ->constrained('assets')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->unique(['invitation_id', 'role']);
            $table->index('invitation_id');
        });

        Schema::create('invitation_event_section', function (Blueprint $table) {
            $table->foreignId('invitation_id')
                ->primary()
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('section_title', 150)->nullable(); // event_section_title
            $table->text('default_location_url')->nullable(); // event_location_url global CTA

            $table->timestamps();
        });

        Schema::create('invitation_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invitation_id')
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->integer('sort_order')->default(1);

            $table->string('title', 150)->nullable();

            // keep "display" fields from template (safe for any format)
            $table->string('event_date_display', 150)->nullable();
            $table->string('event_time_display', 150)->nullable();

            // optional normalized fields
            $table->date('event_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->string('location_text', 255)->nullable();
            $table->text('location_url')->nullable();

            $table->timestamps();

            $table->index('invitation_id');
        });

        Schema::create('invitation_gallery_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invitation_id')
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->integer('sort_order')->default(1);

            $table->foreignId('image_asset_id')
                ->constrained('assets')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->index('invitation_id');
        });

        Schema::create('invitation_map', function (Blueprint $table) {
            $table->foreignId('invitation_id')
                ->primary()
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('map_section_title', 150)->nullable();
            $table->text('map_address')->nullable();
            $table->text('map_embed_src')->nullable();   // iframe src
            $table->text('map_location_url')->nullable(); // CTA link

            $table->timestamps();
        });

        Schema::create('invitation_rsvp', function (Blueprint $table) {
            $table->foreignId('invitation_id')
                ->primary()
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('rsvp_title', 150)->nullable();
            $table->string('rsvp_subtitle', 255)->nullable();
            $table->text('rsvp_message')->nullable();
            $table->string('rsvp_hosts', 255)->nullable();

            $table->timestamps();
        });

        Schema::create('invitation_gift_section', function (Blueprint $table) {
            $table->foreignId('invitation_id')
                ->primary()
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('gift_title', 150)->nullable();
            $table->string('gift_subtitle', 255)->nullable();

            $table->timestamps();
        });

        Schema::create('invitation_gift_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invitation_id')
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->integer('sort_order')->default(1);

            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 100)->nullable();
            $table->string('account_holder', 150)->nullable();

            $table->foreignId('qr_asset_id')
                ->nullable()
                ->constrained('assets')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->index('invitation_id');
        });

        Schema::create('invitation_wish_section', function (Blueprint $table) {
            $table->foreignId('invitation_id')
                ->primary()
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('wish_title', 150)->nullable();

            $table->timestamps();
        });

        Schema::create('invitation_wish_samples', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invitation_id')
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->integer('sort_order')->default(1);

            $table->string('name', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->text('message')->nullable();

            $table->timestamps();

            $table->index('invitation_id');
        });

        Schema::create('invitation_guestbook_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invitation_id')
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('guest_name', 150);
            $table->string('guest_address', 255)->nullable();
            $table->text('message');

            $table->enum('attendance', ['unknown', 'yes', 'no', 'maybe'])->default('unknown');

            $table->timestamps();

            $table->index('invitation_id');
        });

        Schema::create('invitation_music', function (Blueprint $table) {
            $table->foreignId('invitation_id')
                ->primary()
                ->constrained('invitations')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('audio_asset_id')
                ->nullable()
                ->constrained('assets')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->boolean('autoplay')->default(true);
            $table->boolean('loop_audio')->default(true);

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial');
    }
};
