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
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            $table->string('member_no')->unique();
            $table->string('application_no')->nullable()->unique();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();

            $table->date('date_of_birth')->nullable();

            $table->enum('sex', ['male', 'female'])->nullable();
            $table->enum('civil_status', [
                'single',
                'married',
                'widowed',
                'separated',
                'divorced',
            ])->nullable();

            $table->string('nationality')->nullable();
            $table->string('religion')->nullable();
            $table->string('place_of_birth')->nullable();

            $table->string('tin')->nullable();

            $table->string('mobile_number')->nullable();
            $table->string('telephone_number')->nullable();
            $table->string('email')->nullable();

            $table->string('residence_type')->nullable();

            $table->enum('membership_type', [
                'regular',
                'associate',
            ])->nullable();

            $table->date('date_joined')->nullable();

            $table->enum('status', [
                'pending',
                'active',
                'inactive',
                'suspended',
                'terminated',
            ])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
