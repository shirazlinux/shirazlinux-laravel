<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_contributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('authors')->cascadeOnDelete();
            $table->string('role', 32)->default('writer'); // writer|translator|editor
            $table->unsignedInteger('sort')->default(0);
            $table->unique(['post_id', 'author_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_contributors');
    }
};
