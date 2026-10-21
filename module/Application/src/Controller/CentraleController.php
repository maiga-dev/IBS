<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\CentraleTable;
use Application\Model\FamilleTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CentraleController extends AbstractActionController
{
    public function __construct(
        private CentraleTable $centraleTable,
        private FamilleTable $familleTable
    ) {
    }

    /**
     * Liste des centrales.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'centrales' => $this->centraleTable->findAllDetaille(),
        ]);
    }

    /**
     * Ajouter une centrale.
     *
     * Une centrale est une référence indépendante d'un site.
     * Le site sera choisi lors de la création de l'équipement.
     */
    public function addAction(): ViewModel
    {
        $erreur = null;

        $familles = $this->familleTable->findAll('nom_famille');

        $item = [
            'type_centrale' => '',
            'id_famille'    => null,
        ];

        if ($this->getRequest()->isPost()) {

            $type = trim(
                (string) $this->params()->fromPost('type_centrale')
            );

            $idFamille = $this->params()->fromPost('id_famille');

            $idFamille = (
                $idFamille !== ''
                && $idFamille !== null
            )
                ? (int) $idFamille
                : null;

            $item['type_centrale'] = $type;
            $item['id_famille'] = $idFamille;

            if ($type === '') {

                $erreur = 'Le type de centrale est obligatoire.';

            } else {

                $this->centraleTable->insert([
                    'type_centrale' => $type,
                    'id_famille'    => $idFamille,
                ]);

                return $this->redirect()->toRoute('centrale');
            }
        }

        return new ViewModel([
            'erreur'   => $erreur,
            'item'     => $item,
            'familles' => $familles,
            'mode'     => 'ajout',
        ]);
    }

    /**
     * Modifier une centrale.
     */
    public function editAction(): ViewModel
    {
        $id = (int) $this->params()->fromRoute('id');

        $item = $this->centraleTable->find($id);

        if (!$item) {
            return $this->redirect()->toRoute('centrale');
        }

        $erreur = null;

        $familles = $this->familleTable->findAll('nom_famille');

        if ($this->getRequest()->isPost()) {

            $type = trim(
                (string) $this->params()->fromPost('type_centrale')
            );

            $idFamille = $this->params()->fromPost('id_famille');

            $idFamille = (
                $idFamille !== ''
                && $idFamille !== null
            )
                ? (int) $idFamille
                : null;

            $item['type_centrale'] = $type;
            $item['id_famille'] = $idFamille;

            if ($type === '') {

                $erreur = 'Le type de centrale est obligatoire.';

            } else {

                $this->centraleTable->update($id, [
                    'type_centrale' => $type,
                    'id_famille'    => $idFamille,
                ]);

                return $this->redirect()->toRoute('centrale');
            }
        }

        return new ViewModel([
            'erreur'   => $erreur,
            'item'     => $item,
            'familles' => $familles,
            'mode'     => 'modification',
        ]);
    }

    /**
     * Supprimer une centrale.
     */
    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        try {

            $this->centraleTable->delete($id);

            return $this->redirect()->toRoute('centrale');

        } catch (\Laminas\Db\Adapter\Exception\InvalidQueryException $e) {

            $this->flashMessenger()->addErrorMessage(
                'Impossible de supprimer cette centrale car elle est encore utilisée dans une ou plusieurs données.'
            );

            return $this->redirect()->toRoute('centrale');
        }
    }
}