<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\Adapter;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class AuthController extends AbstractActionController
{
    public function __construct(
        private Adapter $adapter,
        private AuthenticationService $authService
    ) {
    }

    /**
     * Connexion.
     */
    public function loginAction()
    {
        /*
         * Si l'utilisateur est déjà connecté,
         * il n'a aucune raison de revenir sur la connexion.
         */
        if ($this->authService->hasIdentity()) {
            return $this->redirect()->toRoute('home');
        }

        $this->layout('layout/layout-auth');

        $erreur = null;
        $email = '';

        if ($this->getRequest()->isPost()) {

            $email = trim(
                (string) $this->params()
                    ->fromPost('email')
            );

            $motDePasse = (string) $this->params()
                ->fromPost('mot_de_passe');

            if ($email === '' || $motDePasse === '') {

                $erreur = 'Veuillez renseigner votre email et votre mot de passe.';

            } else {

                $utilisateur = $this->adapter->query(
                    'SELECT *
                     FROM utilisateurs
                     WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))
                     LIMIT 1',
                    [$email]
                )->current();

                if (
                    $utilisateur
                    && password_verify(
                        $motDePasse,
                        (string) $utilisateur['mot_de_passe']
                    )
                ) {

                    /*
                     * Normalisation du rôle.
                     */
                    $role = strtolower(
                        trim(
                            (string) ($utilisateur['role'] ?? 'consultant')
                        )
                    );

                    /*
                     * Seuls les trois rôles de l'application
                     * sont autorisés.
                     */
                    if (!in_array(
                        $role,
                        [
                            'administrateur',
                            'technicien',
                            'consultant',
                        ],
                        true
                    )) {
                        $role = 'consultant';
                    }

                    /*
                     * On conserve uniquement les informations
                     * nécessaires dans la session.
                     */
                    $this->authService->getStorage()->write([
                        'id'        => $utilisateur['id'],
                        'nom'       => $utilisateur['nom'],
                        'prenom'    => $utilisateur['prenom'],
                        'email'     => $utilisateur['email'],
                        'telephone' => $utilisateur['telephone'] ?? null,
                        'role'      => $role,
                    ]);

                    return $this->redirect()
                        ->toRoute('home');
                }

                $erreur = 'Email ou mot de passe incorrect.';
            }
        }

        return new ViewModel([
            'erreur' => $erreur,
            'email'  => $email,
        ]);
    }

    /**
     * Inscription.
     *
     * Les comptes créés depuis l'inscription publique
     * sont toujours des consultants.
     *
     * Les rôles administrateur et technicien sont attribués
     * uniquement par un administrateur depuis Paramètres > Utilisateurs.
     */
    public function registerAction()
    {
        if ($this->authService->hasIdentity()) {
            return $this->redirect()->toRoute('home');
        }

        $this->layout('layout/layout-auth');

        $erreur = null;

        $valeurs = [
            'nom'        => '',
            'prenom'     => '',
            'email'      => '',
            'telephone'  => '',
        ];

        if ($this->getRequest()->isPost()) {

            $valeurs['nom'] = trim(
                (string) $this->params()
                    ->fromPost('nom')
            );

            $valeurs['prenom'] = trim(
                (string) $this->params()
                    ->fromPost('prenom')
            );

            $valeurs['email'] = trim(
                (string) $this->params()
                    ->fromPost('email')
            );

            $valeurs['telephone'] = trim(
                (string) $this->params()
                    ->fromPost('telephone')
            );

            $motDePasse = (string) $this->params()
                ->fromPost('mot_de_passe');

            $confirmation = (string) $this->params()
                ->fromPost('confirmation');

            if (
                $valeurs['nom'] === ''
                || $valeurs['prenom'] === ''
                || $valeurs['email'] === ''
            ) {

                $erreur =
                    'Nom, prénom et email sont obligatoires.';

            } elseif (
                !filter_var(
                    $valeurs['email'],
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                $erreur =
                    'Adresse email invalide.';

            } elseif (
                $motDePasse === ''
                || strlen($motDePasse) < 8
            ) {

                $erreur =
                    'Le mot de passe doit contenir au moins 8 caractères.';

            } elseif ($motDePasse !== $confirmation) {

                $erreur =
                    'Les mots de passe ne correspondent pas.';

            } else {

                $existant = $this->adapter->query(
                    'SELECT id
                     FROM utilisateurs
                     WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))
                     LIMIT 1',
                    [$valeurs['email']]
                )->current();

                if ($existant) {

                    $erreur =
                        'Un compte existe déjà avec cet email.';

                } else {

                    $hash = password_hash(
                        $motDePasse,
                        PASSWORD_DEFAULT
                    );

                    /*
                     * Une inscription publique crée toujours
                     * un consultant.
                     *
                     * Il n'est pas possible de choisir
                     * administrateur ou technicien ici.
                     */
                    $role = 'consultant';

                    $this->adapter->query(
                        'INSERT INTO utilisateurs
                        (
                            nom,
                            prenom,
                            telephone,
                            email,
                            mot_de_passe,
                            role
                        )
                        VALUES (?, ?, ?, ?, ?, ?)',
                        [
                            $valeurs['nom'],
                            $valeurs['prenom'],
                            $valeurs['telephone'] !== ''
                                ? $valeurs['telephone']
                                : null,
                            $valeurs['email'],
                            $hash,
                            $role,
                        ]
                    );

                    return $this->redirect()
                        ->toRoute(
                            'auth',
                            ['action' => 'login']
                        );
                }
            }
        }

        return new ViewModel([
            'erreur'  => $erreur,
            'valeurs' => $valeurs,
        ]);
    }

    /**
     * Déconnexion.
     */
    public function logoutAction()
    {
        $this->authService->clearIdentity();

        return $this->redirect()
            ->toRoute(
                'auth',
                ['action' => 'login']
            );
    }
}