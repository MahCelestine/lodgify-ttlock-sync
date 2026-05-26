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
        Schema::create('property_mappings', function (Blueprint $table) {
        $table->id();
        $table->string('lodgify_property_id')->unique(); 
        $table->unsignedBigInteger('ttlock_lock_id');
        $table->string('lodgify_property_name')->nullable();
        $table->string('ttlock_lock_name')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_mappings');
    }
};
