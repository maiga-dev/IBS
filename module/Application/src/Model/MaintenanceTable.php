<?php

declare(strict_types=1);

namespace Application\Model;

use InvalidArgumentException;
use Laminas\Db\Adapter\Adapter;

class MaintenanceTable
{
    use RowHydrationTrait;

    public const STATUTS = [
        'planifiee',
        'en_cours',
        'suspendue',
        'arretee',
        'terminee',
    ];

    public function __construct(
        private Adapter $adapter
    ) {
    }

    // ------------------------------------------------------------------
    // SESSIONS
    // ------------------------------------------------------------------

    /**
     * Liste des sessions, avec centrale/site/technicien
     * et un résumé d'avancement.
     */
    public function fetchSessions(): array
    {
        $sql = "
            SELECT
                ses.*,
                c.type_centrale,
                s.nom_site,
                u.nom AS technicien_nom,
                u.prenom AS technicien_prenom,

                (
                    SELECT COUNT(*)
                    FROM maintenance m
                    WHERE m.id_session = ses.id_session
                ) AS nb_equipements,

                (
                    SELECT COUNT(*)
                    FROM maintenance m
                    WHERE m.id_session = ses.id_session
                    AND m.statut = 'terminee'
                ) AS nb_terminees

            FROM session_maintenance ses

            INNER JOIN centrale c
                ON c.id_centrale = ses.id_centrale

            LEFT JOIN site s
                ON s.id_site = c.id_site

            LEFT JOIN technicien t
                ON t.id_utilisateur = ses.id_technicien

            LEFT JOIN utilisateurs u
                ON u.id = t.id_utilisateur

            ORDER BY
                ses.date_session DESC,
                ses.id_session DESC
        ";

        return $this->adapter
            ->query(
                $sql,
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();
    }

    /**
     * Liste des centrales pour le formulaire
     * de création de session.
     */
    public function getCentrales(): array
    {
        $sql = "
            SELECT
                c.*,
                s.nom_site

            FROM centrale c

            LEFT JOIN site s
                ON s.id_site = c.id_site

            ORDER BY
                c.type_centrale,
                c.id_centrale
        ";

        return $this->adapter
            ->query(
                $sql,
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();
    }

    /**
     * Crée une session pour une centrale et génère
     * automatiquement une maintenance pour chaque équipement.
     */
    public function creerSession(
        int $idCentrale,
        string $dateSession
    ): int {
        $row = $this->adapter
            ->query(
                "INSERT INTO session_maintenance
                    (
                        id_centrale,
                        date_session,
                        statut
                    )
                 VALUES
                    (?, ?, 'planifiee')
                 RETURNING id_session",
                [
                    $idCentrale,
                    $dateSession,
                ]
            )
            ->current();

        $idSession = (int) $row['id_session'];

        $equipements = $this->adapter
            ->query(
                'SELECT id_equipement
                 FROM equipement
                 WHERE id_centrale = ?',
                [
                    $idCentrale,
                ]
            )
            ->toArray();

        foreach ($equipements as $equipement) {

            $this->adapter->query(
                "INSERT INTO maintenance
                    (
                        id_equipement,
                        id_centrale,
                        id_session,
                        date_maintenance,
                        statut
                    )
                 VALUES
                    (?, ?, ?, ?, 'planifiee')",
                [
                    $equipement['id_equipement'],
                    $idCentrale,
                    $idSession,
                    $dateSession,
                ]
            );
        }

        return $idSession;
    }

    /**
     * Une session avec son contexte.
     */
    public function findSession(int $id): ?array
    {
        $sql = "
            SELECT
                ses.*,
                c.type_centrale,
                c.id_site,
                s.nom_site,
                u.nom AS technicien_nom,
                u.prenom AS technicien_prenom

            FROM session_maintenance ses

            INNER JOIN centrale c
                ON c.id_centrale = ses.id_centrale

            LEFT JOIN site s
                ON s.id_site = c.id_site

            LEFT JOIN technicien t
                ON t.id_utilisateur = ses.id_technicien

            LEFT JOIN utilisateurs u
                ON u.id = t.id_utilisateur

            WHERE ses.id_session = ?
        ";

        $row = $this->adapter
            ->query(
                $sql,
                [$id]
            )
            ->current();

        return $this->normaliserLigne($row);
    }

    /**
     * Supprime une session.
     */
    public function supprimerSession(
        int $idSession
    ): void {
        $this->adapter->query(
            'DELETE FROM session_maintenance
             WHERE id_session = ?',
            [
                $idSession,
            ]
        );
    }

    // ------------------------------------------------------------------
    // ÉQUIPEMENTS D'UNE SESSION
    // ------------------------------------------------------------------

    /**
     * Les équipements couverts par une session.
     */
    public function getEquipementsDeSession(
        int $idSession
    ): array {
        $sql = "
            SELECT
                m.*,

                te.nom_type AS type_equipement,

                e.marque,
                e.modele,
                e.numero_serie

            FROM maintenance m

            INNER JOIN equipement e
                ON e.id_equipement = m.id_equipement

            LEFT JOIN type_equipement te
                ON te.id_type_equipement = e.id_type_equipement

            LEFT JOIN technicien t
                ON t.id_utilisateur = m.id_technicien

            LEFT JOIN utilisateurs u
                ON u.id = t.id_utilisateur

            WHERE m.id_session = ?

            ORDER BY
                m.id_maintenance
        ";

        $lignes = $this->adapter
            ->query(
                $sql,
                [$idSession]
            )
            ->toArray();

        foreach ($lignes as &$ligne) {

            $ligne['pieces_jointes'] =
                $this->findPiecesJointes(
                    (int) $ligne['id_maintenance']
                );
        }

        unset($ligne);

        return $lignes;
    }

    /**
     * Liste des techniciens.
     */
    public function getTechniciens(): array
    {
        return $this->adapter
            ->query(
                "SELECT *
                 FROM utilisateurs
                 WHERE role = 'technicien'
                 ORDER BY nom",
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();
    }

    /**
     * Affecte un technicien à une maintenance.
     */
    public function affecterTechnicien(
        int $idMaintenance,
        ?int $idTechnicien
    ): void {
        $this->adapter->query(
            'UPDATE maintenance
             SET id_technicien = ?
             WHERE id_maintenance = ?',
            [
                $idTechnicien,
                $idMaintenance,
            ]
        );
    }

    /**
     * Met à jour une ligne de maintenance.
     */
    public function updateItem(
        int $idMaintenance,
        string $statut,
        ?string $dateFin,
        ?string $diagnostic,
        ?string $compteRendu
    ): void {
        if (!in_array(
            $statut,
            self::STATUTS,
            true
        )) {
            throw new InvalidArgumentException(
                'Statut invalide.'
            );
        }

        $sql = "
            UPDATE maintenance

            SET
                statut = ?,
                date_fin = ?,
                diagnostic =
                    COALESCE(?, diagnostic),
                compte_rendu =
                    COALESCE(?, compte_rendu)

            WHERE id_maintenance = ?
        ";

        $this->adapter->query(
            $sql,
            [
                $statut,
                $dateFin,
                $diagnostic,
                $compteRendu,
                $idMaintenance,
            ]
        );
    }

    // ------------------------------------------------------------------
    // HISTORIQUE D'UN ÉQUIPEMENT
    // ------------------------------------------------------------------

    /**
     * Retourne toutes les maintenances d'un équipement.
     *
     * Cette méthode est utilisée par :
     * EquipementController::historiqueAction()
     */
    public function getMaintenancesByEquipement(
        int $idEquipement
    ): array {
        $sql = "
            SELECT
                m.*,

                c.type_centrale,

                s.nom_site,

                u.nom AS technicien_nom,
                u.prenom AS technicien_prenom

            FROM maintenance m

            LEFT JOIN centrale c
                ON c.id_centrale = m.id_centrale

            LEFT JOIN site s
                ON s.id_site = c.id_site

            LEFT JOIN technicien t
                ON t.id_utilisateur = m.id_technicien

            LEFT JOIN utilisateurs u
                ON u.id = t.id_utilisateur

            WHERE m.id_equipement = ?

            ORDER BY
                m.date_maintenance DESC,
                m.id_maintenance DESC
        ";

        return $this->adapter
            ->query(
                $sql,
                [$idEquipement]
            )
            ->toArray();
    }

    // ------------------------------------------------------------------
    // PIÈCES JOINTES
    // ------------------------------------------------------------------

    /**
     * Récupère les pièces jointes d'une intervention.
     */
    public function findPiecesJointes(
        int $idMaintenance
    ): array {
        return $this->adapter
            ->query(
                'SELECT *
                 FROM piece_jointe_intervention
                 WHERE id_maintenance = ?
                 ORDER BY date_ajout DESC',
                [
                    $idMaintenance,
                ]
            )
            ->toArray();
    }

    /**
     * Ajoute une pièce jointe.
     */
    public function ajouterPieceJointe(
        int $idMaintenance,
        string $urlFichier,
        string $typeFichier,
        ?string $legende = null
    ): void {
        $this->adapter->query(
            "INSERT INTO piece_jointe_intervention
                (
                    id_maintenance,
                    url_fichier,
                    type_fichier,
                    legende
                )
             VALUES
                (?, ?, ?, ?)",
            [
                $idMaintenance,
                $urlFichier,
                $typeFichier,
                $legende,
            ]
        );
    }
}
