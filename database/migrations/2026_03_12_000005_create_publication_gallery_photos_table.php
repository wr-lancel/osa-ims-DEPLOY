<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_gallery_photos', function (Blueprint $table) {
            $table->id('photo_id');
            $table->unsignedBigInteger('gallery_id');
            $table->string('image_path', 500);
            $table->string('caption', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('gallery_id', 'fk_pub_gallery_photos_gallery_id')
                ->references('gallery_id')->on('publication_galleries')->onDelete('cascade');

            $table->index('gallery_id', 'idx_pub_gallery_photos_gallery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_gallery_photos');
    }
};
