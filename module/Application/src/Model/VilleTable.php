<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;

class VilleTable extends AbstractReferenceTable
{
    public function __construct(Adapter $adapter)
    {
        parent::__construct(
            $adapter,
            'ville',
            'id_ville'
        );
    }

    /**
     * Liste toutes les villes.
     */
    public function getAll(): array
    {
        return $this->adapter
            ->query(
                'SELECT *
                 FROM ville
                 ORDER BY nom_ville',
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();
    }

    /**
     * Liste détaillée avec le pays.
     */
    public function findAllDetaille(): array
    {
        $sql = "
            SELECT
                v.*,
                p.nom_pays
            FROM ville v
            LEFT JOIN pays p
                ON p.id_pays = v.id_pays
            ORDER BY v.nom_ville
        ";

        return $this->adapter
            ->query(
                $sql,
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();
    }

    /**
     * Liste les villes d'un pays.
     */
    public function findByPays(int $idPays): array
    {
        return $this->adapter
            ->query(
                'SELECT *
                 FROM ville
                 WHERE id_pays = ?
                 ORDER BY nom_ville',
                [$idPays]
            )
            ->toArray();
    }

    /**
     * Récupérer une ville.
     */
    public function getById(int $id): ?array
    {
        $rows = $this->adapter
            ->query(
                'SELECT *
                 FROM ville
                 WHERE id_ville = ?',
                [$id]
            )
            ->toArray();

        return $rows[0] ?? null;
    }

    // insert()/update()/delete() ne sont plus redéfinis ici : hérités
    // d'AbstractReferenceTable (format tableau), compatibles avec le
    // parent ET avec VilleController qui appelle
    // insert(['nom_ville' => ..., 'id_pays' => ...]).
}