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
        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->unique()
                ->constrained('members')
                ->cascadeOnDelete();

            $table->string('account_number', 50)->unique();
            $table->string('account_type', 30)->default('regular');
            $table->decimal('current_balance', 15, 2)->default(0);

            $table->enum('status', [
                'active',
                'inactive',
                'closed',
            ])->default('active');

            $table->date('opened_at')->nullable();
            $table->date('closed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_accounts');
    }
};
