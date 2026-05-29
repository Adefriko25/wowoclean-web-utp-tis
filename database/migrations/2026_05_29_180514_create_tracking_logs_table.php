<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('tracking_logs', function (Blueprint $table) {
        $table->id();
        $table->string('container_id');
        $table->string('location');
        $table->string('description');
        $table->timestamps();
        $table->foreign('container_id')->references('container_id')->on('containers')->onDelete('cascade');
    });
}
};  