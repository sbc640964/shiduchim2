<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people');
            $table->foreignId('payer_id')->nullable()->constrained('people');
            $table->string('method')->default('credit_card');
            $table->foreignId('credit_card_id')->nullable()->constrained();
            $table->foreignId('referrer_id')->nullable()->constrained('people');
            $table->string('status')->default('pending');
            $table->string('error')->nullable();
            $table->integer('payments')->nullable();
            $table->integer('balance_payments')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('next_payment_date')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('user_id')->nullable()->constrained();
            $table->integer('work_day')->nullable();
            $table->float('amount');
            $table->foreignId('proposal_id')->nullable()->constrained();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('subscriber_id')->nullable()->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
            $table->dropConstrainedForeignId('payer_id');
            $table->dropConstrainedForeignId('credit_card_id');
            $table->dropConstrainedForeignId('referrer_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('proposal_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscriber_id');
        });

        Schema::dropIfExists('subscribers');
    }
};
