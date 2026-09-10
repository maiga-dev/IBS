<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;

class TypeEquipementTable extends AbstractReferenceTable
{
    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter, 'type_equipement', 'id_type_equipement');
    }
}
