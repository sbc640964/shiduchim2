<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained();
            $table->string('brand', 100)->nullable();
            $table->string('token', 100);
            $table->string('last4', 4);
            $table->boolean('is_active')->default(true);
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_card_id')->nullable()->constrained();
            $table->foreignId('student_id')->constrained('people');
            $table->string('status', 100);
            $table->decimal('amount');
            $table->timestamp('paid_at')->nullable();
            $table->string('description')->nullable();
            $table->string('status_message')->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->string('last4', 4)->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
            $table->dropConstrainedForeignId('payer_id');
        });
        Schema::dropIfExists('payments');
    }
};
