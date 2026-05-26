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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('lodgify_booking_id')->unique();
            $table->string('lodgify_room_id');
            $table->string('guest_name')->nullable();

            $table->date('arrival_date');
            $table->date('departure_date');

            $table->unsignedBigInteger('ttlock_lock_id');
            $table->unsignedBigInteger('ttlock_pwd_id')->nullable();
            $table->string('generated_passcode')->nullable();

            $table->string('status')->default('Booked');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
