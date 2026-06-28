<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geometry_dash_songs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('song_id');
            $table->string('name');
            $table->unsignedBigInteger('artist_id')->nullable();
            $table->string('artist_name');
            $table->decimal('size');
            $table->string('video_id')->nullable();
            $table->string('youtube_url')->nullable();
            $table->boolean('is_verified')->nullable();
            $table->bigInteger('prriority')->nullable();
            $table->string('download_link')->nullable();
            $table->unsignedTinyInteger('type')->nullable();
            $table->string('extra_artist_ids')->nullable();
            $table->boolean('is_new')->nullable();
            $table->unsignedTinyInteger('new_type')->nullable();
            $table->string('extra_artist_mappings')->nullable();
            $table->string('download_soundtrack_override')->nullable();
            $table->boolean('is_disabled');
            $table->boolean('is_outdated');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geometry_dash_songs');
    }
};
