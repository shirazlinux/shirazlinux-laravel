<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            if (! Schema::hasColumn('tags', 'featured_image')) {
                $table->string('featured_image')->nullable()->after('description');
            }
            if (! Schema::hasColumn('tags', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('featured_image');
            }
            if (! Schema::hasColumn('tags', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
        });

        Schema::table('authors', function (Blueprint $table) {
            if (! Schema::hasColumn('authors', 'website')) {
                $table->string('website')->nullable()->after('avatar');
            }
            if (! Schema::hasColumn('authors', 'telegram')) {
                $table->string('telegram')->nullable()->after('website');
            }
            if (! Schema::hasColumn('authors', 'mastodon')) {
                $table->string('mastodon')->nullable()->after('telegram');
            }
            if (! Schema::hasColumn('authors', 'github')) {
                $table->string('github')->nullable()->after('mastodon');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 32)->default('admin')->after('password');
            }
        });

        if (! Schema::hasTable('redirects')) {
            Schema::create('redirects', function (Blueprint $table) {
                $table->id();
                $table->string('from_path', 500)->unique();
                $table->string('to_url', 500);
                $table->unsignedSmallInteger('status_code')->default(301);
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('post_revisions')) {
            Schema::create('post_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->longText('body')->nullable();
                $table->text('excerpt')->nullable();
                $table->string('status', 32)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 80);
                $table->string('subject_type', 80)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->text('description')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['subject_type', 'subject_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('post_revisions');
        Schema::dropIfExists('redirects');
        // columns left in place for safety
    }
};
