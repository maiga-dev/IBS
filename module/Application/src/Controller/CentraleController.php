<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\CentraleTable;
use Application\Model\FamilleTable;
use Application\Model\SiteTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CentraleController extends AbstractActionController
{
    public function __construct(
        private CentraleTable $centraleTable,
        private SiteTable $siteTable,
        private FamilleTable $familleTable
    ) {
    }

    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'centrales' => $this->centraleTable->findAllDetaille(),
        ]);
    }

    public function addAction()
    {
        $erreur = null;
        $sites = $this->siteTable->findAll('nom_site');
        $familles = $this->familleTable->findAll('nom_famille');

        if ($this->getRequest()->isPost()) {
            $type = trim((string) $this->params()->fromPost('type_centrale'));
            $idSite = (int) $this->params()->fromPost('id_site');
            $idFamille = $this->params()->fromPost('id_famille') ?: null;

            if ($type === '' || $idSite <= 0) {
                $erreur = 'Le type de centrale et le site sont obligatoires.';
            } else {
                $this->centraleTable->insert([
                    'type_centrale' => $type,
                    'id_site'       => $idSite,
                    'id_famille'    => $idFamille,
                ]);
                return $this->redirect()->toRoute('centrale');
            }
        }

        return new ViewModel([
            'erreur'   => $erreur,
            'item'     => ['type_centrale' => '', 'id_site' => null, 'id_famille' => null],
            'sites'    => $sites,
            'familles' => $familles,
            'mode'     => 'ajout',
        ]);
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $item = $this->centraleTable->find($id);

        if (! $item) {
            return $this->redirect()->toRoute('centrale');
        }

        $erreur = null;
        $sites = $this->siteTable->findAll('nom_site');
        $familles = $this->familleTable->findAll('nom_famille');

        if ($this->getRequest()->isPost()) {
            $type = trim((string) $this->params()->fromPost('type_centrale'));
            $idSite = (int) $this->params()->fromPost('id_site');
            $idFamille = $this->params()->fromPost('id_famille') ?: null;

            if ($type === '' || $idSite <= 0) {
                $erreur = 'Le type de centrale et le site sont obligatoires.';
            } else {
                $this->centraleTable->update($id, [
                    'type_centrale' => $type,
                    'id_site'       => $idSite,
                    'id_famille'    => $idFamille,
                ]);
                return $this->redirect()->toRoute('centrale');
            }
        }

        return new ViewModel([
            'erreur'   => $erreur,
            'item'     => $item,
            'sites'    => $sites,
            'familles' => $familles,
            'mode'     => 'modification',
        ]);
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $this->centraleTable->delete($id);

        return $this->redirect()->toRoute('centrale');
    }
}
