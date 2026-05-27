<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_mappings', function (Blueprint $table) {
            $table->boolean('has_cour_access')->default(false)->after('ttlock_lock_id');
            $table->unsignedBigInteger('cour_lock_id')->nullable()->after('has_cour_access');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_mappings', function (Blueprint $table) {
            $table->dropColumn(['has_cour_access', 'cour_lock_id']);
        });
    }
};
