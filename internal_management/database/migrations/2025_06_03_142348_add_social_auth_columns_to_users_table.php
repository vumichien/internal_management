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
        Schema::table('users', function (Blueprint $table) {
            // Social authentication provider IDs
            $table->string('google_id')->nullable()->after('email');
            $table->string('github_id')->nullable()->after('google_id');
            
            // Add indexes for performance
            $table->index('google_id');
            $table->index('github_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['google_id']);
            $table->dropIndex(['github_id']);
            
            // Drop columns
            $table->dropColumn(['google_id', 'github_id']);
        });
    }
};
