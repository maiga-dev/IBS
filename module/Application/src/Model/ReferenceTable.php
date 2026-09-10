<?php

declare(strict_types=1);

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class ReferenceTable
{
    public function __construct(
        private Adapter $db
    ) {
    }

    // =========================================================
    // PAYS
    // =========================================================

    public function getPays(): array
    {
        $sql = new Sql($this->db);

        $select = $sql->select('pays');
        $select->order('nom_pays ASC');

        $result = $sql
            ->prepareStatementForSqlObject($select)
            ->execute();

        return iterator_to_array($result, false);
    }

    public function getPaysById(int $id): ?array
    {
        $result = $this->db->query(
            'SELECT * FROM pays WHERE id_pays = ?',
            [$id]
        )->current();

        return $result ? (array) $result : null;
    }

    public function ajouterPays(string $nom): void
    {
        $this->db->query(
            'INSERT INTO pays (nom_pays) VALUES (?)',
            [$nom]
        );
    }

    public function modifierPays(int $id, string $nom): void
    {
        $this->db->query(
            'UPDATE pays SET nom_pays = ? WHERE id_pays = ?',
            [$nom, $id]
        );
    }

    public function supprimerPays(int $id): void
    {
        $this->db->query(
            'DELETE FROM pays WHERE id_pays = ?',
            [$id]
        );
    }

    // =========================================================
    // VILLES
    // =========================================================

    public function getVilles(): array
    {
        $sql = new Sql($this->db);

        $select = $sql->select(['v' => 'ville']);

        $select->columns([
            'id_ville',
            'nom_ville',
            'id_pays',
        ]);

        $select->join(
            ['p' => 'pays'],
            'p.id_pays = v.id_pays',
            [
                'nom_pays',
            ],
            $select::JOIN_LEFT
        );

        $select->order('v.nom_ville ASC');

        $result = $sql
            ->prepareStatementForSqlObject($select)
            ->execute();

        return iterator_to_array($result, false);
    }

    public function getVilleById(int $id): ?array
    {
        $result = $this->db->query(
            'SELECT * FROM ville WHERE id_ville = ?',
            [$id]
        )->current();

        return $result ? (array) $result : null;
    }

    public function ajouterVille(string $nom, int $idPays): void
    {
        $this->db->query(
            'INSERT INTO ville (nom_ville, id_pays) VALUES (?, ?)',
            [$nom, $idPays]
        );
    }

    public function modifierVille(int $id, string $nom, int $idPays): void
    {
        $this->db->query(
            'UPDATE ville SET nom_ville = ?, id_pays = ? WHERE id_ville = ?',
            [$nom, $idPays, $id]
        );
    }

    public function supprimerVille(int $id): void
    {
        $this->db->query(
            'DELETE FROM ville WHERE id_ville = ?',
            [$id]
        );
    }

    // =========================================================
    // SITES
    // =========================================================

    public function getSites(): array
    {
        $sql = new Sql($this->db);

        $select = $sql->select(['s' => 'site']);

        $select->columns([
            'id_site',
            'nom_site',
            'adresse',
            'latitude',
            'longitude',
            'id_ville',
        ]);

        $select->join(
            ['v' => 'ville'],
            'v.id_ville = s.id_ville',
            [
                'nom_ville',
            ],
            $select::JOIN_LEFT
        );

        $select->join(
            ['p' => 'pays'],
            'p.id_pays = v.id_pays',
            [
                'nom_pays',
            ],
            $select::JOIN_LEFT
        );

        $select->order('s.nom_site ASC');

        $result = $sql
            ->prepareStatementForSqlObject($select)
            ->execute();

        return iterator_to_array($result, false);
    }

    public function getSiteById(int $id): ?array
    {
        $result = $this->db->query(
            'SELECT * FROM site WHERE id_site = ?',
            [$id]
        )->current();

        return $result ? (array) $result : null;
    }

    public function ajouterSite(
        string $nom,
        ?string $adresse,
        ?float $latitude,
        ?float $longitude,
        int $idVille
    ): void {
        $this->db->query(
            'INSERT INTO site
            (nom_site, adresse, latitude, longitude, id_ville)
            VALUES (?, ?, ?, ?, ?)',
            [
                $nom,
                $adresse,
                $latitude,
                $longitude,
                $idVille,
            ]
        );
    }

    public function modifierSite(
        int $id,
        string $nom,
        ?string $adresse,
        ?float $latitude,
        ?float $longitude,
        int $idVille
    ): void {
        $this->db->query(
            'UPDATE site
             SET nom_site = ?,
                 adresse = ?,
                 latitude = ?,
                 longitude = ?,
                 id_ville = ?
             WHERE id_site = ?',
            [
                $nom,
                $adresse,
                $latitude,
                $longitude,
                $idVille,
                $id,
            ]
        );
    }

    public function supprimerSite(int $id): void
    {
        $this->db->query(
            'DELETE FROM site WHERE id_site = ?',
            [$id]
        );
    }

    // =========================================================
    // FAMILLES
    // =========================================================

    public function getFamilles(): array
    {
        $sql = new Sql($this->db);

        $select = $sql->select('famille');
        $select->order('nom_famille ASC');

        $result = $sql
            ->prepareStatementForSqlObject($select)
            ->execute();

        return iterator_to_array($result, false);
    }

    public function getFamilleById(int $id): ?array
    {
        $result = $this->db->query(
            'SELECT * FROM famille WHERE id_famille = ?',
            [$id]
        )->current();

        return $result ? (array) $result : null;
    }

    public function ajouterFamille(string $nom): void
    {
        $this->db->query(
            'INSERT INTO famille (nom_famille) VALUES (?)',
            [$nom]
        );
    }

    public function modifierFamille(int $id, string $nom): void
    {
        $this->db->query(
            'UPDATE famille SET nom_famille = ? WHERE id_famille = ?',
            [$nom, $id]
        );
    }

    public function supprimerFamille(int $id): void
    {
        $this->db->query(
            'DELETE FROM famille WHERE id_famille = ?',
            [$id]
        );
    }
}