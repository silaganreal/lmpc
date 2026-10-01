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
        Schema::create('loan_amortizations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('installment_no');

            $table->date('due_date');

            $table->decimal('beginning_balance', 15, 2);
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_amount', 15, 2);
            $table->decimal('penalty_amount', 15, 2)->default(0);
            $table->decimal('total_due', 15, 2);

            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('paid_date')->nullable();

            $table->string('status', 20)->default('pending');

            $table->timestamps();

            $table->unique([
                'loan_id',
                'installment_no',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_amortizations');
    }
};
