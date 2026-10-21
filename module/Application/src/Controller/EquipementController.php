<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\CentraleTable;
use Application\Model\EquipementTable;
use Application\Model\FamilleTable;
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
    private FamilleTable $familleTable;
    private SiteTable $siteTable;
    private MaintenanceTable $maintenanceTable;

    public function __construct(
        EquipementTable $equipementTable,
        TypeEquipementTable $typeEquipementTable,
        CentraleTable $centraleTable,
        FamilleTable $familleTable,
        SiteTable $siteTable,
        MaintenanceTable $maintenanceTable
    ) {
        $this->equipementTable = $equipementTable;
        $this->typeEquipementTable = $typeEquipementTable;
        $this->centraleTable = $centraleTable;
        $this->familleTable = $familleTable;
        $this->siteTable = $siteTable;
        $this->maintenanceTable = $maintenanceTable;
    }

    /**
     * Liste des équipements.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'equipements' => $this->equipementTable->getAll(),
        ]);
    }

    /**
     * Ajouter un équipement.
     */
    public function addAction(): ViewModel
    {
        $types = $this->typeEquipementTable->getAll();
        $familles = $this->familleTable->findAll('nom_famille');
        $sites = $this->siteTable->getAll();
        $centrales = $this->centraleTable->getAll();

        $postData = [
            'id_famille' => '',
            'id_type_equipement' => '',
            'designation' => '',
            'numero_serie' => '',
            'marque' => '',
            'modele' => '',
            'date_mise_en_service' => '',
            'heure_fonctionnement' => '0',
            'periodicite' => '',
            'date_prochaine_maintenance' => '',
            'id_site' => '',
            'id_centrale' => '',
        ];

        $error = null;

        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            $postData = array_merge(
                $postData,
                $data
            );

            $idFamille = (int) (
                $data['id_famille'] ?? 0
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

            $dateMiseEnService = !empty(
                $data['date_mise_en_service'] ?? null
            )
                ? (string) $data['date_mise_en_service']
                : null;

            $heureFonctionnement = max(
                0,
                (int) (
                    $data['heure_fonctionnement'] ?? 0
                )
            );

            $periodicite = trim(
                (string) (
                    $data['periodicite'] ?? ''
                )
            );

            $dateProchaineMaintenance = !empty(
                $data['date_prochaine_maintenance'] ?? null
            )
                ? (string) $data['date_prochaine_maintenance']
                : null;

            $idSite = !empty(
                $data['id_site'] ?? null
            )
                ? (int) $data['id_site']
                : null;

            $idCentrale = !empty(
                $data['id_centrale'] ?? null
            )
                ? (int) $data['id_centrale']
                : null;

            if ($idFamille <= 0) {

                $error =
                    'Veuillez sélectionner une famille.';

            } elseif ($idTypeEquipement <= 0) {

                $error =
                    'Veuillez sélectionner un type d’équipement.';

            } elseif ($idCentrale <= 0) {

                $error =
                    'Veuillez sélectionner une centrale.';

            } elseif ($idSite <= 0) {

                $error =
                    'Veuillez sélectionner un site.';

            } else {

                try {

                    $centraleValide = false;

                    foreach ($centrales as $centrale) {

                        $centraleId = (int) (
                            $centrale['id_centrale'] ?? 0
                        );

                        $centraleFamille = (int) (
                            $centrale['id_famille'] ?? 0
                        );

                        if (
                            $centraleId === $idCentrale
                            && $centraleFamille === $idFamille
                        ) {
                            $centraleValide = true;
                            break;
                        }
                    }

                    if (!$centraleValide) {

                        $error =
                            'La centrale sélectionnée ne correspond '
                            . 'pas à la famille choisie.';

                    } else {

                        $this->equipementTable->insert(
                            $idTypeEquipement,
                            $designation !== ''
                                ? $designation
                                : null,
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
                            $heureFonctionnement,
                            $periodicite !== ''
                                ? $periodicite
                                : null,
                            $dateProchaineMaintenance,
                            $idSite,
                            $idCentrale
                        );

                        return $this->redirect()
                            ->toRoute('equipement');
                    }

                } catch (\Throwable $e) {

                    $error =
                        'Impossible d’enregistrer l’équipement : '
                        . $e->getMessage();
                }
            }
        }

        return new ViewModel([
            'types' => $types,
            'familles' => $familles,
            'sites' => $sites,
            'centrales' => $centrales,
            'postData' => $postData,
            'item' => [],
            'mode' => 'ajout',
            'error' => $error,
            'erreur' => $error,
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
        $familles = $this->familleTable->findAll('nom_famille');
        $sites = $this->siteTable->getAll();
        $centrales = $this->centraleTable->getAll();

        $error = null;

        $postData = [
            'id_famille' =>
                $equipement['id_famille'] ?? '',

            'id_type_equipement' =>
                $equipement['id_type_equipement'] ?? '',

            'designation' =>
                $equipement['designation'] ?? '',

            'numero_serie' =>
                $equipement['numero_serie'] ?? '',

            'marque' =>
                $equipement['marque'] ?? '',

            'modele' =>
                $equipement['modele'] ?? '',

            'date_mise_en_service' =>
                $equipement['date_mise_en_service'] ?? '',

            'heure_fonctionnement' =>
                $equipement['heure_fonctionnement'] ?? 0,

            'periodicite' =>
                $equipement['periodicite'] ?? '',

            'date_prochaine_maintenance' =>
                $equipement['date_prochaine_maintenance'] ?? '',

            'id_site' =>
                $equipement['id_site'] ?? '',

            'id_centrale' =>
                $equipement['id_centrale'] ?? '',
        ];

        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            $postData = array_merge(
                $postData,
                $data
            );

            $idFamille = (int) (
                $data['id_famille'] ?? 0
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

            $dateMiseEnService = !empty(
                $data['date_mise_en_service'] ?? null
            )
                ? (string) $data['date_mise_en_service']
                : null;

            $heureFonctionnement = max(
                0,
                (int) (
                    $data['heure_fonctionnement'] ?? 0
                )
            );

            $periodicite = trim(
                (string) (
                    $data['periodicite'] ?? ''
                )
            );

            $dateProchaineMaintenance = !empty(
                $data['date_prochaine_maintenance'] ?? null
            )
                ? (string) $data['date_prochaine_maintenance']
                : null;

            $idSite = !empty(
                $data['id_site'] ?? null
            )
                ? (int) $data['id_site']
                : null;

            $idCentrale = !empty(
                $data['id_centrale'] ?? null
            )
                ? (int) $data['id_centrale']
                : null;

            if ($idFamille <= 0) {

                $error =
                    'Veuillez sélectionner une famille.';

            } elseif ($idTypeEquipement <= 0) {

                $error =
                    'Veuillez sélectionner un type d’équipement.';

            } elseif ($idCentrale <= 0) {

                $error =
                    'Veuillez sélectionner une centrale.';

            } elseif ($idSite <= 0) {

                $error =
                    'Veuillez sélectionner un site.';

            } else {

                try {

                    $centraleValide = false;

                    foreach ($centrales as $centrale) {

                        $centraleId = (int) (
                            $centrale['id_centrale'] ?? 0
                        );

                        $centraleFamille = (int) (
                            $centrale['id_famille'] ?? 0
                        );

                        if (
                            $centraleId === $idCentrale
                            && $centraleFamille === $idFamille
                        ) {
                            $centraleValide = true;
                            break;
                        }
                    }

                    if (!$centraleValide) {

                        $error =
                            'La centrale sélectionnée ne correspond '
                            . 'pas à la famille choisie.';

                    } else {

                        $this->equipementTable->update(
                            $id,
                            $idTypeEquipement,
                            $designation !== ''
                                ? $designation
                                : null,
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
                            $heureFonctionnement,
                            $periodicite !== ''
                                ? $periodicite
                                : null,
                            $dateProchaineMaintenance,
                            $idSite,
                            $idCentrale
                        );

                        return $this->redirect()
                            ->toRoute('equipement');
                    }

                } catch (\Throwable $e) {

                    $error =
                        'Impossible de modifier l’équipement : '
                        . $e->getMessage();
                }
            }
        }

        return new ViewModel([
            'item' => $equipement,
            'equipement' => $equipement,
            'types' => $types,
            'familles' => $familles,
            'sites' => $sites,
            'centrales' => $centrales,
            'postData' => $postData,
            'mode' => 'modification',
            'error' => $error,
            'erreur' => $error,
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
     * Historique des maintenances.
     */
    public function historiqueAction(): ViewModel
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
                // Retour à la liste.
            }
        }

        return $this->redirect()
            ->toRoute('equipement');
    }
}