<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('thread_id');
            $table->string('url', 512); // complete url of the image
            $table->string('size', 64)->nullable(); // image size in bytes
            $table->string('name', 128)->nullable(); // name of image complete with the extension
            $table->integer('download_status')->default(0); // 0:not downloaded yet, 1:downloaded, 2:block, 3:modified
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
        Schema::drop('images');
    }
}
