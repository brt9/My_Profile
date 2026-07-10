<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('google_id')->nullable()->unique()->after('id');
        });

        Schema::dropIfExists('professional_experiences');
    }

    public function down(): void
    {
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

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });
    }
};
