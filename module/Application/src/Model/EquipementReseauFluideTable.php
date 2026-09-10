<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;

class EquipementReseauFluideTable extends AbstractReferenceTable
{
    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter, 'equipement_reseau_fluide', 'id_equipement_reseau_fluide');
    }

    /** Liste enrichie : type, centrale (réseau), site — pour l'affichage. */
    public function findAllDetaille(): array
    {
        $sql = "
            SELECT
                er.*,
                te.nom_type,
                c.type_centrale,
                s.nom_site
            FROM equipement_reseau_fluide er
            LEFT JOIN type_equipement te ON te.id_type_equipement = er.id_type_equipement
            LEFT JOIN centrale c ON c.id_centrale = er.id_centrale
            LEFT JOIN site s ON s.id_site = c.id_site
            ORDER BY er.id_equipement_reseau_fluide DESC
        ";

        return $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE)->toArray();
    }
}