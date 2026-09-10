<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\PaysTable;
use Application\Model\VilleTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class VilleController extends AbstractActionController
{
    public function __construct(
        private VilleTable $villeTable,
        private PaysTable $paysTable
    ) {
    }

    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'villes' => $this->villeTable->findAllDetaille(),
        ]);
    }

    public function addAction()
    {
        $erreur = null;
        $paysListe = $this->paysTable->findAll('nom_pays');

        if ($this->getRequest()->isPost()) {
            $nom = trim((string) $this->params()->fromPost('nom_ville'));
            $idPays = (int) $this->params()->fromPost('id_pays');

            if ($nom === '' || $idPays <= 0) {
                $erreur = 'Le nom de la ville et le pays sont obligatoires.';
            } else {
                $this->villeTable->insert(['nom_ville' => $nom, 'id_pays' => $idPays]);
                return $this->redirect()->toRoute('ville');
            }
        }

        return new ViewModel([
            'erreur'    => $erreur,
            'item'      => ['nom_ville' => '', 'id_pays' => null],
            'paysListe' => $paysListe,
            'mode'      => 'ajout',
        ]);
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $item = $this->villeTable->find($id);

        if (! $item) {
            return $this->redirect()->toRoute('ville');
        }

        $erreur = null;
        $paysListe = $this->paysTable->findAll('nom_pays');

        if ($this->getRequest()->isPost()) {
            $nom = trim((string) $this->params()->fromPost('nom_ville'));
            $idPays = (int) $this->params()->fromPost('id_pays');

            if ($nom === '' || $idPays <= 0) {
                $erreur = 'Le nom de la ville et le pays sont obligatoires.';
            } else {
                $this->villeTable->update($id, ['nom_ville' => $nom, 'id_pays' => $idPays]);
                return $this->redirect()->toRoute('ville');
            }
        }

        return new ViewModel([
            'erreur'    => $erreur,
            'item'      => $item,
            'paysListe' => $paysListe,
            'mode'      => 'modification',
        ]);
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $this->villeTable->delete($id);

        return $this->redirect()->toRoute('ville');
    }
}
