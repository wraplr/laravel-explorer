<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWlrleFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wlrle_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('path');
            $table->string('file');
            $table->string('extension');
            $table->string('mime_type');
            $table->integer('size');
            $table->unsignedBigInteger('directory_id')->nullable();
            $table->foreign('directory_id')->references('id')->on('wlrle_directories')->onDelete('cascade');
            $table->timestamps();

            // indexes
            $table->index(['name', 'file']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wlrle_files');
    }
}
