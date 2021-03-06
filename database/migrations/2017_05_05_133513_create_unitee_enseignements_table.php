<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUniteeEnseignementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unitee_enseignements', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->string('nom');
            $table->string('description');


            $table->integer('cm_volume_attendu')->unsigned()->default(0);

            $table->integer('td_volume_attendu')->unsigned()->default(0);

            $table->integer('tp_volume_attendu')->unsigned()->default(0);

            $table->integer('ei_volume_attendu')->unsigned()->default(0);


            $table->integer('td_nb_groupes_attendus')->unsigned()->default(0);

            $table->integer('tp_nb_groupes_attendus')->unsigned()->default(0);

            $table->integer('ei_nb_groupes_attendus')->unsigned()->default(0);


            $table->boolean('attente_validation')->default(false);

            $table->integer('id_formation')->unsigned()->nullable();
            $table->foreign('id_formation')->references('id')->on('formations')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unitee_enseignements', function(Blueprint $table) {
            $table->dropForeign(['id_formation']);
        });
    }
}
