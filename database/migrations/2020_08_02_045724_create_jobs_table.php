<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('pickup')->nullable();
            $table->string('dropoff')->nullable();
            $table->enum('status', ['pending', 'inprogress', 'pickedup', 'completed', 'cancelled'])->default('pending');
            $table->decimal('pickup_lng', 11, 8);
            $table->decimal('pickup_lat', 10, 8);
            $table->decimal('dropoff_lng', 11, 8);
            $table->decimal('dropoff_lat', 10, 8);
            $table->string('item')->nullable();
            $table->integer('quantity');
            $table->string('reference_no')->nullable();
            $table->enum('payment_method', ['credit_card', 'wallet', 'cash', 'banktransfer'])->default('credit_card');
            $table->enum('spatch_type', ['bike', 'van', 'bicycle'])->default('bike');
            $table->decimal('distance', 17, 6);
            $table->boolean('payment_status')->default(0);
            $table->boolean('rating_status')->default(0);
            $table->text('delivery_note')->nullable();
            $table->string('reciever_email')->nullable();
            $table->string('reciever_phone')->nullable();
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
        Schema::dropIfExists('jobs');
    }
}
