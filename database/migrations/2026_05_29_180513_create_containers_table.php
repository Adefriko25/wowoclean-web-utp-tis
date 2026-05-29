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
    Schema::create('containers', function (Blueprint $table) {
        $table->string('container_id')->primary();
        $table->string('waste_type');
        $table->float('weight_kg');
        $table->string('status')->default('Active');
        $table->timestamps();
    });
}
};
