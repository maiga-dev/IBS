<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\MaintenanceTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class MaintenanceController extends AbstractActionController
{
    use CsrfTrait;
    use RoleTrait;

    public function __construct(
        private MaintenanceTable $maintenanceTable
    ) {
    }

    public function indexAction(): ViewModel
    {
        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }

        $utilisateur = $this->utilisateurConnecte();

        if (!$utilisateur) {
            return $this->redirect()->toRoute(
                'auth',
                ['action' => 'login']
            );
        }

        $role = strtolower(
            trim((string) ($utilisateur['role'] ?? ''))
        );

        $sessions = $role === 'technicien'
            ? $this->maintenanceTable->fetchSessionsPourTechnicien(
                (int) $utilisateur['id']
            )
            : $this->maintenanceTable->fetchSessions();

        return new ViewModel([
            'sessions' => $sessions,
            'utilisateur' => $utilisateur,
            'csrf_token' => $this->genererCsrfToken(),
        ]);
    }

    public function addAction(): ViewModel
    {
        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }

        if (!$this->estAdministrateurConnecte()) {
            return $this->redirect()->toRoute('home');
        }

        $request = $this->getRequest();
        $erreurs = [];
        $postData = $request->getPost()->toArray();

        /*
         * Données indépendantes pour la cascade :
         *
         * Pays
         *   ↓
         * Ville
         *   ↓
         * Site
         *   ↓
         * Centrale
         */
        $pays = $this->maintenanceTable->getPays();
        $villes = $this->maintenanceTable->getVilles();
        $sites = $this->maintenanceTable->getSites();
        $centrales = $this->maintenanceTable->getCentrales();

        if ($request->isPost()) {
            if (
                !$this->csrfValide(
                    $request->getPost('csrf_token')
                )
            ) {
                $erreurs[] =
                    'Session expirée, merci de réessayer.';
            }

            $idCentrale = (int) $request->getPost(
                'id_centrale',
                0
            );

            $dateSession = trim(
                (string) $request->getPost(
                    'date_session',
                    ''
                )
            );

            $typeMaintenance = trim(
                (string) $request->getPost(
                    'type_maintenance',
                    ''
                )
            );

            $diagnosticInitial = trim(
                (string) $request->getPost(
                    'diagnostic_initial',
                    ''
                )
            );

            $compteRendu = trim(
                (string) $request->getPost(
                    'compte_rendu',
                    ''
                )
            );

            $commentaire = trim(
                (string) $request->getPost(
                    'commentaire',
                    ''
                )
            );

            if ($idCentrale <= 0) {
                $erreurs[] =
                    'La centrale est obligatoire.';
            }

            if ($dateSession === '') {
                $erreurs[] =
                    'La date est obligatoire.';
            }

            if ($diagnosticInitial === '') {
                $erreurs[] =
                    'Le premier diagnostic est obligatoire.';
            }

            $fichier = $this->getUploadedFile($request);

            if ($fichier !== null) {
                $erreurFichier =
                    $this->validerFichierMaintenance(
                        $fichier
                    );

                if ($erreurFichier !== null) {
                    $erreurs[] = $erreurFichier;
                }
            }

            if (!$erreurs) {
                try {
                    $idSession =
                        $this->maintenanceTable->creerSession(
                            $idCentrale,
                            $dateSession,
                            $typeMaintenance !== ''
                                ? $typeMaintenance
                                : null,
                            $diagnosticInitial,
                            $compteRendu !== ''
                                ? $compteRendu
                                : null,
                            $commentaire !== ''
                                ? $commentaire
                                : null
                        );

                    if ($fichier !== null) {
                        $saved =
                            $this->enregistrerFichierSession(
                                $idSession,
                                $fichier
                            );

                        if ($saved !== null) {
                            $this->maintenanceTable
                                ->ajouterPieceJointeSession(
                                    $idSession,
                                    $saved['url'],
                                    $saved['type'],
                                    'Pièce initiale',
                                    'initiale'
                                );
                        }
                    }

                    return $this->redirect()->toRoute(
                        'maintenance',
                        [
                            'action' => 'detail',
                            'id' => $idSession,
                        ]
                    );
                } catch (\Throwable $e) {
                    error_log(
                        '[MaintenanceController::addAction] '
                        . $e->getMessage()
                    );

                    $erreurs[] =
                        'Erreur lors de la création de la maintenance : '
                        . $e->getMessage();
                }
            }
        }

        return new ViewModel([
            /*
             * Nouvelles listes indépendantes.
             */
            'pays' => $pays,
            'villes' => $villes,
            'sites' => $sites,
            'centrales' => $centrales,

            'postData' => $postData,
            'erreurs' => $erreurs,
            'csrf_token' => $this->genererCsrfToken(),
        ]);
    }

    public function editAction(): ViewModel
    {
        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }

        if (!$this->estAdministrateurConnecte()) {
            return $this->redirect()->toRoute('home');
        }

        $idSession = (int) $this->params()
            ->fromRoute('id', 0);

        if ($idSession <= 0) {
            return $this->notFoundAction();
        }

        $session =
            $this->maintenanceTable->findSession(
                $idSession
            );

        if (!$session) {
            return $this->notFoundAction();
        }

        $request = $this->getRequest();
        $erreurs = [];

        /*
         * Même logique que pour l'ajout :
         * chaque niveau de localisation est récupéré
         * indépendamment.
         */
        $pays = $this->maintenanceTable->getPays();
        $villes = $this->maintenanceTable->getVilles();
        $sites = $this->maintenanceTable->getSites();
        $centrales = $this->maintenanceTable->getCentrales();

        /*
         * Valeurs initiales.
         */
        $postData = [
            'id_centrale' => (string) (
                $session['id_centrale'] ?? ''
            ),

            'date_session' => substr(
                (string) (
                    $session['date_session'] ?? ''
                ),
                0,
                10
            ),

            'type_maintenance' => (string) (
                $session['type_maintenance'] ?? ''
            ),

            'diagnostic_initial' => (string) (
                $session['diagnostic_initial'] ?? ''
            ),

            'compte_rendu' => (string) (
                $session['compte_rendu_initial'] ?? ''
            ),

            'commentaire' => (string) (
                $session['commentaire_initial'] ?? ''
            ),
        ];

        if ($request->isPost()) {
            if (
                !$this->csrfValide(
                    $request->getPost('csrf_token')
                )
            ) {
                $erreurs[] =
                    'Session expirée, merci de réessayer.';
            }

            /*
             * On récupère la centrale envoyée par le formulaire.
             */
            $idCentrale = (int) $request->getPost(
                'id_centrale',
                0
            );

            $dateSession = trim(
                (string) $request->getPost(
                    'date_session',
                    ''
                )
            );

            $typeMaintenance = trim(
                (string) $request->getPost(
                    'type_maintenance',
                    ''
                )
            );

            $diagnosticInitial = trim(
                (string) $request->getPost(
                    'diagnostic_initial',
                    ''
                )
            );

            $compteRendu = trim(
                (string) $request->getPost(
                    'compte_rendu',
                    ''
                )
            );

            $commentaire = trim(
                (string) $request->getPost(
                    'commentaire',
                    ''
                )
            );

            if ($idCentrale <= 0) {
                $erreurs[] =
                    'La centrale est obligatoire.';
            }

            if ($dateSession === '') {
                $erreurs[] =
                    'La date est obligatoire.';
            }

            if ($diagnosticInitial === '') {
                $erreurs[] =
                    'Le premier diagnostic est obligatoire.';
            }

            $fichier = $this->getUploadedFile(
                $request
            );

            if ($fichier !== null) {
                $erreurFichier =
                    $this->validerFichierMaintenance(
                        $fichier
                    );

                if ($erreurFichier !== null) {
                    $erreurs[] = $erreurFichier;
                }
            }

            /*
             * Toujours conserver les valeurs envoyées
             * en cas d'erreur.
             */
            $postData = [
                'id_centrale' => (string) $idCentrale,
                'date_session' => $dateSession,
                'type_maintenance' => $typeMaintenance,
                'diagnostic_initial' => $diagnosticInitial,
                'compte_rendu' => $compteRendu,
                'commentaire' => $commentaire,
            ];

            if (!$erreurs) {
                try {
                    $this->maintenanceTable->modifierSession(
                        $idSession,
                        $idCentrale,
                        $dateSession,
                        $typeMaintenance !== ''
                            ? $typeMaintenance
                            : null,
                        $diagnosticInitial,
                        $compteRendu !== ''
                            ? $compteRendu
                            : null,
                        $commentaire !== ''
                            ? $commentaire
                            : null
                    );

                    if ($fichier !== null) {
                        $saved =
                            $this->enregistrerFichierSession(
                                $idSession,
                                $fichier
                            );

                        if ($saved !== null) {
                            $this->maintenanceTable
                                ->ajouterPieceJointeSession(
                                    $idSession,
                                    $saved['url'],
                                    $saved['type'],
                                    'Pièce ajoutée lors de la modification',
                                    'initiale'
                                );
                        }
                    }

                    return $this->redirect()->toRoute(
                        'maintenance',
                        [
                            'action' => 'detail',
                            'id' => $idSession,
                        ]
                    );
                } catch (\Throwable $e) {
                    error_log(
                        '[MaintenanceController::editAction] '
                        . $e->getMessage()
                    );

                    $erreurs[] =
                        'Erreur lors de la modification : '
                        . $e->getMessage();
                }
            }

            /*
             * Mise à jour des valeurs de session pour
             * conserver l'affichage après une erreur.
             */
            $session['id_centrale'] = $idCentrale;
            $session['date_session'] = $dateSession;
            $session['type_maintenance'] = $typeMaintenance;
            $session['diagnostic_initial'] =
                $diagnosticInitial;
            $session['compte_rendu_initial'] =
                $compteRendu;
            $session['commentaire_initial'] =
                $commentaire;
        }

        return new ViewModel([
            'session' => $session,

            /*
             * Données indépendantes pour la cascade.
             */
            'pays' => $pays,
            'villes' => $villes,
            'sites' => $sites,
            'centrales' => $centrales,

            'postData' => $postData,
            'erreurs' => $erreurs,
            'csrf_token' => $this->genererCsrfToken(),
        ]);
    }

    public function detailAction(): ViewModel
    {
        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }

        $utilisateur = $this->utilisateurConnecte();

        if (!$utilisateur) {
            return $this->redirect()->toRoute(
                'auth',
                ['action' => 'login']
            );
        }

        $idSession = (int) $this->params()
            ->fromRoute('id', 0);

        $session =
            $this->maintenanceTable->findSession(
                $idSession
            );

        if (!$session) {
            return $this->notFoundAction();
        }

        $role = strtolower(
            trim((string) (
                $utilisateur['role'] ?? ''
            ))
        );

        $estAdministrateur = in_array(
            $role,
            ['admin', 'administrateur'],
            true
        );

        return new ViewModel([
            'session' => $session,

            'equipements' =>
                $this->maintenanceTable
                    ->getEquipementsDeSession(
                        $idSession
                    ),

            'techniciens' =>
                $estAdministrateur
                    ? $this->maintenanceTable
                        ->getTechniciens()
                    : [],

            'utilisateur' => $utilisateur,

            'csrf_token' =>
                $this->genererCsrfToken(),
        ]);
    }

    private function sessionActionView(
        int $idSession,
        string $operation
    ): ViewModel {
        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }

        $utilisateur = $this->utilisateurConnecte();

        if (!$utilisateur) {
            return $this->redirect()->toRoute(
                'auth',
                ['action' => 'login']
            );
        }

        $session =
            $this->maintenanceTable->findSession(
                $idSession
            );

        if (!$session) {
            return $this->notFoundAction();
        }

        $role = strtolower(
            trim((string) (
                $utilisateur['role'] ?? ''
            ))
        );

        $admin = in_array(
            $role,
            ['admin', 'administrateur'],
            true
        );

        if ($operation === 'affecter' && !$admin) {
            return $this->redirect()->toRoute(
                'home'
            );
        }

        if (
            $operation !== 'affecter'
            && !in_array(
                $role,
                [
                    'admin',
                    'administrateur',
                    'technicien',
                ],
                true
            )
        ) {
            return $this->redirect()->toRoute(
                'home'
            );
        }

        if (
            $operation !== 'affecter'
            && $role === 'technicien'
            && (int) (
                $session['id_technicien'] ?? 0
            ) !== (int) (
                $utilisateur['id'] ?? 0
            )
        ) {
            return $this->redirect()->toRoute(
                'home'
            );
        }

        $erreurs = [];
        $postData = [];

        $techniciens = $admin
            ? $this->maintenanceTable
                ->getTechniciens()
            : [];

        if ($this->getRequest()->isPost()) {
            if (
                !$this->csrfValide(
                    $this->getRequest()
                        ->getPost('csrf_token')
                )
            ) {
                $erreurs[] =
                    'Session expirée, merci de réessayer.';
            }

            $postData = $this->getRequest()
                ->getPost()
                ->toArray();

            if (!$erreurs) {
                try {
                    if ($operation === 'affecter') {
                        $idTechnicien = (int) (
                            $this->getRequest()
                                ->getPost(
                                    'id_technicien',
                                    0
                                )
                        );

                        if ($idTechnicien <= 0) {
                            throw new \InvalidArgumentException(
                                'Sélectionnez un technicien.'
                            );
                        }

                        $this->maintenanceTable
                            ->affecterTechnicienSession(
                                $idSession,
                                $idTechnicien
                            );
                    } else {
                        $justification = trim(
                            (string) $this->getRequest()
                                ->getPost(
                                    'justification',
                                    ''
                                )
                        );

                        $diagnosticFinal = trim(
                            (string) $this->getRequest()
                                ->getPost(
                                    'diagnostic_final',
                                    ''
                                )
                        );

                        $compteRendu = trim(
                            (string) $this->getRequest()
                                ->getPost(
                                    'compte_rendu',
                                    ''
                                )
                        );

                        $commentaire = trim(
                            (string) $this->getRequest()
                                ->getPost(
                                    'commentaire',
                                    ''
                                )
                        );

                        $statutsOperation = [
                            'demarrer' => 'en_cours',
                            'suspendre' => 'suspendue',
                            'arreter' => 'arretee',
                            'terminer' => 'terminee',
                        ];

                        $nouveauStatut =
                            $statutsOperation[$operation]
                            ?? null;

                        if ($nouveauStatut === null) {
                            throw new \InvalidArgumentException(
                                'Action inconnue.'
                            );
                        }

                        $fichier =
                            $this->getUploadedFile(
                                $this->getRequest()
                            );

                        if ($nouveauStatut === 'terminee') {
                            if ($fichier === null) {
                                throw new \InvalidArgumentException(
                                    'Au moins une photo ou un PDF est obligatoire pour terminer la maintenance.'
                                );
                            }
                        }

                        if ($fichier !== null) {
                            $erreurFichier =
                                $this->validerFichierMaintenance(
                                    $fichier
                                );

                            if ($erreurFichier !== null) {
                                throw new \InvalidArgumentException(
                                    $erreurFichier
                                );
                            }
                        }

                        $this->maintenanceTable
                            ->changerStatutSession(
                                $idSession,
                                $nouveauStatut,
                                $justification !== ''
                                    ? $justification
                                    : null,
                                $diagnosticFinal !== ''
                                    ? $diagnosticFinal
                                    : null,
                                $compteRendu !== ''
                                    ? $compteRendu
                                    : null,
                                $commentaire !== ''
                                    ? $commentaire
                                    : null
                            );

                        if ($fichier !== null) {
                            $saved =
                                $this->enregistrerFichierSession(
                                    $idSession,
                                    $fichier
                                );

                            if ($saved !== null) {
                                $this->maintenanceTable
                                    ->ajouterPieceJointeSession(
                                        $idSession,
                                        $saved['url'],
                                        $saved['type'],
                                        $nouveauStatut === 'terminee'
                                            ? 'Pièce du rapport final'
                                            : 'Pièce de maintenance',
                                        $nouveauStatut === 'terminee'
                                            ? 'finale'
                                            : 'intervention'
                                    );
                            }
                        }
                    }

                    return $this->redirect()->toRoute(
                        'maintenance',
                        [
                            'action' => 'detail',
                            'id' => $idSession,
                        ]
                    );
                } catch (\Throwable $e) {
                    error_log(
                        '[MaintenanceController::sessionActionView] '
                        . $e->getMessage()
                    );

                    $erreurs[] = $e->getMessage();
                }
            }
        }

        $view = new ViewModel([
            'session' => $session,
            'operation' => $operation,
            'techniciens' => $techniciens,
            'postData' => $postData,
            'erreurs' => $erreurs,
            'csrf_token' => $this->genererCsrfToken(),
        ]);

        $view->setTemplate(
            'application/maintenance/action'
        );

        return $view;
    }

    public function affecterAction(): ViewModel
    {
        return $this->sessionActionView(
            (int) $this->params()->fromRoute('id', 0),
            'affecter'
        );
    }

    public function demarrerAction(): ViewModel
    {
        return $this->sessionActionView(
            (int) $this->params()->fromRoute('id', 0),
            'demarrer'
        );
    }

    public function suspendreAction(): ViewModel
    {
        return $this->sessionActionView(
            (int) $this->params()->fromRoute('id', 0),
            'suspendre'
        );
    }

    public function arreterAction(): ViewModel
    {
        return $this->sessionActionView(
            (int) $this->params()->fromRoute('id', 0),
            'arreter'
        );
    }

    public function terminerAction(): ViewModel
    {
        return $this->sessionActionView(
            (int) $this->params()->fromRoute('id', 0),
            'terminer'
        );
    }

    public function affecterTechnicienAction()
    {
        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }

        if (!$this->estAdministrateurConnecte()) {
            return $this->redirect()->toRoute('home');
        }

        $request = $this->getRequest();

        if (
            !$request->isPost()
            || !$this->csrfValide(
                $request->getPost('csrf_token')
            )
        ) {
            return $this->redirect()->toRoute(
                'maintenance'
            );
        }

        $idMaintenance = (int) $request->getPost(
            'id_maintenance',
            0
        );

        $idSession = (int) $request->getPost(
            'id_session',
            0
        );

        $technicienPost =
            $request->getPost('id_technicien');

        $idTechnicien = null;

        if (
            $technicienPost !== null
            && $technicienPost !== ''
        ) {
            $idTechnicien = (int) $technicienPost;

            $idTechnicien =
                $idTechnicien > 0
                    ? $idTechnicien
                    : null;
        }

        if ($idMaintenance > 0) {
            $this->maintenanceTable
                ->affecterTechnicien(
                    $idMaintenance,
                    $idTechnicien
                );
        }

        return $this->redirect()->toRoute(
            'maintenance',
            $idSession > 0
                ? [
                    'action' => 'detail',
                    'id' => $idSession,
                ]
                : []
        );
    }

    public function changerStatutAction()
    {
        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }

        $request = $this->getRequest();

        if (
            !$request->isPost()
            || !$this->csrfValide(
                $request->getPost('csrf_token')
            )
        ) {
            return $this->redirect()->toRoute(
                'maintenance'
            );
        }

        $idMaintenance = (int) $request->getPost(
            'id_maintenance',
            0
        );

        $idSession = (int) $request->getPost(
            'id_session',
            0
        );

        $nouveauStatut = trim(
            (string) $request->getPost(
                'statut',
                ''
            )
        );

        $justification = trim(
            (string) $request->getPost(
                'justification',
                ''
            )
        );

        $diagnosticFinal = trim(
            (string) $request->getPost(
                'diagnostic_final',
                ''
            )
        );

        $compteRendu = trim(
            (string) $request->getPost(
                'compte_rendu',
                ''
            )
        );

        $commentaire = trim(
            (string) $request->getPost(
                'commentaire',
                ''
            )
        );

        $utilisateur =
            $this->utilisateurConnecte();

        if (!$utilisateur || $idMaintenance <= 0) {
            return $this->redirect()->toRoute(
                'maintenance'
            );
        }

        $maintenance =
            $this->maintenanceTable
                ->findMaintenance(
                    $idMaintenance
                );

        if (!$maintenance) {
            return $this->redirect()->toRoute(
                'maintenance'
            );
        }

        $role = strtolower(
            trim((string) (
                $utilisateur['role'] ?? ''
            ))
        );

        if (
            $role === 'technicien'
            && (int) (
                $maintenance['id_technicien'] ?? 0
            ) !== (int) $utilisateur['id']
        ) {
            return $this->redirect()->toRoute(
                'home'
            );
        }

        if (
            !in_array(
                $role,
                [
                    'administrateur',
                    'technicien',
                ],
                true
            )
        ) {
            return $this->redirect()->toRoute(
                'home'
            );
        }

        try {
            $fichier =
                $this->getUploadedFile($request);

            if ($nouveauStatut === 'terminee') {
                if ($fichier === null) {
                    throw new \InvalidArgumentException(
                        'Une photo ou un PDF final est obligatoire pour terminer.'
                    );
                }

                $erreurFichier =
                    $this->validerFichierMaintenance(
                        $fichier
                    );

                if ($erreurFichier !== null) {
                    throw new \InvalidArgumentException(
                        $erreurFichier
                    );
                }
            } elseif ($fichier !== null) {
                $erreurFichier =
                    $this->validerFichierMaintenance(
                        $fichier
                    );

                if ($erreurFichier !== null) {
                    throw new \InvalidArgumentException(
                        $erreurFichier
                    );
                }
            }

            $this->maintenanceTable->changerStatut(
                $idMaintenance,
                $nouveauStatut,
                $justification !== ''
                    ? $justification
                    : null,
                $diagnosticFinal !== ''
                    ? $diagnosticFinal
                    : null,
                $compteRendu !== ''
                    ? $compteRendu
                    : null,
                $commentaire !== ''
                    ? $commentaire
                    : null
            );

            if ($fichier !== null) {
                $saved =
                    $this->enregistrerFichierMaintenance(
                        $idMaintenance,
                        $fichier
                    );

                if ($saved !== null) {
                    $this->maintenanceTable
                        ->ajouterPieceJointe(
                            $idMaintenance,
                            $saved['url'],
                            $saved['type'],
                            $nouveauStatut === 'terminee'
                                ? 'Pièce finale'
                                : 'Pièce de maintenance',
                            $nouveauStatut === 'terminee'
                                ? 'finale'
                                : 'intervention'
                        );
                }
            }
        } catch (\Throwable $e) {
            error_log(
                '[MaintenanceController::changerStatutAction] '
                . $e->getMessage()
            );
        }

        return $this->redirect()->toRoute(
            'maintenance',
            $idSession > 0
                ? [
                    'action' => 'detail',
                    'id' => $idSession,
                ]
                : []
        );
    }

    public function ajouterPieceJointeAction()
    {
        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }

        $request = $this->getRequest();

        if (
            !$request->isPost()
            || !$this->csrfValide(
                $request->getPost('csrf_token')
            )
        ) {
            return $this->redirect()->toRoute(
                'maintenance'
            );
        }

        $idMaintenance = (int) $request->getPost(
            'id_maintenance',
            0
        );

        $idSession = (int) $request->getPost(
            'id_session',
            0
        );

        $legende = trim(
            (string) $request->getPost(
                'legende',
                ''
            )
        );

        $phase = trim(
            (string) $request->getPost(
                'phase',
                'intervention'
            )
        );

        $utilisateur =
            $this->utilisateurConnecte();

        if (!$utilisateur || $idMaintenance <= 0) {
            return $this->redirect()->toRoute(
                'maintenance'
            );
        }

        $maintenance =
            $this->maintenanceTable
                ->findMaintenance(
                    $idMaintenance
                );

        if (!$maintenance) {
            return $this->redirect()->toRoute(
                'maintenance'
            );
        }

        $role = strtolower(
            trim((string) (
                $utilisateur['role'] ?? ''
            ))
        );

        if (
            $role === 'technicien'
            && (int) (
                $maintenance['id_technicien'] ?? 0
            ) !== (int) $utilisateur['id']
        ) {
            return $this->redirect()->toRoute(
                'home'
            );
        }

        if (
            !in_array(
                $role,
                [
                    'administrateur',
                    'technicien',
                ],
                true
            )
        ) {
            return $this->redirect()->toRoute(
                'home'
            );
        }

        $fichier =
            $this->getUploadedFile($request);

        if (
            $fichier !== null
            && $this->validerFichierMaintenance(
                $fichier
            ) === null
        ) {
            $saved =
                $this->enregistrerFichierMaintenance(
                    $idMaintenance,
                    $fichier
                );

            if ($saved !== null) {
                $this->maintenanceTable
                    ->ajouterPieceJointe(
                        $idMaintenance,
                        $saved['url'],
                        $saved['type'],
                        $legende !== ''
                            ? $legende
                            : null,
                        in_array(
                            $phase,
                            ['initiale', 'finale'],
                            true
                        )
                            ? $phase
                            : 'intervention'
                    );
            }
        }

        return $this->redirect()->toRoute(
            'maintenance',
            $idSession > 0
                ? [
                    'action' => 'detail',
                    'id' => $idSession,
                ]
                : []
        );
    }

    public function deleteAction()
    {
        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }

        if (!$this->estAdministrateurConnecte()) {
            return $this->redirect()->toRoute('home');
        }

        $request = $this->getRequest();

        if (
            !$request->isPost()
            || !$this->csrfValide(
                $request->getPost('csrf_token')
            )
        ) {
            return $this->redirect()->toRoute(
                'maintenance'
            );
        }

        $idSession = (int) $this->params()
            ->fromRoute('id', 0);

        if ($idSession > 0) {
            try {
                $this->maintenanceTable
                    ->supprimerSession(
                        $idSession
                    );
            } catch (\Throwable $e) {
                error_log(
                    '[MaintenanceController::deleteAction] '
                    . $e->getMessage()
                );
            }
        }

        return $this->redirect()->toRoute(
            'maintenance'
        );
    }

    public function rapportAction(): ViewModel
    {
        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }

        $idSession = (int) $this->params()
            ->fromRoute('id', 0);

        $session =
            $this->maintenanceTable
                ->findSession($idSession);

        if (!$session) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'session' => $session,

            'equipements' =>
                $this->maintenanceTable
                    ->getEquipementsDeSession(
                        $idSession
                    ),
        ]);
    }

    public function notFoundAction(): ViewModel
    {
        return new ViewModel();
    }

    private function estAdministrateurConnecte(): bool
    {
        $utilisateur =
            $this->utilisateurConnecte();

        if (!$utilisateur) {
            return false;
        }

        $role = strtolower(
            trim((string) (
                $utilisateur['role'] ?? ''
            ))
        );

        return in_array(
            $role,
            ['administrateur'],
            true
        );
    }

    private function getUploadedFile(
        $request
    ): ?array {
        $files = $request
            ->getFiles()
            ->toArray();

        $file = $files['fichier'] ?? null;

        if (
            !is_array($file)
            || empty($file['tmp_name'])
        ) {
            return null;
        }

        return $file;
    }

    private function validerFichierMaintenance(
        array $file
    ): ?string {
        if (
            ($file['error'] ?? UPLOAD_ERR_NO_FILE)
            !== UPLOAD_ERR_OK
        ) {
            return 'Le fichier n’a pas pu être envoyé.';
        }

        if (
            (int) ($file['size'] ?? 0)
            > 10 * 1024 * 1024
        ) {
            return 'Le fichier est limité à 10 Mo.';
        }

        $finfo = new \finfo(
            FILEINFO_MIME_TYPE
        );

        $mime = $finfo->file(
            (string) $file['tmp_name']
        );

        $mimes = [
            'image/jpeg' => 'photo',
            'image/png' => 'photo',
            'image/webp' => 'photo',
            'application/pdf' => 'document',
        ];

        if (!isset($mimes[$mime])) {
            return 'Seuls les fichiers JPG, PNG, WEBP et PDF sont autorisés.';
        }

        return null;
    }

    private function typeFichier(
        array $file
    ): string {
        $finfo = new \finfo(
            FILEINFO_MIME_TYPE
        );

        $mime = $finfo->file(
            (string) $file['tmp_name']
        );

        return $mime === 'application/pdf'
            ? 'document'
            : 'photo';
    }

    private function enregistrerFichierSession(
        int $idSession,
        array $file
    ): ?array {
        return $this->enregistrerFichier(
            'session_' . $idSession,
            $file
        );
    }

    private function enregistrerFichierMaintenance(
        int $idMaintenance,
        array $file
    ): ?array {
        return $this->enregistrerFichier(
            'maintenance_' . $idMaintenance,
            $file
        );
    }

    private function enregistrerFichier(
        string $dossier,
        array $file
    ): ?array {
        $base =
            getcwd()
            . '/uploads/maintenance/'
            . $dossier;

        if (
            !is_dir($base)
            && !mkdir($base, 0775, true)
            && !is_dir($base)
        ) {
            return null;
        }

        $extension = strtolower(
            (string) pathinfo(
                (string) ($file['name'] ?? ''),
                PATHINFO_EXTENSION
            )
        );

        if ($extension === '') {
            $extension = 'bin';
        }

        $type = $this->typeFichier($file);

        $nom =
            bin2hex(random_bytes(16))
            . '.'
            . $extension;

        $destination =
            $base
            . '/'
            . $nom;

        if (
            !move_uploaded_file(
                (string) $file['tmp_name'],
                $destination
            )
        ) {
            return null;
        }

        return [
            'url' =>
                '/uploads/maintenance/'
                . $dossier
                . '/'
                . $nom,
            'type' => $type,
        ];
    }
}