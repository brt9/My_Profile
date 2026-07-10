<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('agent_id', 64);
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('cpu_temperature', 5, 2)->nullable();
            $table->decimal('gpu_usage', 5, 2)->nullable();
            $table->decimal('gpu_temperature', 5, 2)->nullable();
            $table->decimal('memory_usage', 5, 2)->nullable();
            $table->decimal('disk_usage', 5, 2)->nullable();
            $table->decimal('pump_rpm', 8, 2)->nullable();
            $table->decimal('coolant_temperature', 5, 2)->nullable();
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->string('agent_version', 30)->nullable();
            $table->timestampTz('collected_at');
            $table->timestampTz('received_at');

            $table->unique(['agent_id', 'collected_at']);
            $table->index('collected_at');
        });

        Schema::create('telemetry_hourly_aggregates', function (Blueprint $table): void {
            $table->id();
            $table->string('agent_id', 64);
            $table->string('metric', 40);
            $table->timestampTz('bucket_at');
            $table->decimal('minimum', 12, 2);
            $table->decimal('maximum', 12, 2);
            $table->decimal('average', 12, 2);
            $table->unsignedInteger('sample_count');
            $table->timestampsTz();

            $table->unique(['agent_id', 'metric', 'bucket_at']);
            $table->index('bucket_at');
        });

        Schema::create('integration_health', function (Blueprint $table): void {
            $table->id();
            $table->string('integration', 40)->unique();
            $table->string('status', 20);
            $table->timestampTz('last_success_at')->nullable();
            $table->timestampTz('last_failure_at')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_health');
        Schema::dropIfExists('telemetry_hourly_aggregates');
        Schema::dropIfExists('telemetry_snapshots');
    }
};
