<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crew_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('authors')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->string('website')->nullable();
            $table->string('telegram')->nullable();
            $table->string('mastodon')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->boolean('former')->default(false);
            $table->string('tenure')->nullable();
            $table->timestamps();
        });

        Schema::create('post_crew', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('crew_member_id')->constrained('crew_members')->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->primary(['post_id', 'crew_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_crew');
        Schema::dropIfExists('crew_members');
    }
};
