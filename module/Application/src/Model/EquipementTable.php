<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class EquipementTable
{
    private Adapter $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    /**
     * Liste complète des équipements.
     */
    public function getAll(): array
    {
        $sql = new Sql($this->db);

        $select = $sql->select(['e' => 'equipement']);

        $select->columns([
            'id_equipement',
            'id_type_equipement',
            'designation',
            'numero_serie',
            'marque',
            'modele',
            'date_mise_en_service',
            'periodicite',
            'date_prochaine_maintenance',
            'id_site',
            'id_centrale',
        ]);

        $select->join(
            ['t' => 'type_equipement'],
            't.id_type_equipement = e.id_type_equipement',
            [
                'nom_type',
            ],
            $select::JOIN_LEFT
        );

        $select->join(
            ['s' => 'site'],
            's.id_site = e.id_site',
            [
                'nom_site',
            ],
            $select::JOIN_LEFT
        );

        $select->join(
            ['c' => 'centrale'],
            'c.id_centrale = e.id_centrale',
            [
                'type_centrale',
            ],
            $select::JOIN_LEFT
        );

        $select->order('e.id_equipement DESC');

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        $equipements = [];

        foreach ($result as $row) {
            $equipements[] = (array) $row;
        }

        return $equipements;
    }

    /**
     * Récupérer un équipement par son ID.
     */
    public function getById(int $id): ?array
    {
        $sql = new Sql($this->db);

        $select = $sql->select(['e' => 'equipement']);

        $select->columns([
            'id_equipement',
            'id_type_equipement',
            'designation',
            'numero_serie',
            'marque',
            'modele',
            'date_mise_en_service',
            'periodicite',
            'date_prochaine_maintenance',
            'id_site',
            'id_centrale',
        ]);

        $select->join(
            ['t' => 'type_equipement'],
            't.id_type_equipement = e.id_type_equipement',
            [
                'nom_type',
            ],
            $select::JOIN_LEFT
        );

        $select->join(
            ['s' => 'site'],
            's.id_site = e.id_site',
            [
                'nom_site',
                'adresse',
            ],
            $select::JOIN_LEFT
        );

        $select->join(
            ['c' => 'centrale'],
            'c.id_centrale = e.id_centrale',
            [
                'type_centrale',
            ],
            $select::JOIN_LEFT
        );

        $select->where([
            'e.id_equipement' => $id,
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        $row = $result->current();

        if (!$row) {
            return null;
        }

        return (array) $row;
    }

    /**
     * Ajouter un équipement.
     */
    public function insert(
        int $idTypeEquipement,
        ?string $designation,
        ?string $numeroSerie,
        ?string $marque,
        ?string $modele,
        ?string $dateMiseEnService,
        ?string $periodicite,
        ?string $dateProchaineMaintenance,
        ?int $idSite,
        ?int $idCentrale
    ): void {
        $sql = new Sql($this->db);

        $insert = $sql->insert('equipement');

        $insert->values([
            'id_type_equipement' => $idTypeEquipement,
            'designation' => $designation,
            'numero_serie' => $numeroSerie,
            'marque' => $marque,
            'modele' => $modele,
            'date_mise_en_service' => $dateMiseEnService,
            'periodicite' => $periodicite,
            'date_prochaine_maintenance' => $dateProchaineMaintenance,
            'id_site' => $idSite,
            'id_centrale' => $idCentrale,
        ]);

        $statement = $sql->prepareStatementForSqlObject($insert);

        $statement->execute();
    }

    /**
     * Modifier un équipement.
     */
    public function update(
        int $id,
        int $idTypeEquipement,
        ?string $designation,
        ?string $numeroSerie,
        ?string $marque,
        ?string $modele,
        ?string $dateMiseEnService,
        ?string $periodicite,
        ?string $dateProchaineMaintenance,
        ?int $idSite,
        ?int $idCentrale
    ): void {
        $sql = new Sql($this->db);

        $update = $sql->update('equipement');

        $update->set([
            'id_type_equipement' => $idTypeEquipement,
            'designation' => $designation,
            'numero_serie' => $numeroSerie,
            'marque' => $marque,
            'modele' => $modele,
            'date_mise_en_service' => $dateMiseEnService,
            'periodicite' => $periodicite,
            'date_prochaine_maintenance' => $dateProchaineMaintenance,
            'id_site' => $idSite,
            'id_centrale' => $idCentrale,
        ]);

        $update->where([
            'id_equipement' => $id,
        ]);

        $statement = $sql->prepareStatementForSqlObject($update);

        $statement->execute();
    }

    /**
     * Supprimer un équipement.
     */
    public function delete(int $id): void
    {
        $sql = new Sql($this->db);

        $delete = $sql->delete('equipement');

        $delete->where([
            'id_equipement' => $id,
        ]);

        $statement = $sql->prepareStatementForSqlObject($delete);

        $statement->execute();
    }
}
