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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();

            // Optional official account code from LMPC records.
            $table->string('account_code', 50)->nullable()->unique();

            $table->string('account_name', 150);

            // asset, liability, equity, income, expense
            $table->string('account_type', 20);

            // debit or credit
            $table->string('normal_balance', 10);

            // Supports parent and sub-accounts.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('chart_of_accounts')
                ->restrictOnDelete();

            // Determines whether transactions can be posted
            // directly to this account.
            $table->boolean('is_posting_allowed')->default(true);

            $table->boolean('is_active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['account_type', 'is_active']);
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
