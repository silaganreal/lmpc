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
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->string('first_name')->after('member_id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->after('middle_name');
            $table->string('suffix')->nullable()->after('last_name');

            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('sex')->nullable()->after('date_of_birth');
            $table->string('civil_status')->nullable()->after('sex');
            $table->string('nationality')->nullable()->after('civil_status');
            $table->string('religion')->nullable()->after('nationality');
            $table->string('place_of_birth')->nullable()->after('religion');
            $table->string('tin')->nullable()->after('place_of_birth');

            $table->string('mobile_number')->nullable()->after('tin');
            $table->string('telephone_number')->nullable()->after('mobile_number');
            $table->string('email')->nullable()->after('telephone_number');
            $table->string('residence_type')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'date_of_birth',
                'sex',
                'civil_status',
                'nationality',
                'religion',
                'place_of_birth',
                'tin',
                'mobile_number',
                'telephone_number',
                'email',
                'residence_type',
            ]);
        });
    }
};
