<?php

// ===== 6. PAYMENT METHODS =====
// File: database/migrations/2025_08_04_105631_create_payment_methods_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kos_id')->constrained('kos')->onDelete('cascade');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_name');
            $table->enum('type', ['Transfer', 'Cash', 'QRIS'])->default('Transfer');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};