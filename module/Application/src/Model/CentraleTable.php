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
            'SELECT *
             FROM centrale
             ORDER BY id_centrale DESC',
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }

    /**
     * Liste les centrales d'un site.
     */
    public function findBySite(int $idSite): array
    {
        return $this->adapter->query(
            'SELECT *
             FROM centrale
             WHERE id_site = ?
             ORDER BY id_centrale DESC',
            [$idSite]
        )->toArray();
    }

    /**
     * Liste enrichie.
     */
    public function findAllDetaille(): array
    {
        $sql = "
            SELECT
                c.*,
                s.nom_site,
                f.nom_famille
            FROM centrale c
            LEFT JOIN site s
                ON s.id_site = c.id_site
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
