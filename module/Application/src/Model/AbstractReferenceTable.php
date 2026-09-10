<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;

abstract class AbstractReferenceTable
{
    protected Adapter $adapter;

    protected string $table;

    protected string $primaryKey;

    public function __construct(
        Adapter $adapter,
        string $table,
        string $primaryKey
    ) {
        $this->adapter = $adapter;
        $this->table = $table;
        $this->primaryKey = $primaryKey;
    }

    /**
     * Liste tous les enregistrements.
     */
    public function getAll(): array
    {
        $sql = sprintf(
            'SELECT * FROM %s ORDER BY %s',
            $this->table,
            $this->primaryKey . ' DESC'
        );

        return $this->adapter
            ->query(
                $sql,
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();
    }

    /**
     * Alias compatible avec les anciens contrôleurs.
     */
    public function findAll(): array
    {
        return $this->getAll();
    }

    /**
     * Recherche un enregistrement par son ID.
     *
     * Utilise ->toArray() plutôt que ->current() : évite le problème
     * d'ArrayObject rencontré ailleurs, sans dépendre d'un trait externe.
     */
    public function getById(int $id): ?array
    {
        $sql = sprintf(
            'SELECT *
             FROM %s
             WHERE %s = ?',
            $this->table,
            $this->primaryKey
        );

        $result = $this->adapter
            ->query(
                $sql,
                [$id]
            )
            ->toArray();

        return $result[0] ?? null;
    }

    /**
     * Alias compatible avec les anciens contrôleurs.
     */
    public function findById(int $id): ?array
    {
        return $this->getById($id);
    }

    /**
     * NOUVEAU — alias attendu par FamilleController/PaysController/
     * VilleController/SiteController/CentraleController/
     * TypeEquipementController (ils appellent find(), pas getById()).
     */
    public function find(int $id): ?array
    {
        return $this->getById($id);
    }

    /**
     * NOUVEAU — manquait complètement, cause de
     * "Call to undefined method insert()".
     */
    public function insert(array $data): int
    {
        $colonnes = array_keys($data);
        $marqueurs = implode(', ', array_fill(0, count($colonnes), '?'));

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) RETURNING %s',
            $this->table,
            implode(', ', $colonnes),
            $marqueurs,
            $this->primaryKey
        );

        $result = $this->adapter
            ->query($sql, array_values($data))
            ->toArray();

        return (int) $result[0][$this->primaryKey];
    }

    /**
     * NOUVEAU — manquait complètement, nécessaire pour les actions
     * "edit" de tous les contrôleurs de référence.
     */
    public function update(int $id, array $data): void
    {
        $sets = implode(
            ', ',
            array_map(static fn ($colonne) => "{$colonne} = ?", array_keys($data))
        );

        $sql = "UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = ?";

        $this->adapter->query($sql, [...array_values($data), $id]);
    }

    /**
     * Supprime un enregistrement.
     */
    public function delete(int $id): void
    {
        $sql = sprintf(
            'DELETE FROM %s
             WHERE %s = ?',
            $this->table,
            $this->primaryKey
        );

        $this->adapter->query(
            $sql,
            [$id]
        );
    }

    /**
     * Supprime un enregistrement par son ID.
     */
    public function deleteById(int $id): void
    {
        $this->delete($id);
    }
}