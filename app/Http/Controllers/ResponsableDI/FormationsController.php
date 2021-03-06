<?php

namespace App\Http\Controllers\ResponsableDI;

use App\Notification;
use Illuminate\Support\Facades\Log;
use App\Formation;
use App\ResponsableFormation;
use App\User;
use App\Photos;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\View\View;
use League\Csv\Reader;

class FormationsController
{
    /**
     * Retourne la vue présentant la liste des formations
     *
     * @return View
     */
    public function show()
    {
        $formations = Formation::all();
        $users = User::allValidate();
        /** Récupération des droit de l'utilisateur authentifier pour gérer le menu */
        $userA = Auth::user();
        $respoDI = $userA->estResponsableDI();
        $respoUE = $userA->estResponsableUE();
        $respoForm = $userA->estResponsableForm();
        $photoUrl = Photos::where('id_utilisateur', $userA->id)->first();
        $tmp = null;

        if ($photoUrl != null) {
            $url = $photoUrl->adresse;
            $tmp = explode("images", $url);
        }
        return view('di.formations')->with(['formations' => $formations, 'users' => $users])->with('userA', $userA)->with('photoUrl', $tmp[1])->with('respoDI', $respoDI)->with('respoForm', $respoForm)->with('respoUE', $respoUE);
    }

    /**
     * Ajoute une formation
     *
     * @param Request $req
     *
     * @return mixed
     */
    public function add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'nom' => 'required|string|max:255|unique:formations',
            'description' => 'required|string|max:255',
        ]);

        if (!$validator->fails()) {
            $formation = new Formation();
            $formation->nom = $req->nom;
            $formation->description = $req->description;
            $formation->save();
            return redirect('/di/formations');
        } else {
            return redirect('/di/formations')->withErrors($validator);
        }
    }

    /**
     * Supprime une formation avec un ID
     *
     * @param Request $req
     *
     * @return Response
     */
    public function delete(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id_formation' => 'required|integer|exists:formations,id'
        ]);

        if (!$validator->fails()) {
            $form = Formation::where('id', $req->id_formation)->first();
            $resp = $form->responsable;
            if ($resp) {
                $userA = Auth::user();
                $messageNotif = "La Formation " . $form->nom . " a été supprimée";
                Notification::createNotification($messageNotif, $userA->id, $resp->id_utilisateur);

                $resp->delete();
            }
            $form->delete();
            return response()->json(["message" => "success"]);
        } else {
            return response()->json(["message" => "errors", "errors" => json_encode($validator->messages())]);
        }
    }

    /**
     * Renvoie un CSV contenant la liste des formations
     *
     * @return Response
     */
    public function getFormationsCSV()
    {
        $formations = Formation::all();
        $str = array(array("nom", "description", "responsable"));
        foreach ($formations as $formation) {
            if ($formation->hasResponsable()) {
                array_push($str, array($formation->nom, $formation->description, $formation->responsable->user->email));
            } else {
                array_push($str, array($formation->nom, $formation->description));
            }
        }

        $fichier = fopen("/tmp/formations.csv", "w");

        fprintf($fichier, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($str as $fields) {
            fputcsv($fichier, $fields);
        }

        fclose($fichier);
        return response()->download("/tmp/formations.csv");
    }

    /**
     * Fonction chargé de l'imporation de CSV
     *
     *
     */
    public function importCSV(Request $req)
    {
        $validator = Validator::make(
            [
                'file' => $req->file('file_csv'),
                'extension' => strtolower($req->file('file_csv')->getClientOriginalExtension()),
            ]
            ,
            [
                'file' => 'required',
                'extension' => 'required|in:csv',
            ]
        );



        if ($validator->fails()) {
            return redirect('/di/formations')->withErrors($validator);
        }


        $file = $req->file('file_csv');
        $num_row = 0;
        $csv = Reader::createFromPath($file->path());
        $csv->setDelimiter(',');
        $messages = array();
        $res = $csv
            ->addFilter(function ($row, $index) {
                return $index > 0; //we don't take into account the header
            })
            ->addFilter(function ($row) {
                return isset($row[0], $row[1]); //we make sure the data are present
            })->fetch();

        //TODO checker le header
        $new_formations = array();
        $new_responsables = array();
        foreach ($res as $row) {
            $num_row++;
            $validator = Validator::make([
                'nom' => $row[0],
                'description' => $row[1],
            ], [
                'nom' => 'max:255|required|string|unique:formations,nom',
                'description' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                Log::info('Importation formations : fail nom/description');
                $messages['ligne'] = $num_row;
                $this->importRollback($new_formations, $new_responsables);
                return redirect('/di/formations')->with('messages', $messages)->with('errors', $validator->errors());
            }

            $formation = new Formation;
            $formation->nom = $row[0];
            $formation->description = $row[1];
            Log::info('Ajout de la formation : ' . $formation->nom);
            $formation->save();


            array_push($new_formations, $formation);
            if (isset($row[2]) && is_string($row[2]) && strlen(trim($row[2])) > 2) {
                $row[2] = trim($row[2]);
                $validator_mail = Validator::make([
                    'email' => trim($row[2]),
                ], [
                    'email' => 'exists:users,email'
                ]);

                if ($validator_mail->fails()) {
                    $messages['ligne'] = $num_row;
                    Log::info('Importation formations : fail email responsable');
                    $this->importRollback($new_formations, $new_responsables);
                    return redirect('/di/formations')
                        ->with('messages', $messages)
                        ->with('errors', $validator_mail->errors());
                }

                $resp = new ResponsableFormation;
                $resp->id_formation = $formation->id;
                $user = User::where('email', trim($row[2]))->first();
                $resp->id_utilisateur = $user->id;
                $resp->save();

                $userA = Auth::user();
                $messageNotif = "La Formation " . $formation->nom . " a été ajoutée";
                Notification::createNotification($messageNotif, $userA->id, $resp->id_utilisateur);

                array_push($new_responsables, $resp);
            }

        }

        $messages['success'] = "Importation réussie";
        return redirect('/di/formations')->with('messages', $messages);
    }

    /**
     * Annule les changements faits par l'importation en cas d'erreur
     *
     * @param $new_formations
     * @param $new_responsable
     */
    private function importRollback($new_formations, $new_responsable)
    {
        Log::info('Roolback formations');
        foreach ($new_responsable as $resp) {
            $resp->delete();
        }
        foreach ($new_formations as $form) {
            $form->delete();
        }
    }

    /**
     * Change le responsable d'une UE
     *
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateResponsable(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id_utilisateur' => 'required|integer|exists:users,id',
            'id_formation' => 'required|integer|exists:formations,id',
        ]);

        $user = Auth::user();

        if (!$validator->fails()) {
            $formation = Formation::where('id', $req->id_formation)->first();
            if ($formation->hasResponsable()) {
                $formation->responsable->delete();
            }

            $resp = new ResponsableFormation;
            $resp->id_utilisateur = $req->id_utilisateur;
            $resp->id_formation = $req->id_formation;
            $resp->save();

            $responsable = User::where('id', $resp->id_utilisateur)->first();

            $messageNotif = "Le responsable de " . $formation->nom . " est maintenant " . $responsable->prenom . " " . $responsable->nom ;
            Notification::createNotification($messageNotif, $user->id, $responsable->id);

            return response()->json(["message" => "success", "user" => $resp->user]);
        } else {
            return response()->json(["message" => "errors", "errors" => $validator]);
        }
    }
}