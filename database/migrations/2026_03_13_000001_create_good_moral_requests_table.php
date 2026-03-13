<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('good_moral_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('student_number');
            $table->string('course');
            $table->string('year_graduated');
            $table->string('contact_number');
            $table->string('email');
            $table->text('purpose');
            $table->enum('status', ['pending', 'payment_verified', 'ready_for_pickup', 'released'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('good_moral_requests');
    }
};
