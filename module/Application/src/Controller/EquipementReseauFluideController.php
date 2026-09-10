<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\CentraleTable;
use Application\Model\EquipementReseauFluideTable;
use Application\Model\TypeEquipementTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class EquipementReseauFluideController extends AbstractActionController
{
    // Chemin physique réel : module/Application/view/application/parametre/equipement_reseau_fluide/
    // Fixé explicitement pour ne pas dépendre de la conversion automatique
    // du nom du contrôleur (source du bug rencontré sur Type d'équipement).
    private const DOSSIER_VUES = 'application/parametre/equipement_reseau_fluide/';

    public function __construct(
        private EquipementReseauFluideTable $equipementReseauFluideTable,
        private CentraleTable $centraleTable,
        private TypeEquipementTable $typeEquipementTable
    ) {
    }

    public function indexAction(): ViewModel
    {
        $viewModel = new ViewModel([
            'equipements' => $this->equipementReseauFluideTable->findAllDetaille(),
        ]);
        $viewModel->setTemplate(self::DOSSIER_VUES . 'index');

        return $viewModel;
    }

    public function addAction()
    {
        $erreur = null;
        $centrales = $this->centraleTable->getAll();
        $types = $this->typeEquipementTable->getAll();

        if ($this->getRequest()->isPost()) {
            $donnees = $this->donneesFormulaire();

            if ($donnees['erreur']) {
                $erreur = $donnees['erreur'];
            } else {
                $this->equipementReseauFluideTable->insert($donnees['valeurs']);
                return $this->redirect()->toRoute('equipement_reseau_fluide');
            }
        }

        $viewModel = new ViewModel([
            'erreur'    => $erreur,
            'item'      => [],
            'centrales' => $centrales,
            'types'     => $types,
            'mode'      => 'ajout',
        ]);
        $viewModel->setTemplate(self::DOSSIER_VUES . 'add');

        return $viewModel;
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $item = $this->equipementReseauFluideTable->find($id);

        if (! $item) {
            return $this->redirect()->toRoute('equipement_reseau_fluide');
        }

        $erreur = null;
        $centrales = $this->centraleTable->getAll();
        $types = $this->typeEquipementTable->getAll();

        if ($this->getRequest()->isPost()) {
            $donnees = $this->donneesFormulaire();

            if ($donnees['erreur']) {
                $erreur = $donnees['erreur'];
            } else {
                $this->equipementReseauFluideTable->update($id, $donnees['valeurs']);
                return $this->redirect()->toRoute('equipement_reseau_fluide');
            }
        }

        $viewModel = new ViewModel([
            'erreur'    => $erreur,
            'item'      => $item,
            'centrales' => $centrales,
            'types'     => $types,
            'mode'      => 'modification',
        ]);
        $viewModel->setTemplate(self::DOSSIER_VUES . 'edit');

        return $viewModel;
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $this->equipementReseauFluideTable->delete($id);

        return $this->redirect()->toRoute('equipement_reseau_fluide');
    }

    private function donneesFormulaire(): array
    {
        $designation = trim((string) $this->params()->fromPost('designation'));
        $idTypeEquipement = (int) $this->params()->fromPost('id_type_equipement');
        $idCentrale = (int) $this->params()->fromPost('id_centrale');
        $nombre = $this->params()->fromPost('nombre') ?: 1;
        $pressionEntree = $this->params()->fromPost('pression_entree') ?: null;
        $pressionSortie = $this->params()->fromPost('pression_sortie') ?: null;

        if ($designation === '') {
            return ['erreur' => 'La désignation est obligatoire.', 'valeurs' => []];
        }

        if ($idTypeEquipement <= 0) {
            return ['erreur' => 'Le type est obligatoire.', 'valeurs' => []];
        }

        if ($idCentrale <= 0) {
            return ['erreur' => 'La centrale (réseau) est obligatoire.', 'valeurs' => []];
        }

        return [
            'erreur' => null,
            'valeurs' => [
                'designation'         => $designation,
                'id_type_equipement'  => $idTypeEquipement,
                'id_centrale'         => $idCentrale,
                'nombre'              => (int) $nombre,
                'pression_entree'     => $pressionEntree,
                'pression_sortie'     => $pressionSortie,
            ],
        ];
    }
}
