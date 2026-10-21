<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Response;

trait RoleTrait
{
    /**
     * Retourne l'identité de l'utilisateur connecté.
     */
    protected function utilisateurConnecte(): ?array
    {
        $authService = $this->getAuthenticationService();

        if (!$authService->hasIdentity()) {
            return null;
        }

        $identity = $authService->getIdentity();

        if (is_array($identity)) {
            return $identity;
        }

        if (is_object($identity)) {
            return (array) $identity;
        }

        return null;
    }

    /**
     * Récupère le service d'authentification.
     */
    protected function getAuthenticationService(): AuthenticationService
    {
        return $this
            ->getEvent()
            ->getApplication()
            ->getServiceManager()
            ->get(AuthenticationService::class);
    }

    /**
     * Vérifie qu'un utilisateur est connecté.
     */
    protected function exigerConnexion(): ?Response
    {
        if ($this->utilisateurConnecte() === null) {
            return $this->redirect()->toRoute(
                'auth',
                ['action' => 'login']
            );
        }

        return null;
    }

    /**
     * Vérifie qu'un utilisateur possède l'un des rôles autorisés.
     */
    protected function exigerRole(array $rolesAutorises): ?Response
    {
        $utilisateur = $this->utilisateurConnecte();

        if ($utilisateur === null) {
            return $this->redirect()->toRoute(
                'auth',
                ['action' => 'login']
            );
        }

        $role = strtolower(
            trim((string) ($utilisateur['role'] ?? ''))
        );

        $rolesAutorises = array_map(
            static function ($role): string {
                return strtolower(trim((string) $role));
            },
            $rolesAutorises
        );

        if (!in_array($role, $rolesAutorises, true)) {
            return $this->redirect()->toRoute('home');
        }

        return null;
    }

    /**
     * Retourne le rôle de l'utilisateur connecté.
     */
    protected function roleConnecte(): ?string
    {
        $utilisateur = $this->utilisateurConnecte();

        if ($utilisateur === null) {
            return null;
        }

        $role = strtolower(
            trim((string) ($utilisateur['role'] ?? ''))
        );

        return $role !== '' ? $role : null;
    }

    /**
     * Vérifie si l'utilisateur est administrateur.
     */
    protected function estAdmin(): bool
    {
        return $this->roleConnecte() === 'administrateur';
    }

    /**
     * Vérifie si l'utilisateur est technicien.
     */
    protected function estTechnicien(): bool
    {
        return $this->roleConnecte() === 'technicien';
    }

    /**
     * Vérifie si l'utilisateur est consultant.
     */
    protected function estConsultant(): bool
    {
        return $this->roleConnecte() === 'consultant';
    }
}