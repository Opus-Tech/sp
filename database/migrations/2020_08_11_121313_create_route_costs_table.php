<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_costs', function (Blueprint $table) {
            $table->id();
            $table->decimal('fuel_cost', 9, 2);
            $table->decimal('rider_salary', 9, 2);
            $table->decimal('bike_fund', 9, 2);
            $table->decimal('ops_fee', 9, 2);
            $table->decimal('spatch_log', 9, 2);
            $table->decimal('spatch_disp', 9, 2);
            $table->integer('min_km');
            $table->integer('max_km');
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
        Schema::dropIfExists('route_costs');
    }
}
