<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstPosTable extends Migration
{
    public function up()
    {
        Schema::create('est_pos', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Device name');
            $table->string('type')->comment('Device type');
            $table->string('ref')->comment('Reference number');
            $table->foreignId('establishment_id')->constrained('est_establishments')->onDelete('cascade')->comment('Branch ID');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('est_pos');
    }
}
