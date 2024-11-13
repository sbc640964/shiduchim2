<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->date('guy_next_time')->nullable();
            $table->date('girl_next_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('guy_next_time');
            $table->dropColumn('girl_next_time');
        });
    }
};
