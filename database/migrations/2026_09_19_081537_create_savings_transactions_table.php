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
        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('savings_account_id')
                ->constrained('savings_accounts')
                ->cascadeOnDelete();

            $table->dateTime('transaction_date');
            $table->string('transaction_type', 30);

            $table->enum('direction', [
                'credit',
                'debit',
            ]);

            $table->string('reference_no', 100)->nullable();
            $table->string('description', 500)->nullable();
            $table->decimal('amount', 15, 2);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_transactions');
    }
};
