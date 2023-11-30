<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOtpToPasswordResetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('password_resets', function (Blueprint $table) {
            $table->string('otp')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        if (Schema::hasTable('password_resets')) {
            Schema::table('password_resets', function (Blueprint $table) {
                if (Schema::hasColumn('password_resets', 'email')) {
                    $table->renameColumn('email', 'phone');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('password_resets', function (Blueprint $table) {
            //
        });
    }
}
