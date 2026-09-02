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
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();

            $table->string('application_no')->unique();

            $table->foreignId('member_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('date_of_application');

            $table->enum('membership_type', [
                'regular',
                'associate',
            ]);

            $table->unsignedInteger('shares_subscribed')->default(0);
            $table->decimal('amount_subscribed', 15, 2)->default(0);
            $table->decimal('initial_paid_up', 15, 2)->default(0);

            $table->string('recruiter_name')->nullable();
            $table->string('recruiter_mobile')->nullable();

            $table->string('board_resolution_no')->nullable();
            $table->date('board_approval_date')->nullable();

            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'approved',
                'rejected',
                'cancelled',
            ])->default('draft');

            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('processed_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_applications');
    }
};
