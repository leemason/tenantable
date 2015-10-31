<?php

use \Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTenantsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tenants', function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('uuid', 255)->unique();
            $table->string('domain')->unique();
            $table->text('driver');
            $table->text('host');
            $table->text('database');
            $table->text('username');
            $table->text('password');
            $table->string('prefix');
            $table->text('meta');
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
        //
        Schema::drop('tenants');
    }

}
