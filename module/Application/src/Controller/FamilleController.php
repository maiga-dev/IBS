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
        $error = null;

        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            $nomFamille = trim(
                (string) (
                    $data['nom_famille']
                    ?? ''
                )
            );

            if ($nomFamille === '') {

                $error = 'Le nom de la famille est obligatoire.';

            } else {

                try {

                    $this->familleTable->insert([
                        'nom_famille' => $nomFamille,
                    ]);

                    return $this->redirect()
                        ->toRoute('famille');

                } catch (\Throwable $e) {

                    $error = 'Impossible d’enregistrer la famille.';
                }
            }
        }

        return new ViewModel([
            'error' => $error,
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

        $error = null;

        if ($this->getRequest()->isPost()) {

            $data = $this->params()->fromPost();

            $nomFamille = trim(
                (string) (
                    $data['nom_famille']
                    ?? ''
                )
            );

            if ($nomFamille === '') {

                $error = 'Le nom de la famille est obligatoire.';

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

                    $error = 'Impossible de modifier la famille.';
                }
            }
        }

        return new ViewModel([
            'famille' => $famille,
            'error' => $error,
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
