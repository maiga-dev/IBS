<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;

class FamilleTable extends AbstractReferenceTable
{
    public function __construct(Adapter $adapter)
    {
        parent::__construct(
            $adapter,
            'famille',
            'id_famille'
        );
    }

    /**
     * Liste toutes les familles.
     */
    public function getAll(): array
    {
        return $this->adapter
            ->query(
                'SELECT *
                 FROM famille
                 ORDER BY nom_famille',
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();
    }

    /**
     * Récupérer une famille.
     */
    public function getById(int $id): ?array
    {
        $rows = $this->adapter
            ->query(
                'SELECT *
                 FROM famille
                 WHERE id_famille = ?',
                [$id]
            )
            ->toArray();

        return $rows[0] ?? null;
    }
}