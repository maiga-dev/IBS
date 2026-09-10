<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use Laminas\Authentication\AuthenticationService;

class AuthController extends AbstractActionController
{
    private Adapter $adapter;
    private AuthenticationService $authService;

    public function __construct(Adapter $adapter, AuthenticationService $authService)
    {
        $this->adapter = $adapter;
        $this->authService = $authService;
    }

    public function loginAction()
    {
        // Déjà connecté → direction le tableau de bord
        if ($this->authService->hasIdentity()) {
            return $this->redirect()->toRoute('home');
        }

        $this->layout('layout/layout-auth');

        $erreur = null;
        $email = '';

        if ($this->getRequest()->isPost()) {
            $email = trim((string) $this->params()->fromPost('email'));
            $motDePasse = (string) $this->params()->fromPost('mot_de_passe');

            $utilisateur = $this->adapter->query(
                'SELECT * FROM utilisateurs WHERE email = ?',
                [$email]
            )->current();

            if ($utilisateur && password_verify($motDePasse, (string) $utilisateur['mot_de_passe'])) {

                $this->authService->getStorage()->write([
                    'id'     => $utilisateur['id'],
                    'nom'    => $utilisateur['nom'],
                    'prenom' => $utilisateur['prenom'],
                    'email'  => $utilisateur['email'],
                    'role'   => $utilisateur['role'],
                ]);

                return $this->redirect()->toRoute('home');
            }

            $erreur = 'Email ou mot de passe incorrect.';
        }

        return new ViewModel([
            'erreur' => $erreur,
            'email'  => $email,
        ]);
    }

    public function registerAction()
    {
        if ($this->authService->hasIdentity()) {
            return $this->redirect()->toRoute('home');
        }

        $this->layout('layout/layout-auth');

        $erreur = null;
        $valeurs = [
            'nom' => '', 'prenom' => '', 'email' => '', 'telephone' => '',
        ];

        if ($this->getRequest()->isPost()) {
            $valeurs['nom']       = trim((string) $this->params()->fromPost('nom'));
            $valeurs['prenom']    = trim((string) $this->params()->fromPost('prenom'));
            $valeurs['email']     = trim((string) $this->params()->fromPost('email'));
            $valeurs['telephone'] = trim((string) $this->params()->fromPost('telephone'));
            $motDePasse           = (string) $this->params()->fromPost('mot_de_passe');
            $confirmation         = (string) $this->params()->fromPost('confirmation');

            // Rôle par défaut pour toute inscription libre — à faire valider/
            // reclasser par un administrateur ensuite (§4 du cahier des charges :
            // seul un admin devrait accorder les rôles sensibles).
            $role = 'technicien';

            if ($valeurs['nom'] === '' || $valeurs['prenom'] === '' || $valeurs['email'] === '' || $motDePasse === '') {
                $erreur = 'Tous les champs obligatoires doivent être remplis.';
            } elseif (! filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)) {
                $erreur = 'Adresse email invalide.';
            } elseif (strlen($motDePasse) < 8) {
                $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($motDePasse !== $confirmation) {
                $erreur = 'Les mots de passe ne correspondent pas.';
            } else {
                $existant = $this->adapter->query(
                    'SELECT id FROM utilisateurs WHERE email = ?',
                    [$valeurs['email']]
                )->current();

                if ($existant) {
                    $erreur = 'Un compte existe déjà avec cet email.';
                } else {
                    $hash = password_hash($motDePasse, PASSWORD_DEFAULT);

                    $this->adapter->query(
                        'INSERT INTO utilisateurs (nom, prenom, telephone, email, mot_de_passe, role)
                         VALUES (?, ?, ?, ?, ?, ?)',
                        [
                            $valeurs['nom'],
                            $valeurs['prenom'],
                            $valeurs['telephone'],
                            $valeurs['email'],
                            $hash,
                            $role,
                        ]
                    );

                    return $this->redirect()->toRoute('auth', ['action' => 'login']);
                }
            }
        }

        return new ViewModel([
            'erreur'  => $erreur,
            'valeurs' => $valeurs,
        ]);
    }

    public function logoutAction()
    {
        $this->authService->clearIdentity();

        return $this->redirect()->toRoute('auth', ['action' => 'login']);
    }
}