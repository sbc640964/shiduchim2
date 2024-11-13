<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->text('info')->nullable();
            $table->json('info_private')->default(new \Illuminate\Database\Query\Expression('(JSON_OBJECT())'));
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('info');
            $table->dropColumn('info_private');
        });
    }
};
