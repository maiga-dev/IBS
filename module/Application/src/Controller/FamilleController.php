<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\FamilleTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class FamilleController extends AbstractActionController
{
    private FamilleTable $familleTable;

    public function __construct(
        FamilleTable $familleTable
    ) {
        $this->familleTable = $familleTable;
    }

    /**
     * Liste des familles
     */
    public function indexAction(): ViewModel
    {
        $familles = $this->familleTable->getAll();

        return new ViewModel([
            'familles' => $familles,
        ]);
    }

    /**
     * Ajouter une famille
     */
    public function addAction(): ViewModel
    {
        $erreur = null;

        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            $nomFamille = trim(
                (string) ($data['nom_famille'] ?? '')
            );

            if ($nomFamille === '') {

                $erreur = 'Le nom de la famille est obligatoire.';

            } else {

                try {

                    $this->familleTable->insert([
                        'nom_famille' => $nomFamille,
                    ]);

                    return $this->redirect()
                        ->toRoute('famille');

                } catch (\Throwable $e) {

                    $erreur = 'Impossible d’enregistrer la famille.';
                }
            }
        }

        return new ViewModel([
            'item' => [
                'nom_famille' => '',
            ],
            'mode' => 'ajout',
            'erreur' => $erreur,
        ]);
    }

    /**
     * Modifier une famille
     */
    public function editAction(): ViewModel
    {
        $id = (int) $this->params()
            ->fromRoute('id', 0);

        if ($id <= 0) {
            return $this->redirect()
                ->toRoute('famille');
        }

        $famille = $this->familleTable->getById($id);

        if ($famille === null) {
            return $this->redirect()
                ->toRoute('famille');
        }

        $erreur = null;

        /*
         * Modification
         */
        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            $nomFamille = trim(
                (string) ($data['nom_famille'] ?? '')
            );

            if ($nomFamille === '') {

                $erreur = 'Le nom de la famille est obligatoire.';

            } else {

                try {

                    $this->familleTable->update(
                        $id,
                        [
                            'nom_famille' => $nomFamille,
                        ]
                    );

                    return $this->redirect()
                        ->toRoute('famille');

                } catch (\Throwable $e) {

                    $erreur = 'Impossible de modifier la famille.';
                }
            }

            /*
             * On conserve la valeur saisie dans le formulaire
             * en cas d'erreur.
             */
            $famille['nom_famille'] = $nomFamille;
        }

        return new ViewModel([
            'item' => $famille,
            'mode' => 'modification',
            'erreur' => $erreur,
        ]);
    }

    /**
     * Supprimer une famille
     */
    public function deleteAction()
    {
        $id = (int) $this->params()
            ->fromRoute('id', 0);

        if ($id > 0) {

            try {

                $this->familleTable->delete($id);

            } catch (\Throwable $e) {
                // Retour à la liste même en cas d'erreur.
            }
        }

        return $this->redirect()
            ->toRoute('famille');
    }
}