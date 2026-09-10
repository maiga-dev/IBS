<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\UtilisateurTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class UtilisateurController extends AbstractActionController
{
    public const ROLES = [
        'administrateur',
        'responsable',
        'technicien',
    ];

    public function __construct(
        private UtilisateurTable $utilisateurTable
    ) {
    }

    /**
     * Liste des utilisateurs
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'utilisateurs' => $this->utilisateurTable->findAll('nom'),
        ]);
    }

    /**
     * Ajouter un utilisateur
     */
    public function addAction(): ViewModel
    {
        $erreur = null;

        $item = [
            'nom'        => '',
            'prenom'     => '',
            'telephone'  => '',
            'email'      => '',
            'role'       => 'technicien',
        ];

        if ($this->getRequest()->isPost()) {

            $valeurs = $this->valeursFormulaire();

            $motDePasse = trim(
                (string) $this->params()->fromPost('mot_de_passe')
            );

            // Conserver les valeurs saisies dans le formulaire
            $item = array_merge($item, $valeurs);

            if (
                $valeurs['nom'] === ''
                || $valeurs['prenom'] === ''
                || $valeurs['email'] === ''
            ) {
                $erreur = 'Nom, prénom et email sont obligatoires.';

            } elseif (
                !filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)
            ) {
                $erreur = 'Adresse email invalide.';

            } elseif (
                $motDePasse === ''
                || strlen($motDePasse) < 8
            ) {
                $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';

            } elseif (
                $this->utilisateurTable->findByEmail($valeurs['email'])
            ) {
                $erreur = 'Un compte existe déjà avec cet email.';

            } else {

                $this->utilisateurTable->insert([
                    ...$valeurs,
                    'mot_de_passe' => $motDePasse,
                ]);

                return $this->redirect()->toRoute('utilisateur');
            }
        }

        $view = new ViewModel([
            'erreur' => $erreur,
            'item'   => $item,
            'roles'  => self::ROLES,
            'mode'   => 'ajout',
        ]);

        // Vue située dans :
        // view/application/parametre/utilisateur/add.phtml
        $view->setTemplate('application/utilisateur/add');

        return $view;
    }

    /**
     * Modifier un utilisateur
     */
    public function editAction(): ViewModel
    {
        $id = (int) $this->params()->fromRoute('id');

        $item = $this->utilisateurTable->find($id);

        if (!$item) {
            return $this->redirect()->toRoute('utilisateur');
        }

        $erreur = null;

        if ($this->getRequest()->isPost()) {

            $valeurs = $this->valeursFormulaire();

            $motDePasse = trim(
                (string) $this->params()->fromPost('mot_de_passe')
            );

            // Conserver les valeurs saisies
            $item = array_merge($item, $valeurs);

            if (
                $valeurs['nom'] === ''
                || $valeurs['prenom'] === ''
                || $valeurs['email'] === ''
            ) {
                $erreur = 'Nom, prénom et email sont obligatoires.';

            } elseif (
                !filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)
            ) {
                $erreur = 'Adresse email invalide.';

            } else {

                $existant = $this->utilisateurTable
                    ->findByEmail($valeurs['email']);

                if (
                    $existant
                    && (int) $existant['id'] !== $id
                ) {
                    $erreur = 'Un autre compte utilise déjà cet email.';

                } else {

                    $this->utilisateurTable->update(
                        $id,
                        $valeurs
                    );

                    /*
                     * Le mot de passe est facultatif lors
                     * de la modification.
                     */
                    if ($motDePasse !== '') {

                        if (strlen($motDePasse) < 8) {

                            $erreur =
                                'Le mot de passe doit contenir au moins 8 caractères.';

                        } else {

                            $this->utilisateurTable
                                ->changerMotDePasse(
                                    $id,
                                    $motDePasse
                                );
                        }
                    }

                    if (!$erreur) {
                        return $this->redirect()
                            ->toRoute('utilisateur');
                    }
                }
            }
        }

        $view = new ViewModel([
            'erreur' => $erreur,
            'item'   => $item,
            'roles'  => self::ROLES,
            'mode'   => 'modification',
        ]);

        // Vue située dans :
        // view/application/parametre/utilisateur/edit.phtml
        $view->setTemplate('application/utilisateur/edit');

        return $view;
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        $this->utilisateurTable->delete($id);

        return $this->redirect()->toRoute('utilisateur');
    }

    /**
     * Récupération et validation des données du formulaire
     */
    private function valeursFormulaire(): array
    {
        $role = (string) $this->params()
            ->fromPost('role', 'technicien');

        if (!in_array($role, self::ROLES, true)) {
            $role = 'technicien';
        }

        return [
            'nom' => trim(
                (string) $this->params()->fromPost('nom')
            ),

            'prenom' => trim(
                (string) $this->params()->fromPost('prenom')
            ),

            'telephone' => trim(
                (string) $this->params()->fromPost('telephone')
            ) ?: null,

            'email' => trim(
                (string) $this->params()->fromPost('email')
            ),

            'role' => $role,
        ];
    }
}
