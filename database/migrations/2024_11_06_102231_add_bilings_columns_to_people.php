<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('billing_status', 100)->nullable();
            $table->string('billing_method', 100)->nullable();
            $table->foreignId('billing_credit_card_id')->nullable()->constrained('credit_cards');
            $table->foreignId('billing_matchmaker')->nullable()->constrained('users');
            $table->decimal('billing_amount')->nullable();
            $table->timestamp('billing_next_date')->nullable();
            $table->integer('billing_balance_times')->nullable();
            $table->integer('billing_matchmaker_day')->nullable();
            $table->foreignId('billing_payer_id')->nullable()->constrained('people');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('billing_status');
            $table->dropColumn('billing_method');
            $table->dropColumn('billing_amount');
            $table->dropColumn('billing_next_date');
            $table->dropColumn('billing_balance_times');
            $table->dropColumn('billing_matchmaker_day');
            $table->dropConstrainedForeignId('billing_matchmaker');
            $table->dropConstrainedForeignId('billing_credit_card_id');
            $table->dropConstrainedForeignId('billing_payer_id');

        });
    }
};
