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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('loan_product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('loan_no', 50)->unique();

            $table->date('application_date');
            $table->date('approval_date')->nullable();
            $table->date('release_date')->nullable();
            $table->date('maturity_date')->nullable();

            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate_monthly', 5, 2);
            $table->unsignedSmallInteger('term_months')->nullable();
            $table->decimal('monthly_amortization', 15, 2)->nullable();
            $table->decimal('outstanding_principal', 15, 2)->default(0);
            $table->decimal('outstanding_interest', 15, 2)->default(0);
            $table->decimal('outstanding_penalty', 15, 2)->default(0);
            $table->decimal('service_fee', 15, 2)->default(0);
            $table->decimal('cbu_retention', 15, 2)->default(0);
            $table->decimal('insurance_premium', 15, 2)->default(0);
            $table->decimal('notarial_fee', 15, 2)->default(0);
            $table->decimal('net_process', 15, 2)->default(0);

            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'released',
                'active',
                'past_due',
                'fully_paid',
                'cancelled',
                'rejected',
            ])->default('draft');

            $table->text('purpose')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index(['loan_product_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
