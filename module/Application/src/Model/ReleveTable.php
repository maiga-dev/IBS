<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class ReleveTable
{
    public function __construct(
        private Adapter $adapter
    ) {
    }

    /**
     * Tous les relevés d'un équipement.
     */
    public function findByEquipement(int $idEquipement): array
    {
        $sql = new Sql($this->adapter);

        $select = $sql->select('releve');

        $select->columns([
            'id_releve',
            'id_equipement',
            'date_releve',
            'heure_total',
            'heure_production',
            'pression',
            'purete',
            'observation',
        ]);

        $select->where([
            'id_equipement' => $idEquipement,
        ]);

        $select->order('date_releve DESC');

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        return iterator_to_array($result);
    }

    /**
     * Récupérer un relevé.
     */
    public function find(int $id): ?array
    {
        $sql = new Sql($this->adapter);

        $select = $sql->select('releve');

        $select->columns([
            'id_releve',
            'id_equipement',
            'date_releve',
            'heure_total',
            'heure_production',
            'pression',
            'purete',
            'observation',
        ]);

        $select->where([
            'id_releve' => $id,
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        $row = $result->current();

        return $row ?: null;
    }

    /**
     * Ajouter un relevé.
     */
    public function insert(array $data): int
    {
        $sql = new Sql($this->adapter);

        $insert = $sql->insert('releve');

        $insert->values([
            'id_equipement'    => $data['id_equipement'],
            'date_releve'      => $data['date_releve'],
            'heure_total'      => $data['heure_total'],
            'heure_production' => $data['heure_production'],
            'pression'         => $data['pression'],
            'purete'           => $data['purete'],
            'observation'      => $data['observation'],
        ]);

        $statement = $sql->prepareStatementForSqlObject($insert);

        $statement->execute();

        return (int) $this->adapter
            ->getDriver()
            ->getLastGeneratedValue();
    }

    /**
     * Modifier un relevé.
     */
    public function update(int $id, array $data): void
    {
        $sql = new Sql($this->adapter);

        $update = $sql->update('releve');

        $update->set([
            'date_releve'      => $data['date_releve'],
            'heure_total'      => $data['heure_total'],
            'heure_production' => $data['heure_production'],
            'pression'         => $data['pression'],
            'purete'           => $data['purete'],
            'observation'      => $data['observation'],
        ]);

        $update->where([
            'id_releve' => $id,
        ]);

        $statement = $sql->prepareStatementForSqlObject($update);

        $statement->execute();
    }

    /**
     * Supprimer un relevé.
     */
    public function delete(int $id): void
    {
        $sql = new Sql($this->adapter);

        $delete = $sql->delete('releve');

        $delete->where([
            'id_releve' => $id,
        ]);

        $statement = $sql->prepareStatementForSqlObject($delete);

        $statement->execute();
    }
}