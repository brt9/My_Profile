<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('connection_id')->nullable()->constrained('google_calendar_connections')->nullOnDelete();
            $table->string('provider_event_key', 64)->unique();
            $table->text('provider_event_id')->nullable();
            $table->string('provider_calendar_id', 180)->nullable();
            $table->string('public_title', 100);
            $table->string('category', 20)->default('ocupado');
            $table->string('status', 20)->default('confirmado');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->boolean('all_day')->default(false);
            $table->string('source', 20)->default('local');
            $table->string('sync_status', 20)->default('local_only');
            $table->timestampTz('synced_at')->nullable();
            $table->string('last_sync_error', 120)->nullable();
            $table->timestampsTz();

            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index(['user_id', 'starts_at']);
        });

        if (Schema::hasTable('calendar_public_events')) {
            $connectionUsers = DB::table('google_calendar_connections')->pluck('user_id', 'id');

            DB::table('calendar_public_events')->orderBy('id')->each(function (object $event) use ($connectionUsers): void {
                DB::table('calendar_events')->insert([
                    'user_id' => $connectionUsers[$event->connection_id] ?? null,
                    'connection_id' => $event->connection_id,
                    'provider_event_key' => $event->provider_event_key,
                    'provider_event_id' => null,
                    'provider_calendar_id' => null,
                    'public_title' => $event->public_title,
                    'category' => $event->category,
                    'status' => $event->status,
                    'starts_at' => $event->starts_at,
                    'ends_at' => $event->ends_at,
                    'all_day' => $event->all_day,
                    'source' => 'google',
                    'sync_status' => 'synced',
                    'synced_at' => $event->synced_at,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                ]);
            });
        }

        Schema::create('weather_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('location_key', 40)->index();
            $table->string('label', 100);
            $table->decimal('latitude', 8, 4);
            $table->decimal('longitude', 8, 4);
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('feels_like', 5, 2)->nullable();
            $table->unsignedTinyInteger('humidity')->nullable();
            $table->unsignedSmallInteger('wind_kmh')->nullable();
            $table->unsignedSmallInteger('weather_code')->nullable();
            $table->string('condition', 80)->nullable();
            $table->string('emoji', 16)->nullable();
            $table->timestampTz('observed_at')->nullable();
            $table->timestampTz('captured_at');
            $table->timestampsTz();

            $table->index(['location_key', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_snapshots');
        Schema::dropIfExists('calendar_events');
    }
};
