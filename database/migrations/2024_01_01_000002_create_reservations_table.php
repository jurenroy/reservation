<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();

            $table->string('requester_name');
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->string('division_other')->nullable(); // used when "Others" is picked

            $table->date('date_of_filing');
            $table->unsignedInteger('participants');

            $table->string('purpose');            // Training/Seminar | Meeting | Others
            $table->string('purpose_title');

            $table->json('extras')->nullable();   // ["LCD Projector","Sound System",...]
            $table->string('setup_requirement')->nullable(); // Conference Type (U-Shape) | Classroom Type
            $table->string('other_needs')->nullable();

            $table->longText('signature')->nullable(); // base64 PNG data URI

            $table->enum('status', ['pending', 'approved', 'rejected', 'archived'])->default('pending');
            $table->string('approved_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
