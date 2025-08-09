<?php

// ===== 7. FAVORITES =====
// File: database/migrations/2025_08_04_105631_create_favorites_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('kos_id')->constrained('kos')->onDelete('cascade');
            $table->timestamps();
            
            // Prevent duplicate favorites
            $table->unique(['user_id', 'kos_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};