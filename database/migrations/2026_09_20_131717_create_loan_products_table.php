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
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();
            $table->string('name', 100);

            $table->decimal('interest_rate_monthly', 5, 2);
            $table->decimal('maximum_amount', 15, 2)->nullable();
            $table->unsignedSmallInteger('maximum_term_months')->nullable();
            $table->string('term_type', 20)->default('monthly');
            $table->text('eligibility_requirements')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};
