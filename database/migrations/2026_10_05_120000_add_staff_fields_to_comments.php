<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            if (! Schema::hasColumn('comments', 'is_staff')) {
                $table->boolean('is_staff')->default(false)->after('status');
            }
            if (! Schema::hasColumn('comments', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('is_staff');
                $table->index('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            if (Schema::hasColumn('comments', 'user_id')) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('comments', 'is_staff')) {
                $table->dropColumn('is_staff');
            }
        });
    }
};
