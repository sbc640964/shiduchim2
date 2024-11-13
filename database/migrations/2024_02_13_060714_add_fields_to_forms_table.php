<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->json('edit_pleases')->default(new Expression('(JSON_ARRAY())'));
            $table->json('view_pleases')->default(new Expression('(JSON_ARRAY())'));
            $table->json('edit_roles')->default(new Expression('(JSON_ARRAY())'));
            $table->json('view_roles')->default(new Expression('(JSON_ARRAY())'));
            $table->boolean('is_multiple')->default(false);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(
                'edit_pleases',
                'view_pleases',
                'edit_roles',
                'view_roles',
                'is_multiple',
                'is_active'
            );
        });
    }
};
