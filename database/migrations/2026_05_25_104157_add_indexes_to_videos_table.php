<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {

            $table->index('language');

            $table->index('keyword');

            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {

            $table->dropIndex(['language']);

            $table->dropIndex(['keyword']);

            $table->dropIndex(['published_at']);
        });
    }
};