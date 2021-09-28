<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->integer('vnr');
            $table->integer('semester');
            $table->string('title');
            $table->boolean('aktiv');
            $table->integer('sws')->nullable();
            $table->string('type');
            $table->string('targets')->nullable();
            $table->integer('rythm')->nullable();
            $table->boolean('changed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
}
