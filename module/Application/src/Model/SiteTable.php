<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;

class SiteTable extends AbstractReferenceTable
{
    public function __construct(Adapter $adapter)
    {
        parent::__construct(
            $adapter,
            'site',
            'id_site'
        );
    }

    /**
     * Liste tous les sites.
     */
    public function getAll(): array
    {
        return $this->adapter->query(
            'SELECT *
             FROM site
             ORDER BY nom_site ASC',
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }

    /**
     * Liste les sites d'une ville.
     */
    public function findByVille(int $idVille): array
    {
        return $this->adapter->query(
            'SELECT *
             FROM site
             WHERE id_ville = ?
             ORDER BY nom_site ASC',
            [$idVille]
        )->toArray();
    }

    /**
     * Liste enrichie avec le nom de la ville.
     */
    public function findAllDetaille(): array
    {
        $sql = "
            SELECT
                s.*,
                v.nom_ville
            FROM site s
            LEFT JOIN ville v
                ON v.id_ville = s.id_ville
            ORDER BY s.nom_site ASC
        ";

        return $this->adapter
            ->query(
                $sql,
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();
    }
}
