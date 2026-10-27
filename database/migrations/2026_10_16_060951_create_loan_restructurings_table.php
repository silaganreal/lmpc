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
        Schema::create('loan_restructurings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('requested_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->date('request_date');
            $table->text('request_reason');

            $table->unsignedTinyInteger('requested_term_years');
            $table->unsignedTinyInteger('approved_term_years')->nullable();

            $table->string('status')->default('pending');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->date('reviewed_at')->nullable();
            $table->text('board_remarks')->nullable();

            $table->decimal('remaining_principal', 15, 2)->nullable();
            $table->decimal('deferred_interest', 15, 2)->default(0);
            $table->decimal('penalties', 15, 2)->default(0);

            $table->date('restructured_at')->nullable();

            $table->timestamps();

            $table->index(['loan_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_restructurings');
    }
};
