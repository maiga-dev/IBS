<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;

class UtilisateurTable extends AbstractReferenceTable
{
    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter, 'utilisateurs', 'id');
    }

    /** Hash automatiquement le mot de passe à la création. */
    public function insert(array $data): int
    {
        if (isset($data['mot_de_passe'])) {
            $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        }

        return parent::insert($data);
    }

    /** À utiliser uniquement si l'admin change le mot de passe d'un compte. */
    public function changerMotDePasse(int $id, string $motDePasse): void
    {
        $this->update($id, [
            'mot_de_passe' => password_hash($motDePasse, PASSWORD_DEFAULT),
        ]);
    }

    public function findByEmail(string $email): ?array
    {
        $row = $this->adapter->query(
            'SELECT * FROM utilisateurs WHERE email = ?',
            [$email]
        )->current();

        return $this->normaliserLigne($row);
    }

    /** Liste des techniciens, pour le menu d'affectation d'une maintenance. */
    public function findTechniciens(): array
    {
        return $this->adapter->query(
            "SELECT * FROM utilisateurs WHERE role = 'technicien' ORDER BY nom",
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }
}
