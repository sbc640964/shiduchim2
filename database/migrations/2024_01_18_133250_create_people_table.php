<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('external_code')->nullable();
            $table->enum('gender', ['B', 'G']);
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->foreignId('parents_family_id')->nullable()->constrained('families')->nullOnDelete();
            $table->string('prefix_name')->nullable();
            $table->string('suffix_name')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_update_relatives')->nullable();
            $table->timestamp('born_at')->nullable();
            $table->timestamp('died_at')->nullable();
            $table->json('data_raw')
                ->default(new Expression('(JSON_OBJECT())'))
                ->comment('מיועד לאחסון מידע גולמי מיבוא למשל');
            $table->foreignId('live_with_id')->nullable()->constrained('people')->nullOnDelete();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
