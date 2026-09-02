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
        Schema::create('membership_application_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('membership_application_id')
                ->constrained(
                    table: 'membership_applications',
                    indexName: 'mad_application_id_foreign'
                )
                ->cascadeOnDelete();

            $table->string('document_type');
            $table->string('file_path')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('status', [
                'pending',
                'submitted',
                'verified',
                'rejected',
            ])->default('pending');

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_application_documents');
    }
};
