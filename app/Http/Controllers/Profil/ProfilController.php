<?php

namespace App\Http\Controllers\Profil;

use App\Http\Controllers\Controller;
use App\Photos;
use App\Statut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use App\User;
use Illuminate\Validation\Rule;
use Validator;

class ProfilController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //route accessible que si l'utilisateur est authentifié
        $this->middleware('auth');
    }



    public function show(){


        /** Récupération des droit de l'utilisateur authentifier pour gérer le menu */
        $userA = Auth::user();
	    $respoDI = $userA->estResponsableDI();
        $respoUE = $userA->estResponsableUE();
        
        $photoUrl =  Photos::where('id_utilisateur', $userA->id)->first();

        $statuts = Statut::all();

        $civilite = User::select('civilite')->where('id', '=', $userA->id)->first();
        if ($civilite->civilite == "M") $civilites = array("M" => "M","Mme" => "Mme");
        else $civilites = array("Mme" => "Mme", "M" => "M");

        $photoUrl =  Photos::where('id_utilisateur', $userA->id)->first();

        $tmp = null;

        if ($photoUrl != null){
            $url = $photoUrl->adresse;
            $tmp = explode("images", $url);
        }
        
        $statuts = Statut::all();
        

        return view('profil')
            ->with('userA', $userA)
            ->with('statuts', $statuts)
            ->with('civilites', $civilites)
            ->with('photoUrl', $tmp[1])
            ->with('respoDI', $respoDI)
            ->with('respoUE', $respoUE);

    }



    public static function getStatut(){
        $user = Auth::user();

        $statut = Statut::select('statut')->where('id', '=', $user->id_statut)->first();

        return $statut->statut;
    }




    public function postEmail(Request $request){
        $user = Auth::user();

        $user->updateEmail($request->input('email'));
    }





    public function postUpdateInformations(Request $request) {

        // Authentification de l'utilisateur
        $user = Auth::user();

        // Validation des champs
        $validator = Validator::make($request->all(), [
            'nom' => 'string|max:255|alpha',
            'prenom' => 'string|max:255|alpha',
            'statut' => 'string', Rule::in(["ATER", "PRAG", "Enseignant chercheur", "Doctorant", "Vacataire", "Aucun"]),
            'civilite' => 'string', Rule::in(["M", "Mme"]),
            'adresse' => 'string|max:255',
            'email' => 'string|email|max:255'
        ]);

        // Si la verification a echoue
        if ($validator->fails()) {
            $messages = "Impossible de modifier vos informations, un des champs spécifiés n'est pas valide";
            return redirect('profil')
                ->with('messages', $messages);
        }
        else {

            // Mise a jour des champs
            $user->updateNom($request->input('nom'));
            $user->updatePrenom($request->input('prenom'));
            $user->updateStatut($request->input('statut') + 1);
            $user->updateCivilite($request->input('civilite'));
            $user->updateAdresse($request->input('adresse'));
            $user->updateEmail($request->input('email'));

            $messages = "Informations modifiées avec succès";

            return redirect('profil')
                ->with('messages', $messages);

        }
    }





    public function postPassword(Request $request){
        //TODO : mettre un beau message sur la vue
        if ($request->input('password') != $request->input('check_password')){

            return redirect('profil')->with('password_message', 'Les deux mot de passe entrés sont différents');

        }else {

            $user = Auth::user();
            $user->updatePassword(bcrypt($request->input('password')));
            $messages = "Mot de passe modifié avec succès";

            return redirect('profil')
                ->with('password_message', 'Mot de passe modifié avec succé')
                ->with('messages', $messages);;
        }
    }





    public function postImage(Request $request){

        //TODO : modifier le bouton parcourir de la vue

        $user = Auth::user();
        $file = Input::file('image');

        $infos = pathinfo($file->getClientOriginalName());
        $extension = $infos['extension'];


        if ($extension == 'png' || $extension == 'jpg'){

            //on supprime l'ancienne adresse de l'image
            Photos::where('id_utilisateur', $user->id)->delete();

            //stocke l'adresse de l'image dans la BDD
            Photos::creerImage(public_path().'/images/user_'.$user->id.'/profil.' . $extension, $user->id);

            // stocke l'image
            $file->move(public_path().'/images/user_'.$user->id, 'profil.' . $extension);

            $photoUrl =  Photos::where('id_utilisateur', $user->id)->first()->adresse;
            $tmp = explode("images", $photoUrl);
            $messages = "Photographie de profil modifiée avec succès";

            return redirect('profil')
                ->with('photoUrl', $tmp[1])
                ->with('messages', $messages);

        } else{

            return redirect('profil')->with('image_message', 'Format du fichier invalide: "' . $extension . '"');
        }
    }
}
