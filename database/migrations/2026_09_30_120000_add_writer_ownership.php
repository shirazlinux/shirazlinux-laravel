<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 32)->default('admin')->after('password');
            }
            if (! Schema::hasColumn('users', 'author_id')) {
                $table->unsignedBigInteger('author_id')->nullable()->after('role');
                $table->index('author_id');
            }
        });

        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('author_id');
                $table->index('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'author_id')) {
                $table->dropColumn('author_id');
            }
        });
    }
};
