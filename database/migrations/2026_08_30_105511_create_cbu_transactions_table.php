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
        Schema::create('cbu_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cbu_account_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('transaction_date');

            $table->enum('transaction_type', [
                'contribution',
                'loan_retention',
                'adjustment',
                'refund',
                'transfer',
            ]);

            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
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
        Schema::dropIfExists('cbu_transactions');
    }
};
