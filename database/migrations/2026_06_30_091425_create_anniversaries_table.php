<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('anniversaries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('date');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anniversaries');
    }
};
