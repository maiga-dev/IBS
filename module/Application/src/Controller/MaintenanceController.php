<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\MaintenanceTable;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class MaintenanceController extends AbstractActionController
{
    use CsrfTrait;

    private MaintenanceTable $maintenanceTable;

    public function __construct(MaintenanceTable $maintenanceTable)
    {
        $this->maintenanceTable = $maintenanceTable;
    }

    /**
     * Liste des sessions.
     *
     * Le filtrage est effectué côté navigateur
     * dans index.phtml.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'sessions' =>
                $this->maintenanceTable->fetchSessions(),

            'csrf_token' =>
                $this->genererCsrfToken(),
        ]);
    }

    /**
     * Création d'une session.
     *
     * Aucun technicien n'est affecté ici.
     */
    public function addAction(): ViewModel
    {
        $request = $this->getRequest();

        $erreurs = [];

        $centrales =
            $this->maintenanceTable->getCentrales();

        $idCentralePreselectionnee =
            (int) $this->params()
                ->fromQuery('centrale', 0);

        if ($request->isPost()) {
            if (!$this->csrfValide(
                $request->getPost('csrf_token')
            )) {
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

            if ($idCentrale <= 0) {
                $erreurs[] =
                    'La centrale est obligatoire.';
            }

            if ($dateSession === '') {
                $erreurs[] =
                    'La date est obligatoire.';
            }

            if (empty($erreurs)) {
                try {
                    $idSession =
                        $this->maintenanceTable->creerSession(
                            $idCentrale,
                            $dateSession
                        );

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
                        "Erreur lors de l'enregistrement.";
                }
            }
        }

        return new ViewModel([
            'centrales' =>
                $centrales,

            'id_centrale_preselectionnee' =>
                $idCentralePreselectionnee,

            'erreurs' =>
                $erreurs,

            'csrf_token' =>
                $this->genererCsrfToken(),
        ]);
    }

    /**
     * Détail d'une session.
     */
    public function detailAction()
    {
        $id = (int) $this->params()
            ->fromRoute('id', 0);

        if ($id <= 0) {
            return $this->notFoundAction();
        }

        $session =
            $this->maintenanceTable->findSession($id);

        if (!$session) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'session' =>
                $session,

            'equipements' =>
                $this->maintenanceTable
                    ->getEquipementsDeSession($id),

            'techniciens' =>
                $this->maintenanceTable
                    ->getTechniciens(),

            'csrf_token' =>
                $this->genererCsrfToken(),
        ]);
    }

    /**
     * Affecte ou change le technicien
     * d'une maintenance.
     *
     * Un seul technicien est possible.
     */
    public function affecterTechnicienAction()
    {
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return $this->redirect()
                ->toRoute('maintenance');
        }

        if (!$this->csrfValide(
            $request->getPost('csrf_token')
        )) {
            return $this->redirect()
                ->toRoute('maintenance');
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

        $idTechnicien =
            ($technicienPost !== null
                && $technicienPost !== '')
            ? (int) $technicienPost
            : null;

        if ($idMaintenance > 0) {
            $this->maintenanceTable
                ->affecterTechnicien(
                    $idMaintenance,
                    $idTechnicien
                );
        }

        if ($idSession > 0) {
            return $this->redirect()->toRoute(
                'maintenance',
                [
                    'action' => 'detail',
                    'id' => $idSession,
                ]
            );
        }

        return $this->redirect()
            ->toRoute('maintenance');
    }

    /**
     * Mise à jour du statut / diagnostic /
     * rapport d'une maintenance.
     */
    public function updateItemAction()
    {
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return $this->redirect()
                ->toRoute('maintenance');
        }

        if (!$this->csrfValide(
            $request->getPost('csrf_token')
        )) {
            return $this->redirect()
                ->toRoute('maintenance');
        }

        $idMaintenance = (int) $request->getPost(
            'id_maintenance',
            0
        );

        $idSession = (int) $request->getPost(
            'id_session',
            0
        );

        if ($idMaintenance > 0) {
            $statut = trim(
                (string) $request->getPost(
                    'statut',
                    'planifiee'
                )
            );

            $dateFin =
                $request->getPost('date_fin') ?: null;

            $diagnostic =
                trim((string) (
                    $request->getPost(
                        'diagnostic',
                        ''
                    )
                ));

            $compteRendu =
                trim((string) (
                    $request->getPost(
                        'compte_rendu',
                        ''
                    )
                ));

            $this->maintenanceTable->updateItem(
                $idMaintenance,
                $statut,
                $dateFin,
                $diagnostic ?: null,
                $compteRendu ?: null
            );
        }

        if ($idSession > 0) {
            return $this->redirect()->toRoute(
                'maintenance',
                [
                    'action' => 'detail',
                    'id' => $idSession,
                ]
            );
        }

        return $this->redirect()
            ->toRoute('maintenance');
    }

    /**
     * Ajoute une photo/document à une ligne de maintenance (diagnostic
     * ou clôture). Stocké dans public/uploads/maintenance/<id>/.
     */
    public function ajouterPieceJointeAction()
    {
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return $this->redirect()->toRoute('maintenance');
        }

        if (!$this->csrfValide(
            $request->getPost('csrf_token')
        )) {
            return $this->redirect()->toRoute('maintenance');
        }

        $idMaintenance = (int) $request->getPost('id_maintenance', 0);
        $idSession = (int) $request->getPost('id_session', 0);
        $legende = trim((string) $request->getPost('legende', ''));

        $files = $request->getFiles()->toArray();

        if (
            $idMaintenance > 0
            && ! empty($files['fichier']['tmp_name'])
            && $files['fichier']['error'] === UPLOAD_ERR_OK
        ) {
            $dossierDestination = getcwd() . '/uploads/maintenance/' . $idMaintenance;

            if (! is_dir($dossierDestination)) {
                mkdir($dossierDestination, 0775, true);
            }

            $nomOriginal = basename($files['fichier']['name']);
            $nomFichier = uniqid('piece_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nomOriginal);
            $cheminDestination = $dossierDestination . '/' . $nomFichier;

            if (move_uploaded_file($files['fichier']['tmp_name'], $cheminDestination)) {
                $urlPublique = '/uploads/maintenance/' . $idMaintenance . '/' . $nomFichier;
                $extension = strtolower((string) pathinfo($nomFichier, PATHINFO_EXTENSION));
                $typeFichier = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? 'photo' : 'document';

                $this->maintenanceTable->ajouterPieceJointe($idMaintenance, $urlPublique, $typeFichier, $legende ?: null);
            }
        }

        if ($idSession > 0) {
            return $this->redirect()->toRoute(
                'maintenance',
                ['action' => 'detail', 'id' => $idSession]
            );
        }

        return $this->redirect()->toRoute('maintenance');
    }

    /**
     * Suppression d'une session.
     */
    public function deleteAction()
    {
        $request = $this->getRequest();

        if (
            !$request->isPost()
            || !$this->csrfValide(
                $request->getPost('csrf_token')
            )
        ) {
            return $this->redirect()
                ->toRoute('maintenance');
        }

        $id = (int) $this->params()
            ->fromRoute('id', 0);

        if ($id > 0) {
            $this->maintenanceTable
                ->supprimerSession($id);
        }

        return $this->redirect()
            ->toRoute('maintenance');
    }

    /**
     * Rapport de session.
     */
    public function rapportAction()
    {
        $id = (int) $this->params()
            ->fromRoute('id', 0);

        $session =
            $this->maintenanceTable
                ->findSession($id);

        if (!$session) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'session' =>
                $session,

            'equipements' =>
                $this->maintenanceTable
                    ->getEquipementsDeSession($id),
        ]);
    }

    /**
     * NOUVEAU — manquait sur ce contrôleur : AbstractActionController ne
     * fournit pas notFoundAction() par défaut (contrairement à
     * IndexController dans le squelette Laminas). On la définit ici pour
     * que detailAction()/rapportAction() puissent l'appeler sans erreur
     * fatale "Call to undefined method".
     */
    public function notFoundAction(): ViewModel
    {
        $response = $this->getResponse();

        if ($response instanceof Response) {
            $response->setStatusCode(404);
        }

        $viewModel = new ViewModel([
            'message' => 'Session de maintenance introuvable.',
        ]);
        $viewModel->setTemplate('application/maintenance/not-found');

        return $viewModel;
    }
}
