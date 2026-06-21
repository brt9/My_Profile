<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_calendar_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('refresh_token');
            $table->json('scopes')->nullable();
            $table->json('calendar_ids');
            $table->string('status', 30)->default('connected');
            $table->timestampTz('last_synced_at')->nullable();
            $table->string('last_error_code', 80)->nullable();
            $table->timestampsTz();
        });

        Schema::create('calendar_public_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connection_id')->constrained('google_calendar_connections')->cascadeOnDelete();
            $table->string('provider_event_key', 64);
            $table->string('public_title', 100)->default('Ocupado');
            $table->string('category', 20)->default('ocupado');
            $table->string('status', 20)->default('confirmado');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->boolean('all_day')->default(false);
            $table->timestampTz('synced_at');
            $table->timestampsTz();

            $table->unique(['connection_id', 'provider_event_key']);
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('duolingo_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 80);
            $table->string('language', 16);
            $table->string('language_name', 80);
            $table->unsignedBigInteger('course_xp')->default(0);
            $table->unsignedBigInteger('total_xp')->default(0);
            $table->unsignedInteger('streak')->default(0);
            $table->date('snapshot_date');
            $table->timestampTz('collected_at');
            $table->timestampsTz();

            $table->unique(['username', 'language', 'snapshot_date']);
            $table->index('collected_at');
        });

        Schema::create('professional_experiences', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 30)->default('linkedin_export');
            $table->string('external_key', 64);
            $table->string('company', 160);
            $table->string('role', 160);
            $table->string('location', 160)->nullable();
            $table->text('description')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestampTz('imported_at');
            $table->timestampsTz();

            $table->unique(['source', 'external_key']);
            $table->index(['is_current', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_experiences');
        Schema::dropIfExists('duolingo_snapshots');
        Schema::dropIfExists('calendar_public_events');
        Schema::dropIfExists('google_calendar_connections');
    }
};
