<?php

// ===== 5. BOOKINGS =====
// File: database/migrations/2025_08_04_105630_create_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kos_id')->constrained('kos')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('kos_rooms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('booking_code')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_price');
            $table->enum('status', ['pending', 'accept', 'reject'])->default('pending');
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};