<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('publii_id')->nullable()->unique();
            $table->foreignId('author_id')->nullable()->constrained('authors')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('type')->default('post'); // post | page
            $table->string('status')->default('draft'); // published | draft | hidden
            $table->boolean('featured')->default(false);
            $table->boolean('exclude_homepage')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
