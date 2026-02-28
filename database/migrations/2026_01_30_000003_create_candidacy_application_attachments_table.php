<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('candidacy_application_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->unsignedBigInteger('application_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->foreign('application_id', 'fk_candidacy_attachment_application_id')
                ->references('application_id')
                ->on('candidacy_applications')
                ->onDelete('cascade');

            $table->index('application_id', 'idx_candidacy_attachment_application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidacy_application_attachments');
    }
};
