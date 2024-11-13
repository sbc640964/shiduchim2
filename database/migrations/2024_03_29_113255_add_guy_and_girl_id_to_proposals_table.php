<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('guy_id')->nullable()->constrained('people');
            $table->foreignId('girl_id')->nullable()->constrained('people');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['guy_id']);
            $table->dropForeign(['girl_id']);
;
            $table->dropColumn('guy_id');
            $table->dropColumn('girl_id');
        });
    }
};
