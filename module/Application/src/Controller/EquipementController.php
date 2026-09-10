<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\CentraleTable;
use Application\Model\EquipementTable;
use Application\Model\MaintenanceTable;
use Application\Model\SiteTable;
use Application\Model\TypeEquipementTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class EquipementController extends AbstractActionController
{
    private EquipementTable $equipementTable;

    private TypeEquipementTable $typeEquipementTable;

    private CentraleTable $centraleTable;

    private SiteTable $siteTable;

    private MaintenanceTable $maintenanceTable;

    public function __construct(
        EquipementTable $equipementTable,
        TypeEquipementTable $typeEquipementTable,
        CentraleTable $centraleTable,
        SiteTable $siteTable,
        MaintenanceTable $maintenanceTable
    ) {
        $this->equipementTable = $equipementTable;
        $this->typeEquipementTable = $typeEquipementTable;
        $this->centraleTable = $centraleTable;
        $this->siteTable = $siteTable;
        $this->maintenanceTable = $maintenanceTable;
    }

    /**
     * Liste des équipements.
     */
    public function indexAction(): ViewModel
    {
        $equipements = $this->equipementTable->getAll();

        return new ViewModel([
            'equipements' => $equipements,
        ]);
    }

    /**
     * Ajouter un équipement.
     */
    public function addAction(): ViewModel
    {
        $types = $this->typeEquipementTable->getAll();
        $sites = $this->siteTable->getAll();
        $centrales = $this->centraleTable->getAll();

        $error = null;

        $postData = [
            'id_type_equipement' => '',
            'designation' => '',
            'numero_serie' => '',
            'marque' => '',
            'modele' => '',
            'date_mise_en_service' => '',
            'periodicite' => '',
            'date_prochaine_maintenance' => '',
            'id_site' => '',
            'id_centrale' => '',
        ];

        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            $postData = array_merge(
                $postData,
                $data
            );

            $idTypeEquipement = (int) (
                $data['id_type_equipement'] ?? 0
            );

            $designation = trim(
                (string) (
                    $data['designation'] ?? ''
                )
            );

            $numeroSerie = trim(
                (string) (
                    $data['numero_serie'] ?? ''
                )
            );

            $marque = trim(
                (string) (
                    $data['marque'] ?? ''
                )
            );

            $modele = trim(
                (string) (
                    $data['modele'] ?? ''
                )
            );

            $dateMiseEnService =
                !empty($data['date_mise_en_service'] ?? null)
                    ? (string) $data['date_mise_en_service']
                    : null;

            $periodicite = trim(
                (string) (
                    $data['periodicite'] ?? ''
                )
            );

            $dateProchaineMaintenance =
                !empty($data['date_prochaine_maintenance'] ?? null)
                    ? (string) $data['date_prochaine_maintenance']
                    : null;

            $idSite =
                !empty($data['id_site'] ?? null)
                    ? (int) $data['id_site']
                    : null;

            $idCentrale =
                !empty($data['id_centrale'] ?? null)
                    ? (int) $data['id_centrale']
                    : null;

            if ($idTypeEquipement <= 0) {

                $error =
                    'Veuillez sélectionner un type d’équipement.';

            } elseif ($designation === '') {

                $error =
                    'Veuillez renseigner la désignation de l’équipement.';

            } else {

                try {

                    $this->equipementTable->insert(
                        $idTypeEquipement,
                        $designation,
                        $numeroSerie !== ''
                            ? $numeroSerie
                            : null,
                        $marque !== ''
                            ? $marque
                            : null,
                        $modele !== ''
                            ? $modele
                            : null,
                        $dateMiseEnService,
                        $periodicite !== ''
                            ? $periodicite
                            : null,
                        $dateProchaineMaintenance,
                        $idSite,
                        $idCentrale
                    );

                    return $this->redirect()
                        ->toRoute('equipement');

                } catch (\Throwable $e) {

                    $error =
                        'Impossible d’enregistrer l’équipement. '
                        . $e->getMessage();
                }
            }
        }

        return new ViewModel([
            'types' => $types,
            'sites' => $sites,
            'centrales' => $centrales,
            'postData' => $postData,
            'error' => $error,
        ]);
    }

    /**
     * Modifier un équipement.
     */
    public function editAction(): ViewModel
    {
        $id = (int) $this->params()
            ->fromRoute('id', 0);

        if ($id <= 0) {
            return $this->redirect()
                ->toRoute('equipement');
        }

        $equipement = $this->equipementTable
            ->getById($id);

        if ($equipement === null) {
            return $this->redirect()
                ->toRoute('equipement');
        }

        $types = $this->typeEquipementTable->getAll();
        $sites = $this->siteTable->getAll();
        $centrales = $this->centraleTable->getAll();

        $error = null;

        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            $idTypeEquipement = (int) (
                $data['id_type_equipement'] ?? 0
            );

            $designation = trim(
                (string) (
                    $data['designation'] ?? ''
                )
            );

            $numeroSerie = trim(
                (string) (
                    $data['numero_serie'] ?? ''
                )
            );

            $marque = trim(
                (string) (
                    $data['marque'] ?? ''
                )
            );

            $modele = trim(
                (string) (
                    $data['modele'] ?? ''
                )
            );

            $dateMiseEnService =
                !empty($data['date_mise_en_service'] ?? null)
                    ? (string) $data['date_mise_en_service']
                    : null;

            $periodicite = trim(
                (string) (
                    $data['periodicite'] ?? ''
                )
            );

            $dateProchaineMaintenance =
                !empty($data['date_prochaine_maintenance'] ?? null)
                    ? (string) $data['date_prochaine_maintenance']
                    : null;

            $idSite =
                !empty($data['id_site'] ?? null)
                    ? (int) $data['id_site']
                    : null;

            $idCentrale =
                !empty($data['id_centrale'] ?? null)
                    ? (int) $data['id_centrale']
                    : null;

            if ($idTypeEquipement <= 0) {

                $error =
                    'Veuillez sélectionner un type d’équipement.';

            } elseif ($designation === '') {

                $error =
                    'Veuillez renseigner la désignation de l’équipement.';

            } else {

                try {

                    $this->equipementTable->update(
                        $id,
                        $idTypeEquipement,
                        $designation,
                        $numeroSerie !== ''
                            ? $numeroSerie
                            : null,
                        $marque !== ''
                            ? $marque
                            : null,
                        $modele !== ''
                            ? $modele
                            : null,
                        $dateMiseEnService,
                        $periodicite !== ''
                            ? $periodicite
                            : null,
                        $dateProchaineMaintenance,
                        $idSite,
                        $idCentrale
                    );

                    return $this->redirect()
                        ->toRoute('equipement');

                } catch (\Throwable $e) {

                    $error =
                        'Impossible de modifier l’équipement. '
                        . $e->getMessage();
                }
            }
        }

        return new ViewModel([
            'equipement' => $equipement,
            'types' => $types,
            'sites' => $sites,
            'centrales' => $centrales,
            'error' => $error,
        ]);
    }

    /**
     * Détail d'un équipement.
     */
    public function viewAction(): ViewModel
    {
        $id = (int) $this->params()
            ->fromRoute('id', 0);

        if ($id <= 0) {
            return $this->redirect()
                ->toRoute('equipement');
        }

        $equipement = $this->equipementTable
            ->getById($id);

        if ($equipement === null) {
            return $this->redirect()
                ->toRoute('equipement');
        }

        return new ViewModel([
            'equipement' => $equipement,
        ]);
    }

    /**
     * Historique des maintenances d'un équipement.
     */
    public function historiqueAction(): ViewModel
    {
        $id = (int) $this->params()
            ->fromRoute('id', 0);

        if ($id <= 0) {
            return $this->redirect()
                ->toRoute('equipement');
        }

        /*
         * IMPORTANT :
         * getEquipement() n'existe pas dans EquipementTable.
         * La bonne méthode est getById().
         */
        $equipement = $this->equipementTable
            ->getById($id);

        if ($equipement === null) {
            return $this->redirect()
                ->toRoute('equipement');
        }

        /*
         * Récupération de toutes les interventions
         * liées à cet équipement.
         */
        $maintenances = $this->maintenanceTable
            ->getMaintenancesByEquipement($id);

        return new ViewModel([
            'equipement' => $equipement,
            'maintenances' => $maintenances,
        ]);
    }

    /**
     * Supprimer un équipement.
     */
    public function deleteAction()
    {
        $id = (int) $this->params()
            ->fromRoute('id', 0);

        if ($id > 0) {

            try {

                $this->equipementTable->delete($id);

            } catch (\Throwable $e) {

                // Retour à la liste même en cas d'erreur.
            }
        }

        return $this->redirect()
            ->toRoute('equipement');
    }
}
