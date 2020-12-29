<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWlrleDirectoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wlrle_directories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('directory_id')->nullable();
            $table->foreign('directory_id')->references('id')->on('wlrle_directories')->onDelete('cascade');
            $table->timestamps();

            // indexes
            $table->index('name');
        });

        // insert root element
        DB::table('wlrle_directories')->insert([
            'name' => '',
            'directory_id' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wlrle_directories');
    }
}
