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
        Schema::create('share_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('shares_subscribed')->default(0);

            $table->decimal('total_subscribed_amount', 15, 2)->default(0);
            $table->decimal('paid_up_amount', 15, 2)->default(0);

            $table->enum('status', [
                'active',
                'inactive',
                'closed',
            ])->default('active');

            $table->date('opened_at')->nullable();

            $table->timestamps();

            $table->unique('member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_accounts');
    }
};
