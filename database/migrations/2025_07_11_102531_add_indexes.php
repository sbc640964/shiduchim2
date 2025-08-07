<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            //add index to first_name and last_name
            $table->index(['first_name', 'last_name']);
        });

        Schema::table('families', function (Blueprint $table) {
            //add index to name
            $table->index('name');
        });

        Schema::table('diaries', function (Blueprint $table) {
            //add column storage as JSON_UNQUOTE(JSON_EXTRACT(`diaries`.`data`, '$."call_id"')) with index
            $table->string('call_id')->storedAs('JSON_UNQUOTE(JSON_EXTRACT(`data`, \'$."call_id"\'))')->index();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            //drop index from first_name and last_name
            $table->dropIndex(['first_name', 'last_name']);
        });

        Schema::table('families', function (Blueprint $table) {
            //drop index from name
            $table->dropIndex(['name']);
        });

        Schema::table('diaries', function (Blueprint $table) {
            //drop column call_id
            $table->dropColumn('call_id');
        });
    }
};
