<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\SiteTable;
use Application\Model\VilleTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SiteController extends AbstractActionController
{
    public function __construct(
        private SiteTable $siteTable,
        private VilleTable $villeTable
    ) {
    }

    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'sites' => $this->siteTable->findAllDetaille(),
        ]);
    }

    public function addAction()
    {
        $erreur = null;
        $villes = $this->villeTable->findAll('nom_ville');

        if ($this->getRequest()->isPost()) {
            $nom = trim((string) $this->params()->fromPost('nom_site'));
            $adresse = trim((string) $this->params()->fromPost('adresse'));
            $idVille = (int) $this->params()->fromPost('id_ville');
            $latitude = $this->params()->fromPost('latitude') ?: null;
            $longitude = $this->params()->fromPost('longitude') ?: null;

            if ($nom === '' || $idVille <= 0) {
                $erreur = 'Le nom du site et la ville sont obligatoires.';
            } else {
                $this->siteTable->insert([
                    'nom_site'  => $nom,
                    'adresse'   => $adresse ?: null,
                    'id_ville'  => $idVille,
                    'latitude'  => $latitude,
                    'longitude' => $longitude,
                ]);
                return $this->redirect()->toRoute('site');
            }
        }

        return new ViewModel([
            'erreur' => $erreur,
            'item'   => ['nom_site' => '', 'adresse' => '', 'id_ville' => null, 'latitude' => '', 'longitude' => ''],
            'villes' => $villes,
            'mode'   => 'ajout',
        ]);
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $item = $this->siteTable->find($id);

        if (! $item) {
            return $this->redirect()->toRoute('site');
        }

        $erreur = null;
        $villes = $this->villeTable->findAll('nom_ville');

        if ($this->getRequest()->isPost()) {
            $nom = trim((string) $this->params()->fromPost('nom_site'));
            $adresse = trim((string) $this->params()->fromPost('adresse'));
            $idVille = (int) $this->params()->fromPost('id_ville');
            $latitude = $this->params()->fromPost('latitude') ?: null;
            $longitude = $this->params()->fromPost('longitude') ?: null;

            if ($nom === '' || $idVille <= 0) {
                $erreur = 'Le nom du site et la ville sont obligatoires.';
            } else {
                $this->siteTable->update($id, [
                    'nom_site'  => $nom,
                    'adresse'   => $adresse ?: null,
                    'id_ville'  => $idVille,
                    'latitude'  => $latitude,
                    'longitude' => $longitude,
                ]);
                return $this->redirect()->toRoute('site');
            }
        }

        return new ViewModel([
            'erreur' => $erreur,
            'item'   => $item,
            'villes' => $villes,
            'mode'   => 'modification',
        ]);
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $this->siteTable->delete($id);

        return $this->redirect()->toRoute('site');
    }
}
