<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;

class PaysTable extends AbstractReferenceTable
{
    public function __construct(Adapter $adapter)
    {
        parent::__construct(
            $adapter,
            'pays',
            'id_pays'
        );
    }

    /**
     * Liste tous les pays.
     */
    public function getAll(): array
    {
        return $this->adapter->query(
            'SELECT *
             FROM pays
             ORDER BY nom_pays ASC',
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }
}
