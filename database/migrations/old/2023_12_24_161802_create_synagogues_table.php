<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synagogues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('city_id')->nullable()->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('synagogues', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
        });
        Schema::dropIfExists('synagogues');
    }
};
