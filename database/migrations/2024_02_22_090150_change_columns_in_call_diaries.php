<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_diaries', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->change();
            $table->foreignId('phone_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('call_diaries', function (Blueprint $table) {
            //
        });
    }
};
