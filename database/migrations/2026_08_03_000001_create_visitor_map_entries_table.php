<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_map_entries', function (Blueprint $table): void {
            $table->id();
            $table->char('visitor_key', 64)->unique();
            $table->decimal('latitude', 4, 1);
            $table->decimal('longitude', 5, 1);
            $table->timestampsTz();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_map_entries');
    }
};
