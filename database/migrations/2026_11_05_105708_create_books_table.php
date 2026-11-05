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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('openlibrary_id')->unique();
            $table->string('title');
            $table->unsignedSmallInteger('first_publish_year')->nullable();
            $table->unsignedBigInteger('cover_id')->nullable();
            $table->unsignedInteger('page_count_median')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
