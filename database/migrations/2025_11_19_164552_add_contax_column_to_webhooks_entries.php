<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('webhook_entries', function (Blueprint $table) {
            $table->json('context')->default(new \Illuminate\Database\Query\Expression('(JSON_OBJECT())'));
        });
    }

    public function down(): void
    {
        Schema::table('webhook_entries', function (Blueprint $table) {
            $table->dropColumn('context');
        });
    }
};
