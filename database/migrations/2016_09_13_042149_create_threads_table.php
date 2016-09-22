<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('threads', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('site_id');
            $table->string('name', 512)->nullable();
            $table->string('url', 512);
            $table->integer('status')->default(1); // 1:active, 2:inactive, 3:closed, 4:delete
            $table->string('download_directory', 256)->nullable(); // full absolute path
            $table->dateTime('last_update');
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
        Schema::drop('threads');
    }
}
