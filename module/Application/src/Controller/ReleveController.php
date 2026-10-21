<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\EquipementTable;
use Application\Model\ReleveTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class ReleveController extends AbstractActionController
{
    public function __construct(
        private ReleveTable $releveTable,
        private EquipementTable $equipementTable
    ) {
    }

    /**
     * Liste des relevés d'un équipement.
     *
     * URL :
     * /equipements/:id/releves
     */
    public function indexAction(): ViewModel
    {
        $idEquipement = (int) $this->params()->fromRoute('id');

        /*
         * L'équipement est récupéré avec la méthode réellement
         * disponible dans EquipementTable.
         */
        $equipement = $this->equipementTable->getById($idEquipement);

        if (!$equipement) {
            return $this->redirect()->toRoute('equipement');
        }

        $releves = $this->releveTable->findByEquipement($idEquipement);

        $view = new ViewModel([
            'equipement'   => $equipement,
            'releves'      => $releves,
            'idEquipement' => $idEquipement,
        ]);

        /*
         * Les vues Relevé sont rangées sous :
         *
         * application/
         * └── parametre/
         *     └── equipement/
         *         └── releve/
         */
        $view->setTemplate(
            'application/parametre/equipement/releve/index'
        );

        return $view;
    }

    /**
     * Ajouter un relevé.
     *
     * URL :
     * /equipements/:id/releves/add
     */
    public function addAction(): ViewModel
    {
        $idEquipement = (int) $this->params()->fromRoute('id');

        $equipement = $this->equipementTable->getById($idEquipement);

        if (!$equipement) {
            return $this->redirect()->toRoute('equipement');
        }

        $error = null;

        /*
         * Valeurs initiales du formulaire.
         */
        $postData = [
            'date_releve'      => date('Y-m-d\TH:i'),
            'heure_total'      => '',
            'heure_production' => '',
            'pression'         => '',
            'purete'           => '',
            'observation'      => '',
        ];

        /*
         * Traitement du formulaire.
         */
        if ($this->getRequest()->isPost()) {

            $postData = [
                'date_releve' => trim(
                    (string) $this->params()->fromPost('date_releve')
                ),

                'heure_total' => trim(
                    (string) $this->params()->fromPost('heure_total')
                ),

                'heure_production' => trim(
                    (string) $this->params()->fromPost('heure_production')
                ),

                'pression' => trim(
                    (string) $this->params()->fromPost('pression')
                ),

                'purete' => trim(
                    (string) $this->params()->fromPost('purete')
                ),

                'observation' => trim(
                    (string) $this->params()->fromPost('observation')
                ),
            ];

            /*
             * Validation minimale.
             */
            if ($postData['date_releve'] === '') {
                $error = 'La date du relevé est obligatoire.';
            }

            /*
             * Enregistrement.
             *
             * L'id_equipement ne vient PAS du formulaire.
             * Il vient de l'URL afin d'éviter qu'un utilisateur
             * rattache accidentellement un relevé au mauvais équipement.
             */
            if ($error === null) {

                $this->releveTable->insert([
                    'id_equipement' => $idEquipement,

                    'date_releve' => $postData['date_releve'],

                    'heure_total' => $postData['heure_total'] !== ''
                        ? $postData['heure_total']
                        : null,

                    'heure_production' => $postData['heure_production'] !== ''
                        ? $postData['heure_production']
                        : null,

                    'pression' => $postData['pression'] !== ''
                        ? $postData['pression']
                        : null,

                    'purete' => $postData['purete'] !== ''
                        ? $postData['purete']
                        : null,

                    'observation' => $postData['observation'] !== ''
                        ? $postData['observation']
                        : null,
                ]);

                /*
                 * Retour à la liste des relevés de l'équipement.
                 */
                return $this->redirect()->toRoute(
                    'releve',
                    [
                        'id' => $idEquipement,
                    ]
                );
            }
        }

        $view = new ViewModel([
            'equipement'   => $equipement,
            'idEquipement' => $idEquipement,
            'postData'     => $postData,
            'error'        => $error,
        ]);

        $view->setTemplate(
            'application/parametre/equipement/releve/add'
        );

        return $view;
    }

    /**
     * Modifier un relevé.
     *
     * URL :
     * /equipements/:id/releves/edit/:releve
     */
    public function editAction(): ViewModel
    {
        $idEquipement = (int) $this->params()->fromRoute('id');
        $idReleve = (int) $this->params()->fromRoute('releve');

        /*
         * Récupération de l'équipement.
         */
        $equipement = $this->equipementTable->getById($idEquipement);

        /*
         * Récupération du relevé.
         */
        $releve = $this->releveTable->find($idReleve);

        /*
         * Vérification de l'existence des deux éléments.
         */
        if (!$equipement || !$releve) {
            return $this->redirect()->toRoute(
                'releve',
                [
                    'id' => $idEquipement,
                ]
            );
        }

        /*
         * Sécurité importante :
         *
         * Le relevé doit réellement appartenir à l'équipement
         * affiché dans l'URL.
         */
        if (
            !isset($releve['id_equipement'])
            || (int) $releve['id_equipement'] !== $idEquipement
        ) {
            return $this->redirect()->toRoute(
                'releve',
                [
                    'id' => $idEquipement,
                ]
            );
        }

        $error = null;

        /*
         * Valeurs initiales du formulaire.
         */
        $postData = [
            'date_releve' => $releve['date_releve'] ?? '',
            'heure_total' => $releve['heure_total'] ?? '',
            'heure_production' => $releve['heure_production'] ?? '',
            'pression' => $releve['pression'] ?? '',
            'purete' => $releve['purete'] ?? '',
            'observation' => $releve['observation'] ?? '',
        ];

        /*
         * Traitement du formulaire.
         */
        if ($this->getRequest()->isPost()) {

            $postData = [
                'date_releve' => trim(
                    (string) $this->params()->fromPost('date_releve')
                ),

                'heure_total' => trim(
                    (string) $this->params()->fromPost('heure_total')
                ),

                'heure_production' => trim(
                    (string) $this->params()->fromPost('heure_production')
                ),

                'pression' => trim(
                    (string) $this->params()->fromPost('pression')
                ),

                'purete' => trim(
                    (string) $this->params()->fromPost('purete')
                ),

                'observation' => trim(
                    (string) $this->params()->fromPost('observation')
                ),
            ];

            /*
             * Validation.
             */
            if ($postData['date_releve'] === '') {
                $error = 'La date du relevé est obligatoire.';
            }

            /*
             * Mise à jour.
             */
            if ($error === null) {

                $this->releveTable->update(
                    $idReleve,
                    [
                        'date_releve' => $postData['date_releve'],

                        'heure_total' => $postData['heure_total'] !== ''
                            ? $postData['heure_total']
                            : null,

                        'heure_production' => $postData['heure_production'] !== ''
                            ? $postData['heure_production']
                            : null,

                        'pression' => $postData['pression'] !== ''
                            ? $postData['pression']
                            : null,

                        'purete' => $postData['purete'] !== ''
                            ? $postData['purete']
                            : null,

                        'observation' => $postData['observation'] !== ''
                            ? $postData['observation']
                            : null,
                    ]
                );

                /*
                 * Retour à la liste.
                 */
                return $this->redirect()->toRoute(
                    'releve',
                    [
                        'id' => $idEquipement,
                    ]
                );
            }
        }

        $view = new ViewModel([
            'equipement'   => $equipement,
            'releve'       => $releve,
            'postData'     => $postData,
            'idEquipement' => $idEquipement,
            'idReleve'     => $idReleve,
            'error'        => $error,
        ]);

        $view->setTemplate(
            'application/parametre/equipement/releve/edit'
        );

        return $view;
    }

    /**
     * Supprimer un relevé.
     *
     * URL :
     * /equipements/:id/releves/delete/:releve
     */
    public function deleteAction()
    {
        $idEquipement = (int) $this->params()->fromRoute('id');
        $idReleve = (int) $this->params()->fromRoute('releve');

        /*
         * Récupération du relevé.
         */
        $releve = $this->releveTable->find($idReleve);

        /*
         * Sécurité :
         * suppression uniquement si le relevé appartient
         * bien à l'équipement demandé.
         */
        if (
            $releve
            && isset($releve['id_equipement'])
            && (int) $releve['id_equipement'] === $idEquipement
        ) {
            $this->releveTable->delete($idReleve);
        }

        /*
         * Retour à la liste des relevés.
         */
        return $this->redirect()->toRoute(
            'releve',
            [
                'id' => $idEquipement,
            ]
        );
    }
}