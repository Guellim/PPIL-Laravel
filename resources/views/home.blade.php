@extends('layouts.app')

@section('content')
<br />
<div class="container">
    <div class="row">        
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">

		<?php
		header('Location: /profil');
		exit();
		?>
                <div class="panel-heading">Votre connexion au Système de gestion des enseignements a bien été effectué. Que 
                    voulez-vous faire ?</div>

                <div class="panel-body">
                     <a href="{{ route('logout') }}" onclick="event.preventDefault();   document.getElementById('logout-form').submit();">
                     Déconnexion 
                     </a>
                </div>
                  <div class="panel-body">    
                    <a href="/profil">Acceder à votre profil</a>
            </div>
        </div>
    </div>
</div>
@endsection
