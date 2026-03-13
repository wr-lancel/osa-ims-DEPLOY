<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_articles', function (Blueprint $table) {
            $table->id('article_id');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->longText('body');
            $table->text('excerpt')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('author_id', 'fk_pub_articles_author_id')
                ->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by', 'fk_pub_articles_reviewed_by')
                ->references('user_id')->on('users')->onDelete('set null');

            $table->index('author_id', 'idx_pub_articles_author_id');
            $table->index('status', 'idx_pub_articles_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_articles');
    }
};
