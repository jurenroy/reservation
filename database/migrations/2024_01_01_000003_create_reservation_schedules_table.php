<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->date('booking_date');
            $table->time('time_from');
            $table->time('time_to');
            $table->timestamps();

            // Speeds up the conflict-check query (same date, overlapping range)
            $table->index(['booking_date', 'time_from', 'time_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_schedules');
    }
};
