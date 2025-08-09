<?php

// ===== 4. REVIEW REPLIES =====
// File: database/migrations/2025_08_04_105630_create_review_replies_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->text('owner_reply');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_replies');
    }
};