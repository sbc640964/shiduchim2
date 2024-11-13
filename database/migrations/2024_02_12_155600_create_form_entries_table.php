<?php

use App\Models\Form;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Form::class)->constrained('forms');
            $table->json('data');
            $table->morphs('model');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_entries');
    }
};
