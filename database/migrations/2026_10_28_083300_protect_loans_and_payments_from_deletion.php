
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
            $table->foreign('loan_id')
                ->references('id')
                ->on('loans')
                ->restrictOnDelete();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropForeign(['loan_id']);
            $table->foreign('loan_id')
                ->references('id')
                ->on('loans')
                ->cascadeOnDelete();
        });
    }
};
