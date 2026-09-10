<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\TypeEquipementTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class TypeEquipementController extends AbstractActionController
{
    // Chemin physique réel : module/Application/view/application/parametre/type_equipement/
    // On le fixe ici une fois pour toutes, pour ne plus dépendre de la
    // conversion automatique du nom du contrôleur (source du bug précédent).
    private const DOSSIER_VUES = 'application/parametre/type_equipement/';

    public function __construct(private TypeEquipementTable $typeEquipementTable)
    {
    }

    public function indexAction(): ViewModel
    {
        $viewModel = new ViewModel([
            'types' => $this->typeEquipementTable->findAll('nom_type'),
        ]);
        $viewModel->setTemplate(self::DOSSIER_VUES . 'index');

        return $viewModel;
    }

    public function addAction()
    {
        $erreur = null;

        if ($this->getRequest()->isPost()) {
            $nom = trim((string) $this->params()->fromPost('nom_type'));

            if ($nom === '') {
                $erreur = 'Le nom du type est obligatoire.';
            } else {
                $this->typeEquipementTable->insert(['nom_type' => $nom]);
                return $this->redirect()->toRoute('type_equipement');
            }
        }

        $viewModel = new ViewModel([
            'erreur' => $erreur,
            'item'   => ['nom_type' => ''],
            'mode'   => 'ajout',
        ]);
        $viewModel->setTemplate(self::DOSSIER_VUES . 'add');

        return $viewModel;
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $item = $this->typeEquipementTable->find($id);

        if (! $item) {
            return $this->redirect()->toRoute('type_equipement');
        }

        $erreur = null;

        if ($this->getRequest()->isPost()) {
            $nom = trim((string) $this->params()->fromPost('nom_type'));

            if ($nom === '') {
                $erreur = 'Le nom du type est obligatoire.';
            } else {
                $this->typeEquipementTable->update($id, ['nom_type' => $nom]);
                return $this->redirect()->toRoute('type_equipement');
            }
        }

        $viewModel = new ViewModel([
            'erreur' => $erreur,
            'item'   => $item,
            'mode'   => 'modification',
        ]);
        $viewModel->setTemplate(self::DOSSIER_VUES . 'edit');

        return $viewModel;
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $this->typeEquipementTable->delete($id);

        return $this->redirect()->toRoute('type_equipement');
    }
}
