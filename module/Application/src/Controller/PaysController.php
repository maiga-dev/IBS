<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\PaysTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class PaysController extends AbstractActionController
{
    public function __construct(private PaysTable $paysTable)
    {
    }

    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'pays' => $this->paysTable->findAll('nom_pays'),
        ]);
    }

    public function addAction()
    {
        $erreur = null;

        if ($this->getRequest()->isPost()) {
            $nom = trim((string) $this->params()->fromPost('nom_pays'));

            if ($nom === '') {
                $erreur = 'Le nom du pays est obligatoire.';
            } else {
                $this->paysTable->insert([
                    'nom_pays' => $nom
                ]);

                return $this->redirect()->toRoute('pays');
            }
        }

        return new ViewModel([
            'erreur' => $erreur,
            'item'   => ['nom_pays' => ''],
            'mode'   => 'ajout',
        ]);
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $item = $this->paysTable->find($id);

        if (!$item) {
            return $this->redirect()->toRoute('pays');
        }

        $erreur = null;

        if ($this->getRequest()->isPost()) {
            $nom = trim((string) $this->params()->fromPost('nom_pays'));

            if ($nom === '') {
                $erreur = 'Le nom du pays est obligatoire.';
            } else {
                $this->paysTable->update(
                    $id,
                    ['nom_pays' => $nom]
                );

                return $this->redirect()->toRoute('pays');
            }
        }

        return new ViewModel([
            'erreur' => $erreur,
            'item'   => $item,
            'mode'   => 'modification',
        ]);
    }

   public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        try {
            $this->paysTable->delete($id);

            $this->flashMessenger()->addSuccessMessage(
                'Le pays a été supprimé avec succès.'
            );
        } catch (\Throwable $e) {
            $this->flashMessenger()->addErrorMessage(
                'Impossible de supprimer ce pays : il est encore utilisé par une ou plusieurs villes.'
            );
        }

        return $this->redirect()->toRoute('pays');
    }
}