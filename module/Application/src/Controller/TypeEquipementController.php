<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\TypeEquipementTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Throwable;

class TypeEquipementController extends AbstractActionController
{
    /**
     * Dossier des vues.
     */
    private const DOSSIER_VUES = 'application/parametre/type_equipement/';

    public function __construct(
        private TypeEquipementTable $typeEquipementTable
    ) {
    }

    /**
     * Liste des types d'équipement.
     */
    public function indexAction(): ViewModel
    {
        $types = $this->typeEquipementTable->findAll();

        $viewModel = new ViewModel([
            'types' => $types,
        ]);

        $viewModel->setTemplate(
            self::DOSSIER_VUES . 'index'
        );

        return $viewModel;
    }

    /**
     * Ajouter un type d'équipement.
     */
    public function addAction(): ViewModel
    {
        $erreur = null;

        $item = [
            'nom_type' => '',
        ];

        if ($this->getRequest()->isPost()) {

            $nom = trim(
                (string) $this->params()->fromPost('nom_type', '')
            );

            $item['nom_type'] = $nom;

            if ($nom === '') {

                $erreur = 'Le nom du type est obligatoire.';

            } else {

                try {

                    $this->typeEquipementTable->insert([
                        'nom_type' => $nom,
                    ]);

                    return $this->redirect()->toRoute(
                        'type_equipement'
                    );

                } catch (Throwable $e) {

                    error_log(
                        'Erreur ajout type_equipement : '
                        . $e->getMessage()
                    );

                    $erreur =
                        'Une erreur est survenue lors de l’enregistrement du type d’équipement.';
                }
            }
        }

        $viewModel = new ViewModel([
            'erreur' => $erreur,
            'item' => $item,
            'mode' => 'ajout',
        ]);

        $viewModel->setTemplate(
            self::DOSSIER_VUES . 'add'
        );

        return $viewModel;
    }

    /**
     * Modifier un type d'équipement.
     */
    public function editAction(): ViewModel
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if ($id <= 0) {

            return $this->redirect()->toRoute(
                'type_equipement'
            );
        }

        $item = $this->typeEquipementTable->find($id);

        if (!$item) {

            return $this->redirect()->toRoute(
                'type_equipement'
            );
        }

        $erreur = null;

        if ($this->getRequest()->isPost()) {

            $nom = trim(
                (string) $this->params()->fromPost('nom_type', '')
            );

            $item['nom_type'] = $nom;

            if ($nom === '') {

                $erreur = 'Le nom du type est obligatoire.';

            } else {

                try {

                    $this->typeEquipementTable->update(
                        $id,
                        [
                            'nom_type' => $nom,
                        ]
                    );

                    return $this->redirect()->toRoute(
                        'type_equipement'
                    );

                } catch (Throwable $e) {

                    error_log(
                        'Erreur modification type_equipement : '
                        . $e->getMessage()
                    );

                    $erreur =
                        'Une erreur est survenue lors de la modification du type d’équipement.';
                }
            }
        }

        $viewModel = new ViewModel([
            'erreur' => $erreur,
            'item' => $item,
            'mode' => 'modification',
        ]);

        $viewModel->setTemplate(
            self::DOSSIER_VUES . 'edit'
        );

        return $viewModel;
    }

    /**
     * Supprimer un type d'équipement.
     */
    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if ($id <= 0) {

            return $this->redirect()->toRoute(
                'type_equipement'
            );
        }

        try {

            $this->typeEquipementTable->delete($id);

        } catch (Throwable $e) {

            error_log(
                'Erreur suppression type_equipement : '
                . $e->getMessage()
            );
        }

        return $this->redirect()->toRoute(
            'type_equipement'
        );
    }
}