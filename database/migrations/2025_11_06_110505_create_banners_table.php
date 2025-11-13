<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('heading')->nullable();
            $table->longText('body')->nullable();
            $table->timestamp('published_at');
            $table->timestamp('expires_at')->nullable();
            $table->json('locations')->default(DB::raw('(JSON_ARRAY())'));
            $table->json('locations_data')->default(DB::raw('(JSON_ARRAY())'));
            $table->json('config')->default(DB::raw('(JSON_OBJECT())'));
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
