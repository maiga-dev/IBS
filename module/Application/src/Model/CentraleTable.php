<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;

class CentraleTable extends AbstractReferenceTable
{
    public function __construct(Adapter $adapter)
    {
        parent::__construct(
            $adapter,
            'centrale',
            'id_centrale'
        );
    }

    /**
     * Liste toutes les centrales.
     */
    public function getAll(): array
    {
        return $this->adapter->query(
            'SELECT
                id_centrale,
                type_centrale,
                id_famille
             FROM centrale
             ORDER BY id_centrale DESC',
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }

    /**
     * Liste détaillée des centrales.
     *
     * Une centrale n'est pas rattachée à un site.
     * Le site sera déterminé au niveau de l'équipement.
     */
    public function findAllDetaille(): array
    {
        $sql = "
            SELECT
                c.id_centrale,
                c.type_centrale,
                c.id_famille,
                f.nom_famille
            FROM centrale c

            LEFT JOIN famille f
                ON f.id_famille = c.id_famille

            ORDER BY c.id_centrale DESC
        ";

        return $this->adapter
            ->query(
                $sql,
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();
    }
}