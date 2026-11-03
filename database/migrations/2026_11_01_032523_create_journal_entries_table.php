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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            $table->string('entry_number', 50)->unique();
            $table->date('entry_date')->index();

            $table->string('reference_number', 100)->nullable();
            $table->text('description');

            $table->string('status', 20)->default('draft')->index();

            // For future integration with system transactions.
            $table->string('source_type', 100)->nullable();
            $table->string('source_id', 100)->nullable();

            // Links a reversal entry to its original entry.
            $table->foreignId('reversal_of_id')
                ->nullable()
                ->unique()
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('posted_at')->nullable();

            $table->timestamps();

            // Prevent duplicate journals for the same source transaction.
            $table->unique(
                ['source_type', 'source_id'],
                'journal_entries_source_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
