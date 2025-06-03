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
            // Role management
            $table->enum('role', ['admin', 'manager', 'employee'])->default('employee')->after('email_verified_at');
            
            // User status
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('role');
            
            // Profile information
            $table->string('profile_image_path')->nullable()->after('status');
            $table->string('phone')->nullable()->after('profile_image_path');
            $table->text('bio')->nullable()->after('phone');
            
            // Activity tracking
            $table->timestamp('last_login_at')->nullable()->after('bio');
            $table->ipAddress('last_login_ip')->nullable()->after('last_login_at');
            
            // Preferences (JSON field for flexible storage)
            $table->json('preferences')->nullable()->after('last_login_ip');
            
            // Timezone and locale
            $table->string('timezone')->default('UTC')->after('preferences');
            $table->string('locale')->default('en')->after('timezone');
            
            // Account verification and security
            $table->boolean('is_verified')->default(false)->after('locale');
            $table->boolean('two_factor_enabled')->default(false)->after('is_verified');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            
            // Soft deletes
            $table->softDeletes()->after('updated_at');
            
            // Add indexes for performance
            $table->index('role');
            $table->index('status');
            $table->index(['role', 'status']);
            $table->index('last_login_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropIndex(['role', 'status']);
            $table->dropIndex(['last_login_at']);
            $table->dropIndex(['deleted_at']);
            
            // Drop columns
            $table->dropColumn([
                'role',
                'status',
                'profile_image_path',
                'phone',
                'bio',
                'last_login_at',
                'last_login_ip',
                'preferences',
                'timezone',
                'locale',
                'is_verified',
                'two_factor_enabled',
                'two_factor_secret',
                'deleted_at'
            ]);
        });
    }
}; 