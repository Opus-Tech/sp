<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerificationCodeToRidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->string('verification_code')->nullable()->after('current_lat');
        });

        if (Schema::hasTable('riders')) {
            Schema::table('riders', function (Blueprint $table) {
                if (Schema::hasColumn('riders', 'current_lng')) {
                    $table->decimal('current_lng', 11, 8)->default(0)->change();
                }
                if (Schema::hasColumn('riders', 'current_lat')) {
                    $table->decimal('current_lat', 10, 8)->default(0)->change();
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
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn('verification_code');
        });
    }
}
