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
            'paysListe' => $this->paysTable->findAll('nom_pays'),
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
                $this->paysTable->insert(['nom_pays' => $nom]);
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

        if (! $item) {
            return $this->redirect()->toRoute('pays');
        }

        $erreur = null;

        if ($this->getRequest()->isPost()) {
            $nom = trim((string) $this->params()->fromPost('nom_pays'));

            if ($nom === '') {
                $erreur = 'Le nom du pays est obligatoire.';
            } else {
                $this->paysTable->update($id, ['nom_pays' => $nom]);
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
        $this->paysTable->delete($id);

        return $this->redirect()->toRoute('pays');
    }
}
