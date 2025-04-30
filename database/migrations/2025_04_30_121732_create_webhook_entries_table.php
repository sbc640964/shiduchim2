<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_entries', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->json('headers');
            $table->json('body');
            $table->boolean('is_completed')->default(false);
            $table->json('error')->nullable();
            $table->timestamps();
        });
    }
};
