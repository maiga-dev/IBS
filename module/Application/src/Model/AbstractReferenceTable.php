<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use RuntimeException;

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
     * Alias utilisé par les contrôleurs.
     */
    public function find(int $id): ?array
    {
        return $this->getById($id);
    }

    /**
     * Insère un enregistrement.
     */
    public function insert(array $data): int
    {
        $colonnes = array_keys($data);

        $marqueurs = implode(
            ', ',
            array_fill(0, count($colonnes), '?')
        );

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) RETURNING %s',
            $this->table,
            implode(', ', $colonnes),
            $marqueurs,
            $this->primaryKey
        );

        $result = $this->adapter
            ->query(
                $sql,
                array_values($data)
            )
            ->toArray();

        return (int) $result[0][$this->primaryKey];
    }

    /**
     * Met à jour un enregistrement.
     */
    public function update(int $id, array $data): void
    {
        $sets = implode(
            ', ',
            array_map(
                static fn ($colonne) => "{$colonne} = ?",
                array_keys($data)
            )
        );

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = ?',
            $this->table,
            $sets,
            $this->primaryKey
        );

        $this->adapter->query(
            $sql,
            [...array_values($data), $id]
        );
    }

    /**
     * Supprime un enregistrement.
     *
     * Si l'enregistrement est encore référencé par une autre table,
     * PostgreSQL lève une violation de clé étrangère.
     *
     * On transforme cette erreur en RuntimeException afin que les
     * contrôleurs puissent afficher un message propre à l'utilisateur.
     */
    public function delete(int $id): void
    {
        $sql = sprintf(
            'DELETE FROM %s
             WHERE %s = ?',
            $this->table,
            $this->primaryKey
        );

        try {
            $this->adapter->query(
                $sql,
                [$id]
            );
        } catch (InvalidQueryException $e) {

            $message = $e->getMessage();

            if (
                str_contains($message, '23503')
                || str_contains($message, 'Foreign key violation')
                || str_contains($message, 'clé étrangère')
                || str_contains($message, 'foreign key')
            ) {
                throw new RuntimeException(
                    'Impossible de supprimer cet élément car il est encore utilisé par d’autres données.',
                    0,
                    $e
                );
            }

            throw $e;
        }
    }

    /**
     * Supprime un enregistrement par son ID.
     */
    public function deleteById(int $id): void
    {
        $this->delete($id);
    }
}