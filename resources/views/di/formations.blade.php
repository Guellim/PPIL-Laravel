@extends('layouts.main')
@section('title')
    Formations
@stop
@section('content')

    <div class="card">
        <div class="card-content" id="content">


            <div class="row">
                <h3 class="header s12 orange-text center">Formations</h3>
            </div>

            <div class="row" id="liste-formation">
                <div v-for="formation in formations">
                    <formations-main-new
                            :getmodalmodifid="getModalModifId"
                            :getmodalsuppid="getModalSuppId"
                            :openmodal="openModal"
                            :responsable="responsable"
                            :formationarg="formation">
                    </formations-main-new>
                    <formations-modal-suppression
                            :getmodalsuppid="getModalSuppId"
                            :deleteformation="deleteFormation"
                            :formationarg="formation">
                    </formations-modal-suppression>
                    <formations-modal-modification-responsable
                        :formationarg="formation"
                        :users="users"
                        :modifierresponsable="modifierResponsable"
                        :getmodalmodifid="getModalModifId">
                    </formations-modal-modification-responsable>
                </div>

                <formations-modal-ajout :token="token" :submitformadd="submitFormAdd"></formations-modal-ajout>

            </div>

            <div id="modal_export" class="modal">
                <div class="modal-content">
                    <h4>Exportation des données</h4>
                    <p>Les données concernant les utilisateur seront exportées au format CSV</p>
                </div>


                <div class="modal-footer">
                    <a href="/di/formations.csv" onclick="makeToast('Exportation réussie')"
                       class="modal-action modal-close waves-effect waves-green btn-flat blue-text">Exporter</a>
                    <a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat red-text">Annuler</a>
                </div>
            </div>

            <div id="modal_import" class="modal">
                <div class="modal-content">
                    <div class="row">
                        <h4>Importation des données</h4>
                        <p>Les données importées doivent être au format CSV. Un header doit être présent et le
                            séparateur doit
                            être ;</p>
                        <p>Le format à respecter est le suivant : <br><strong>nom ; description ; email du responsable</strong></p>
                    </div>
                    <div class="row">
                        <form id="form-import" method="post" action="/di/formations/importCSV" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <div class="file-field input-field">
                                <div class="btn purple">
                                    <span>Choisir un fichier</span>
                                    <input type="file" name="file_csv">
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" type="text">
                                </div>
                            </div>
                        </form>
                    </div>

                </div>


                <div class="modal-footer">
                    <a onclick="event.preventDefault();document.getElementById('form-import').submit();" href="#!" class="btn-large modal-action modal-close waves-effect waves-light btn-flat
               purple-text">Importer</a>
                    <a href="#!"
                       class="modal-action modal-close waves-effect waves-light btn-flat btn-large red-text">Annuler</a>
                </div>
            </div>




        </div>
    </div>








    @include('includes.buttonImportExportAdd')



    <script src="/js/jquery-2.1.1.min.js"></script>
    <script src="/js/materialize.js"></script>
    <script src="/js/vue.min.js"></script>
    <script src="/js/di/FormationsDIComponents.js"></script>
    <script src="/js/utils.js"></script>

    <script>

        var listeFormationsVue = new Vue({
            delimiters: ['${', '}'],
            el: "#liste-formation",
            data: {
                formations: [
                    @foreach(App\Formation::all() as $formation)
                    {
                        nom: "{{$formation->nom}}",
                        description: "{{$formation->description}}",
                        id: {{$formation->id}},
                        @if(isset($formation->responsable))
                            id_responsable: {{$formation->responsable->id_utilisateur}}
                        @else
                            id_responsable: 0
                        @endif
                    },
                    @endforeach
                ],
                users: [
                    @foreach(App\User::allValidate() as $user)
                    {
                        nom: "{{ $user->nom  }}",
                        prenom: "{{ $user->prenom  }}",
                        email: "{{ $user->email  }}",
                        id: {{ $user->id  }},
                    },
                    @endforeach
                ],
            },
            computed: {
                token: function() {
                    return $('meta[name="csrf-token"]').attr('content');
                }
            },

            methods: {

                responsable: function (id_formation) {
                    var formation = this.getFormation(id_formation);
                    if (formation === null) return '';
                    var user = this.getUser(formation.id_responsable);
                    console.log('User obtenue : ' + JSON.stringify(user));
                    if (user === null) return 'Aucun responsable';
                    return user.prenom + " " + user.nom;
                },

                getFormation: function (id_formation) {
                    var res = null;
                    $.each(this.formations, function (index, value) {
                        if (value.id === id_formation) {
                            console.log('Formation : ' + JSON.stringify(value));
                            res = value;
                        }
                    });
                    return res;
                },

                getUser: function (id_user) {
                    var res = null;
                    $.each(this.users, function (index, value) {
                        if (value.id == id_user) {
                            console.log('User : ' + JSON.stringify(value));
                            res = value;
                        }
                    });
                    return res;
                },

                openModal: function(id_formation) {
                    console.log('modal open');
                    $('#'+id_formation).modal('open');
                },

                deleteFormation : function(id_formation) {
                    console.log('Delete Formation : ' + id_formation)
                    var deleteFormationWithId = this.deleteFormationWithId;
                    $.ajax({
                        url: "/di/formations/delete",
                        method: "post",
                        data: "id_formation="+id_formation
                    }).done(function (msg) {
                        if (msg['message'] === 'success') {
                            deleteFormationWithId(id_formation);
                            makeToast("Suppression de la formation réussie");
                        } else {
                            // TODO Message d'erreur
                        }
                    }).fail(function (xhr, msg) {
                        makeToast('Erreur serveur : ' + xhr['status'])
                    });
                },

                deleteFormationWithId: function(id_formation) {
                    var formations = this.formations;
                    $.each(this.formations, function(index, value) {
                        if (value.id === id_formation) {
                            Vue.delete(formations, index);
                            return;
                        }
                    });
                },

                submitFormAdd: function() {
                    $('#form-add').submit();
                },

                getModalSuppId: function(id_formation) {
                    return 'modal-supp-' + id_formation;
                },

                getModalModifId: function(id_formation) {
                    return 'modal-modif-' + id_formation;
                },

                modifierResponsable(id_formation, id_utilisateur) {
                    console.log('Formation : ' + id_formation)
                    console.log('User : ' + id_utilisateur)
                    var getModalModifId = this.getModalModifId;
                    var getFormationWithId = this.getFormation;
                    $.ajax({
                        url: "/di/formations/updateResponsable",
                        method: "post",
                        data: "id_utilisateur=" + id_utilisateur + "&id_formation=" + id_formation
                    }).done(function (msg) {
                        console.log(msg);
                        if (msg['message'] === 'success') {
                            var formation = getFormationWithId(id_formation);
                            formation.id_responsable = id_utilisateur;
                            makeToast("Modification du responsable réussie");
                            console.log('id : ' + getModalModifId(id_formation));
                            $('#' + getModalModifId(id_formation)).modal('close');
                        } else {

                        }
                    }).fail(function (xhr, msg) {
                        makeToast('Erreur serveur : ' + xhr['status'])
                    })
                },
            }
        });


        $(document).ready(function () {

            // Toast pour action réussie

            @if (Session::get('messages') !== null && isset(Session::get('messages')['success']))
                makeToast('{{Session::get('messages')["success"]}}');
            @endif


            // Toast pour les erreurs

            @foreach($errors->all() as $error)
                @if (Session::get('messages') !== null)
                    makeToast('{{$error}} (ligne {{Session::get('messages')["ligne"]}})');
                @else
                    makeToast('{{$error}}');
                @endif
            @endforeach

            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
                }
            });
        });
    </script>
@stop

