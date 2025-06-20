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
        Schema::table('module_states', function (Blueprint $table) {
            if (!Schema::hasColumn('module_states', 'enabled')) {
                $table->boolean('enabled')->default(false)->after('status');
            }
            if (!Schema::hasColumn('module_states', 'last_enabled_at')) {
                $table->timestamp('last_enabled_at')->nullable()->after('enabled');
            }
            if (!Schema::hasColumn('module_states', 'last_disabled_at')) {
                $table->timestamp('last_disabled_at')->nullable()->after('last_enabled_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_states', function (Blueprint $table) {
            $table->dropColumn(['enabled', 'last_enabled_at', 'last_disabled_at']);
        });
    }
}; 