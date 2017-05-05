<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            // --- Attribut par défaut --- //
            $table->increments('id');
            //$table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            // -------------------------- //
            $table->string('nom');
            $table->string('prenom');
            $table->string('civilite');
            
            $table->string('adresse');
            
            $table->boolean('attente_validation');
            $table->integer('prochaine_modif_en_attente');
            $table->date('derniere_modif');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
