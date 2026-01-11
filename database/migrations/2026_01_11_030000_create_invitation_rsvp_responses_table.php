<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_rsvp_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();

            $table->string('guest_name', 150);
            $table->string('phone', 50)->nullable();
            $table->enum('attendance', ['yes', 'no', 'maybe'])->default('yes');
            $table->unsignedSmallInteger('pax')->default(1);
            $table->text('note')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['invitation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_rsvp_responses');
    }
};
