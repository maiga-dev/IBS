<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;

class UtilisateurTable extends AbstractReferenceTable
{
    public function __construct(Adapter $adapter)
    {
        parent::__construct(
            $adapter,
            'utilisateurs',
            'id'
        );
    }

    /**
     * Création d'un utilisateur.
     *
     * Le mot de passe est automatiquement hashé.
     *
     * Si le rôle est technicien, l'utilisateur est également
     * enregistré dans la table technicien.
     */
    public function insert(array $data): int
    {
        if (isset($data['mot_de_passe'])) {
            $data['mot_de_passe'] = password_hash(
                (string) $data['mot_de_passe'],
                PASSWORD_DEFAULT
            );
        }

        $role = strtolower(
            trim((string) ($data['role'] ?? 'consultant'))
        );

        /*
         * On limite les rôles aux trois rôles
         * utilisés par l'application.
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

        $data['role'] = $role;

        $idUtilisateur = parent::insert($data);

        /*
         * Seul le rôle technicien doit avoir
         * une correspondance dans la table technicien.
         */
        if ($role === 'technicien') {
            $this->creerTechnicien($idUtilisateur);
        }

        return $idUtilisateur;
    }

    /**
     * Modification d'un utilisateur.
     *
     * Le rôle est synchronisé avec la table technicien.
     */
    public function update(
        int $id,
        array $data
    ): void {
        if ($id <= 0) {
            return;
        }

        /*
         * Récupération du rôle actuel.
         *
         * Cela permet de conserver le rôle existant
         * si la méthode est appelée sans champ role.
         */
        $utilisateur = $this->find($id);

        if (!$utilisateur) {
            return;
        }

        $role = strtolower(
            trim(
                (string) (
                    $data['role']
                    ?? $utilisateur['role']
                    ?? 'consultant'
                )
            )
        );

        /*
         * Vérification du rôle.
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

        $data['role'] = $role;

        /*
         * Le mot de passe ne doit pas être modifié
         * directement par cette méthode.
         *
         * Utiliser changerMotDePasse().
         */
        if (isset($data['mot_de_passe'])) {
            unset($data['mot_de_passe']);
        }

        parent::update($id, $data);

        /*
         * Synchronisation avec la table technicien.
         */
        if ($role === 'technicien') {
            $this->creerTechnicien($id);
        } else {
            $this->supprimerTechnicien($id);
        }
    }

    /**
     * Change le mot de passe d'un utilisateur.
     */
    public function changerMotDePasse(
        int $id,
        string $motDePasse
    ): void {
        if (
            $id <= 0
            || trim($motDePasse) === ''
        ) {
            return;
        }

        parent::update($id, [
            'mot_de_passe' => password_hash(
                $motDePasse,
                PASSWORD_DEFAULT
            ),
        ]);
    }

    /**
     * Recherche un utilisateur par son email.
     */
    public function findByEmail(
        string $email
    ): ?array {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        $row = $this->adapter->query(
            'SELECT *
             FROM utilisateurs
             WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))
             LIMIT 1',
            [$email]
        )->current();

        return $row
            ? (array) $row
            : null;
    }

    /**
     * Liste des techniciens disponibles
     * pour l'affectation d'une maintenance.
     */
    public function findTechniciens(): array
    {
        return $this->adapter->query(
            "
            SELECT
                u.id,
                u.nom,
                u.prenom,
                u.telephone,
                u.email,
                u.role
            FROM utilisateurs u
            INNER JOIN technicien t
                ON t.id_utilisateur = u.id
            WHERE LOWER(TRIM(u.role)) = 'technicien'
            ORDER BY u.nom ASC, u.prenom ASC
            ",
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }

    /**
     * Crée le lien utilisateur → technicien
     * uniquement s'il n'existe pas déjà.
     */
    private function creerTechnicien(
        int $idUtilisateur
    ): void {
        if ($idUtilisateur <= 0) {
            return;
        }

        $existant = $this->adapter->query(
            'SELECT id_utilisateur
             FROM technicien
             WHERE id_utilisateur = ?
             LIMIT 1',
            [$idUtilisateur]
        )->current();

        if ($existant) {
            return;
        }

        $this->adapter->query(
            'INSERT INTO technicien (id_utilisateur)
             VALUES (?)',
            [$idUtilisateur]
        );
    }

    /**
     * Supprime le lien avec la table technicien
     * lorsque l'utilisateur n'est plus technicien.
     */
    private function supprimerTechnicien(
        int $idUtilisateur
    ): void {
        if ($idUtilisateur <= 0) {
            return;
        }

        $this->adapter->query(
            'DELETE FROM technicien
             WHERE id_utilisateur = ?',
            [$idUtilisateur]
        );
    }
}
