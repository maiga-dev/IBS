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
        'affectee',
        'en_cours',
        'suspendue',
        'arretee',
        'terminee',
    ];

    public function __construct(
        private Adapter $adapter
    ) {
    }

   
    public function fetchSessions(): array
    {
        $sql = "
            SELECT
                ses.*,
                c.type_centrale,

                
                site_session.nom_site,

                site_session.id_site,
                site_session.id_ville,
                site_session.nom_ville,
                site_session.id_pays,
                site_session.nom_pays,

                (
                    SELECT u.nom
                    FROM maintenance m
                    INNER JOIN technicien t
                        ON t.id_utilisateur = m.id_technicien
                    INNER JOIN utilisateurs u
                        ON u.id = t.id_utilisateur
                    WHERE m.id_session = ses.id_session
                      AND m.id_technicien IS NOT NULL
                    ORDER BY m.id_maintenance ASC
                    LIMIT 1
                ) AS technicien_nom,

                (
                    SELECT u.prenom
                    FROM maintenance m
                    INNER JOIN technicien t
                        ON t.id_utilisateur = m.id_technicien
                    INNER JOIN utilisateurs u
                        ON u.id = t.id_utilisateur
                    WHERE m.id_session = ses.id_session
                      AND m.id_technicien IS NOT NULL
                    ORDER BY m.id_maintenance ASC
                    LIMIT 1
                ) AS technicien_prenom,

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

            LEFT JOIN LATERAL (
                SELECT
                    s.id_site,
                    s.nom_site,
                    s.id_ville,
                    v.nom_ville,
                    v.id_pays,
                    p.nom_pays
                FROM equipement e
                INNER JOIN site s
                    ON s.id_site = e.id_site
                LEFT JOIN ville v
                    ON v.id_ville = s.id_ville
                LEFT JOIN pays p
                    ON p.id_pays = v.id_pays
                WHERE e.id_centrale = ses.id_centrale
                ORDER BY e.id_equipement ASC
                LIMIT 1
            ) site_session ON TRUE

            ORDER BY
                ses.date_session DESC,
                ses.id_session DESC
        ";

        return $this->adapter->query(
            $sql,
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }

    /**
     * Liste les sessions auxquelles un technicien est affecté.
     */
    public function fetchSessionsPourTechnicien(
        int $idTechnicien
    ): array {
        if ($idTechnicien <= 0) {
            return [];
        }

        $sql = "
            SELECT
                ses.*,
                c.type_centrale,

                site_session.nom_site,
                site_session.id_site,
                site_session.id_ville,
                site_session.nom_ville,
                site_session.id_pays,
                site_session.nom_pays,

                (
                    SELECT u.nom
                    FROM maintenance m
                    INNER JOIN technicien t
                        ON t.id_utilisateur = m.id_technicien
                    INNER JOIN utilisateurs u
                        ON u.id = t.id_utilisateur
                    WHERE m.id_session = ses.id_session
                      AND m.id_technicien = ?
                    ORDER BY m.id_maintenance ASC
                    LIMIT 1
                ) AS technicien_nom,

                (
                    SELECT u.prenom
                    FROM maintenance m
                    INNER JOIN technicien t
                        ON t.id_utilisateur = m.id_technicien
                    INNER JOIN utilisateurs u
                        ON u.id = t.id_utilisateur
                    WHERE m.id_session = ses.id_session
                      AND m.id_technicien = ?
                    ORDER BY m.id_maintenance ASC
                    LIMIT 1
                ) AS technicien_prenom,

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

            LEFT JOIN LATERAL (
                SELECT
                    s.id_site,
                    s.nom_site,
                    s.id_ville,
                    v.nom_ville,
                    v.id_pays,
                    p.nom_pays
                FROM equipement e
                INNER JOIN site s
                    ON s.id_site = e.id_site
                LEFT JOIN ville v
                    ON v.id_ville = s.id_ville
                LEFT JOIN pays p
                    ON p.id_pays = v.id_pays
                WHERE e.id_centrale = ses.id_centrale
                ORDER BY e.id_equipement ASC
                LIMIT 1
            ) site_session ON TRUE

            WHERE EXISTS (
                SELECT 1
                FROM maintenance mx
                WHERE mx.id_session = ses.id_session
                  AND mx.id_technicien = ?
            )

            ORDER BY
                ses.date_session DESC,
                ses.id_session DESC
        ";

        return $this->adapter->query(
            $sql,
            [
                $idTechnicien,
                $idTechnicien,
                $idTechnicien,
            ]
        )->toArray();
    }

   
    public function findSession(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = "
            SELECT
                ses.*,

                c.type_centrale,

                site_session.id_site,
                site_session.nom_site,
                site_session.adresse,
                site_session.telephone,
                site_session.latitude,
                site_session.longitude,

                site_session.id_ville,
                site_session.nom_ville,

                site_session.id_pays,
                site_session.nom_pays,

                fm.type_maintenance,
                fm.diagnostic AS diagnostic_initial,
                fm.compte_rendu AS compte_rendu_initial,
                fm.commentaire AS commentaire_initial,
                fm.justification,
                fm.id_technicien,

                t.id_utilisateur AS id_technicien,
                tech.nom AS technicien_nom,
                tech.prenom AS technicien_prenom,

                /*
                 * Date de fin de la session.
                 */
                (
                    SELECT MAX(m.date_fin)
                    FROM maintenance m
                    WHERE m.id_session = ses.id_session
                ) AS date_fin,

                /*
                 * Diagnostic final.
                 */
                (
                    SELECT m.diagnostic_final
                    FROM maintenance m
                    WHERE m.id_session = ses.id_session
                      AND m.diagnostic_final IS NOT NULL
                      AND TRIM(m.diagnostic_final) <> ''
                    ORDER BY m.id_maintenance ASC
                    LIMIT 1
                ) AS diagnostic_final,

                /*
                 * Compte rendu final.
                 */
                (
                    SELECT m.compte_rendu
                    FROM maintenance m
                    WHERE m.id_session = ses.id_session
                      AND m.compte_rendu IS NOT NULL
                      AND TRIM(m.compte_rendu) <> ''
                    ORDER BY m.id_maintenance ASC
                    LIMIT 1
                ) AS compte_rendu,

                /*
                 * Commentaire final.
                 */
                (
                    SELECT m.commentaire
                    FROM maintenance m
                    WHERE m.id_session = ses.id_session
                      AND m.commentaire IS NOT NULL
                      AND TRIM(m.commentaire) <> ''
                    ORDER BY m.id_maintenance ASC
                    LIMIT 1
                ) AS commentaire,

                (
                    SELECT COUNT(*)
                    FROM maintenance m
                    WHERE m.id_session = ses.id_session
                ) AS nb_equipements

            FROM session_maintenance ses

            INNER JOIN centrale c
                ON c.id_centrale = ses.id_centrale

            
            LEFT JOIN LATERAL (
                SELECT
                    s.id_site,
                    s.nom_site,
                    s.adresse,
                    s.telephone,
                    s.latitude,
                    s.longitude,

                    v.id_ville,
                    v.nom_ville,

                    p.id_pays,
                    p.nom_pays

                FROM equipement e

                INNER JOIN site s
                    ON s.id_site = e.id_site

                LEFT JOIN ville v
                    ON v.id_ville = s.id_ville

                LEFT JOIN pays p
                    ON p.id_pays = v.id_pays

                WHERE e.id_centrale = ses.id_centrale

                ORDER BY e.id_equipement ASC
                LIMIT 1
            ) site_session ON TRUE

            /*
             * Première maintenance de la session.
             */
            LEFT JOIN LATERAL (
                SELECT
                    m.type_maintenance,
                    m.diagnostic,
                    m.compte_rendu,
                    m.commentaire,
                    m.justification,
                    m.id_technicien

                FROM maintenance m

                WHERE m.id_session = ses.id_session

                ORDER BY m.id_maintenance ASC
                LIMIT 1
            ) fm ON TRUE

            LEFT JOIN technicien t
                ON t.id_utilisateur = fm.id_technicien

            LEFT JOIN utilisateurs tech
                ON tech.id = t.id_utilisateur

            WHERE ses.id_session = ?
        ";

        $row = $this->adapter->query(
            $sql,
            [$id]
        )->current();

        if (!$row) {
            return null;
        }

        return $this->normaliserLigne($row);
    }

    
    /**
     * Retourne tous les pays.
     */
    public function getPays(): array
    {
        $sql = "
            SELECT
                id_pays,
                nom_pays
            FROM pays
            ORDER BY nom_pays ASC
        ";

        return $this->adapter->query(
            $sql,
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }


    /**
     * Retourne toutes les villes avec leur pays.
     */
    public function getVilles(): array
    {
        $sql = "
            SELECT
                v.id_ville,
                v.nom_ville,
                v.id_pays,
                p.nom_pays
            FROM ville v
            INNER JOIN pays p
                ON p.id_pays = v.id_pays
            ORDER BY
                p.nom_pays ASC,
                v.nom_ville ASC
        ";

        return $this->adapter->query(
            $sql,
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }

    /**
     * Retourne tous les sites avec leur ville et leur pays.
     */
    public function getSites(): array
    {
        $sql = "
            SELECT
                s.id_site,
                s.nom_site,
                s.id_ville,
                v.nom_ville,
                v.id_pays,
                p.nom_pays
            FROM site s
            INNER JOIN ville v
                ON v.id_ville = s.id_ville
            INNER JOIN pays p
                ON p.id_pays = v.id_pays
            ORDER BY
                p.nom_pays ASC,
                v.nom_ville ASC,
                s.nom_site ASC
        ";

        return $this->adapter->query(
            $sql,
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }

    /**
     * Retourne toutes les centrales.
     *
     * IMPORTANT :
     * une centrale n'appartient plus directement à un site.
     *
     * Le site n'est donc volontairement PAS sélectionné ici.
     */
    public function getCentrales(): array
    {
        $sql = "
            SELECT
                c.id_centrale,
                c.type_centrale,
                c.id_famille
            FROM centrale c
            ORDER BY
                c.type_centrale ASC NULLS LAST,
                c.id_centrale ASC
        ";

        return $this->adapter->query(
            $sql,
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }

    /**
     * Retourne les centrales utilisées sur un site.
     *
     * Nouvelle relation :
     *
     * site → equipement → centrale
     */
    public function getCentralesBySite(int $idSite): array
    {
        if ($idSite <= 0) {
            return [];
        }

        $sql = "
            SELECT DISTINCT
                c.id_centrale,
                c.type_centrale,
                c.id_famille
            FROM centrale c
            INNER JOIN equipement e
                ON e.id_centrale = c.id_centrale
            WHERE e.id_site = ?
            ORDER BY
                c.type_centrale ASC
        ";

        return $this->adapter->query(
            $sql,
            [$idSite]
        )->toArray();
    }

    /**
     * Retourne les utilisateurs ayant le rôle technicien.
     */
    public function getTechniciens(): array
    {
        $sql = "
            SELECT
                u.id AS id_utilisateur,
                u.nom,
                u.prenom,
                u.email,
                u.role
            FROM utilisateurs u
            WHERE LOWER(TRIM(u.role)) = 'technicien'
            ORDER BY
                u.nom ASC,
                u.prenom ASC
        ";

        return $this->adapter->query(
            $sql,
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();
    }

    /**
     * Crée une session de maintenance pour une centrale.
     *
     * Tous les équipements possédant le même id_centrale
     * sont ajoutés à la session.
     */
    public function creerSession(
        int $idCentrale,
        string $dateSession,
        ?string $typeMaintenance,
        string $diagnosticInitial,
        ?string $compteRendu,
        ?string $commentaire
    ): int {
        if ($idCentrale <= 0) {
            throw new InvalidArgumentException(
                'La centrale est obligatoire.'
            );
        }

        if (trim($dateSession) === '') {
            throw new InvalidArgumentException(
                'La date est obligatoire.'
            );
        }

        if (trim($diagnosticInitial) === '') {
            throw new InvalidArgumentException(
                'Le premier diagnostic est obligatoire.'
            );
        }

        $centrale = $this->adapter->query(
            "
                SELECT
                    id_centrale
                FROM centrale
                WHERE id_centrale = ?
            ",
            [$idCentrale]
        )->current();

        if (!$centrale) {
            throw new InvalidArgumentException(
                'La centrale sélectionnée n’existe pas.'
            );
        }

        /*
         * Les équipements appartiennent à la centrale.
         * Le site est porté par l'équipement.
         */
        $equipements = $this->adapter->query(
            "
                SELECT
                    id_equipement
                FROM equipement
                WHERE id_centrale = ?
                ORDER BY id_equipement ASC
            ",
            [$idCentrale]
        )->toArray();

        if (!$equipements) {
            throw new InvalidArgumentException(
                'Aucun équipement n’est rattaché à cette centrale.'
            );
        }

        $row = $this->adapter->query(
            "
                INSERT INTO session_maintenance
                (
                    id_centrale,
                    date_session,
                    statut
                )
                VALUES (?, ?, 'planifiee')
                RETURNING id_session
            ",
            [
                $idCentrale,
                $dateSession,
            ]
        )->current();

        $idSession = (int) (
            $row['id_session'] ?? 0
        );

        if ($idSession <= 0) {
            throw new \RuntimeException(
                'Impossible de créer la session.'
            );
        }

        foreach ($equipements as $equipement) {
            $this->adapter->query(
                "
                    INSERT INTO maintenance
                    (
                        date_maintenance,
                        type_maintenance,
                        compte_rendu,
                        id_equipement,
                        statut,
                        id_session,
                        diagnostic,
                        commentaire
                    )
                    VALUES (?, ?, ?, ?, 'planifiee', ?, ?, ?)
                ",
                [
                    $dateSession,
                    $typeMaintenance,
                    $compteRendu,
                    (int) $equipement['id_equipement'],
                    $idSession,
                    $diagnosticInitial,
                    $commentaire,
                ]
            );
        }

        return $idSession;
    }

    /**
     * Modifie une session existante.
     */
    public function modifierSession(
        int $idSession,
        int $idCentrale,
        string $dateSession,
        ?string $typeMaintenance,
        string $diagnosticInitial,
        ?string $compteRendu,
        ?string $commentaire
    ): void {
        if ($idSession <= 0 || $idCentrale <= 0) {
            throw new InvalidArgumentException(
                'Session ou centrale invalide.'
            );
        }

        if (
            trim($dateSession) === ''
            || trim($diagnosticInitial) === ''
        ) {
            throw new InvalidArgumentException(
                'La date et le diagnostic initial sont obligatoires.'
            );
        }

        /*
         * Vérifie que la centrale existe.
         */
        $centrale = $this->adapter->query(
            "
                SELECT id_centrale
                FROM centrale
                WHERE id_centrale = ?
            ",
            [$idCentrale]
        )->current();

        if (!$centrale) {
            throw new InvalidArgumentException(
                'La centrale sélectionnée n’existe pas.'
            );
        }

        /*
         * Modification de la session.
         */
        $this->adapter->query(
            "
                UPDATE session_maintenance
                SET
                    id_centrale = ?
                WHERE id_session = ?
            ",
            [
                $idCentrale,
                $idSession,
            ]
        );

        /*
         * Les équipements de la nouvelle centrale
         * doivent être pris en compte si la centrale
         * de la session est changée.
         *
         * On récupère les équipements actuels.
         */
        $equipements = $this->adapter->query(
            "
                SELECT
                    id_equipement
                FROM equipement
                WHERE id_centrale = ?
                ORDER BY id_equipement ASC
            ",
            [$idCentrale]
        )->toArray();

        if (!$equipements) {
            throw new InvalidArgumentException(
                'Aucun équipement n’est rattaché à cette centrale.'
            );
        }

        /*
         * Mise à jour des maintenances existantes.
         */
        $maintenances = $this->adapter->query(
            "
                SELECT
                    id_maintenance,
                    id_equipement
                FROM maintenance
                WHERE id_session = ?
                ORDER BY id_maintenance ASC
            ",
            [$idSession]
        )->toArray();

        /*
         * Mise à jour des maintenances existantes.
         */
        foreach ($maintenances as $index => $maintenance) {
            if (!isset($equipements[$index])) {
                continue;
            }

            $this->adapter->query(
                "
                    UPDATE maintenance
                    SET
                        id_equipement = ?,
                        date_maintenance = ?,
                        type_maintenance = ?,
                        diagnostic = ?,
                        compte_rendu = ?,
                        commentaire = ?
                    WHERE id_maintenance = ?
                      AND statut = 'planifiee'
                ",
                [
                    (int) $equipements[$index]['id_equipement'],
                    $dateSession,
                    $typeMaintenance,
                    $diagnosticInitial,
                    $compteRendu,
                    $commentaire,
                    (int) $maintenance['id_maintenance'],
                ]
            );
        }

        /*
         * Si la nouvelle centrale possède davantage
         * d'équipements, on crée les maintenances manquantes.
         */
        $nombreMaintenances = count($maintenances);

        if (count($equipements) > $nombreMaintenances) {
            for (
                $i = $nombreMaintenances;
                $i < count($equipements);
                $i++
            ) {
                $this->adapter->query(
                    "
                        INSERT INTO maintenance
                        (
                            date_maintenance,
                            type_maintenance,
                            compte_rendu,
                            id_equipement,
                            statut,
                            id_session,
                            diagnostic,
                            commentaire
                        )
                        VALUES (?, ?, ?, ?, 'planifiee', ?, ?, ?)
                    ",
                    [
                        $dateSession,
                        $typeMaintenance,
                        $compteRendu,
                        (int) $equipements[$i]['id_equipement'],
                        $idSession,
                        $diagnosticInitial,
                        $commentaire,
                    ]
                );
            }
        }
    }

    /**
     * Ajoute une pièce jointe à toutes les maintenances
     * d'une session.
     */
    public function ajouterPieceJointeSession(
        int $idSession,
        string $url,
        string $type,
        ?string $legende = null,
        string $phase = 'initiale'
    ): void {
        if (
            $idSession <= 0
            || trim($url) === ''
        ) {
            throw new InvalidArgumentException(
                'Pièce jointe invalide.'
            );
        }

        $maintenances = $this->adapter->query(
            "
                SELECT
                    id_maintenance
                FROM maintenance
                WHERE id_session = ?
            ",
            [$idSession]
        )->toArray();

        foreach ($maintenances as $maintenance) {
            $this->ajouterPieceJointe(
                (int) $maintenance['id_maintenance'],
                $url,
                $type,
                $legende,
                $phase
            );
        }
    }

    /**
     * Supprime une session.
     */
    public function supprimerSession(int $idSession): void
    {
        if ($idSession <= 0) {
            throw new InvalidArgumentException(
                'Session invalide.'
            );
        }

        $this->adapter->query(
            "
                DELETE FROM session_maintenance
                WHERE id_session = ?
            ",
            [$idSession]
        );
    }

    /**
     * Retourne une maintenance individuelle.
     *
     * Le site est récupéré depuis l'équipement.
     */
    public function findMaintenance(
        int $idMaintenance
    ): ?array {
        if ($idMaintenance <= 0) {
            return null;
        }

        $sql = "
            SELECT
                m.*,

                e.id_site,
                e.id_centrale,

                e.marque,
                e.modele,
                e.numero_serie,

                te.nom_type AS type_equipement,

                c.type_centrale,

                s.nom_site,
                s.adresse AS site_adresse,
                s.telephone AS site_telephone,

                v.id_ville,
                v.nom_ville,

                p.id_pays,
                p.nom_pays

            FROM maintenance m

            LEFT JOIN equipement e
                ON e.id_equipement = m.id_equipement

            LEFT JOIN type_equipement te
                ON te.id_type_equipement = e.id_type_equipement

            LEFT JOIN centrale c
                ON c.id_centrale = e.id_centrale

            LEFT JOIN site s
                ON s.id_site = e.id_site

            LEFT JOIN ville v
                ON v.id_ville = s.id_ville

            LEFT JOIN pays p
                ON p.id_pays = v.id_pays

            WHERE m.id_maintenance = ?
        ";

        $row = $this->adapter->query(
            $sql,
            [$idMaintenance]
        )->current();

        return $row
            ? $this->normaliserLigne($row)
            : null;
    }

    /**
     * Affecte un technicien à une maintenance.
     */
    public function affecterTechnicien(
        int $idMaintenance,
        ?int $idTechnicien
    ): void {
        if ($idMaintenance <= 0) {
            throw new InvalidArgumentException(
                'Maintenance invalide.'
            );
        }

        if (
            $idTechnicien !== null
            && $idTechnicien <= 0
        ) {
            throw new InvalidArgumentException(
                'Technicien invalide.'
            );
        }

        $ancienne = $this->findMaintenance(
            $idMaintenance
        );

        if (!$ancienne) {
            throw new InvalidArgumentException(
                'Maintenance introuvable.'
            );
        }

        $ancienTechnicien = (int) (
            $ancienne['id_technicien'] ?? 0
        );

        if ($idTechnicien === null) {
            $this->adapter->query(
                "
                    UPDATE maintenance
                    SET
                        id_technicien = NULL,
                        statut = CASE
                            WHEN statut = 'affectee'
                            THEN 'planifiee'
                            ELSE statut
                        END
                    WHERE id_maintenance = ?
                ",
                [$idMaintenance]
            );
        } else {
            $this->adapter->query(
                "
                    UPDATE maintenance
                    SET
                        id_technicien = ?,
                        statut = CASE
                            WHEN statut = 'planifiee'
                            THEN 'affectee'
                            ELSE statut
                        END
                    WHERE id_maintenance = ?
                ",
                [
                    $idTechnicien,
                    $idMaintenance,
                ]
            );
        }

        /*
         * Notification lors d'une nouvelle affectation.
         */
        if (
            $idTechnicien !== null
            && $ancienTechnicien !== $idTechnicien
        ) {
            $this->adapter->query(
                "
                    INSERT INTO notification
                    (
                        type,
                        statut,
                        id_utilisateur,
                        id_equipement,
                        id_maintenance
                    )
                    SELECT
                        'maintenance_affectee',
                        'non lue',
                        ?,
                        id_equipement,
                        id_maintenance
                    FROM maintenance
                    WHERE id_maintenance = ?
                ",
                [
                    $idTechnicien,
                    $idMaintenance,
                ]
            );
        }

        $maintenance = $this->findMaintenance(
            $idMaintenance
        );

        if ($maintenance) {
            $this->synchroniserStatutSession(
                (int) $maintenance['id_session']
            );
        }
    }

    /**
     * Change le statut d'une maintenance.
     */
    public function changerStatut(
        int $idMaintenance,
        string $nouveauStatut,
        ?string $justification = null,
        ?string $diagnosticFinal = null,
        ?string $compteRendu = null,
        ?string $commentaire = null
    ): void {
        $maintenance = $this->findMaintenance(
            $idMaintenance
        );

        if (!$maintenance) {
            throw new InvalidArgumentException(
                'Maintenance introuvable.'
            );
        }

        if (
            !in_array(
                $nouveauStatut,
                self::STATUTS,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Statut invalide.'
            );
        }

        $ancienStatut = (string) (
            $maintenance['statut'] ?? ''
        );

        $transitions = [
            'planifiee' => ['affectee'],
            'affectee' => ['en_cours'],
            'en_cours' => [
                'suspendue',
                'arretee',
                'terminee',
            ],
            'suspendue' => [
                'en_cours',
                'arretee',
            ],
            'arretee' => [],
            'terminee' => [],
        ];

        if (
            $nouveauStatut !== $ancienStatut
            && !in_array(
                $nouveauStatut,
                $transitions[$ancienStatut] ?? [],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Transition de statut non autorisée.'
            );
        }

        if (
            in_array(
                $nouveauStatut,
                ['suspendue', 'arretee'],
                true
            )
            && trim((string) $justification) === ''
        ) {
            throw new InvalidArgumentException(
                'Une justification est obligatoire pour suspendre ou arrêter.'
            );
        }

        if ($nouveauStatut === 'terminee') {
            if (
                trim((string) $diagnosticFinal) === ''
            ) {
                throw new InvalidArgumentException(
                    'Le deuxième diagnostic est obligatoire pour terminer.'
                );
            }

            if (
                trim((string) $compteRendu) === ''
            ) {
                throw new InvalidArgumentException(
                    'Le compte rendu est obligatoire pour terminer.'
                );
            }
        }

        $dateFin = in_array(
            $nouveauStatut,
            ['arretee', 'terminee'],
            true
        )
            ? date('Y-m-d')
            : null;

        $this->adapter->query(
            "
                UPDATE maintenance
                SET
                    statut = ?,
                    date_fin = ?,
                    justification = COALESCE(?, justification),
                    diagnostic_final = COALESCE(?, diagnostic_final),
                    compte_rendu = COALESCE(?, compte_rendu),
                    commentaire = COALESCE(?, commentaire)
                WHERE id_maintenance = ?
            ",
            [
                $nouveauStatut,
                $dateFin,
                $justification,
                $diagnosticFinal,
                $compteRendu,
                $commentaire,
                $idMaintenance,
            ]
        );

        $this->synchroniserStatutSession(
            (int) $maintenance['id_session']
        );
    }

    /**
     * Synchronise le statut de la session.
     */
    private function synchroniserStatutSession(
        int $idSession
    ): void {
        if ($idSession <= 0) {
            return;
        }

        $rows = $this->adapter->query(
            "
                SELECT statut
                FROM maintenance
                WHERE id_session = ?
            ",
            [$idSession]
        )->toArray();

        if (!$rows) {
            return;
        }

        $statuts = array_map(
            static fn(array $row): string =>
                (string) $row['statut'],
            $rows
        );

        if (
            count(
                array_filter(
                    $statuts,
                    static fn(string $s): bool =>
                        $s === 'terminee'
                )
            ) === count($statuts)
        ) {
            $sessionStatut = 'terminee';
        } elseif (
            in_array(
                'en_cours',
                $statuts,
                true
            )
        ) {
            $sessionStatut = 'en_cours';
        } elseif (
            in_array(
                'suspendue',
                $statuts,
                true
            )
        ) {
            $sessionStatut = 'suspendue';
        } elseif (
            in_array(
                'arretee',
                $statuts,
                true
            )
        ) {
            $sessionStatut = 'arretee';
        } elseif (
            in_array(
                'affectee',
                $statuts,
                true
            )
        ) {
            $sessionStatut = 'affectee';
        } else {
            $sessionStatut = 'planifiee';
        }

        $this->adapter->query(
            "
                UPDATE session_maintenance
                SET statut = ?
                WHERE id_session = ?
            ",
            [
                $sessionStatut,
                $idSession,
            ]
        );
    }

    /**
     * Retourne tous les équipements d'une session.
     */
    public function getEquipementsDeSession(
        int $idSession
    ): array {
        if ($idSession <= 0) {
            return [];
        }

        $rows = $this->adapter->query(
            "
                SELECT
                    m.*,

                    e.id_site,
                    e.id_centrale,

                    te.nom_type AS type_equipement,

                    e.marque,
                    e.modele,
                    e.numero_serie,

                    c.type_centrale,

                    s.nom_site,

                    u.nom AS technicien_nom,
                    u.prenom AS technicien_prenom

                FROM maintenance m

                INNER JOIN equipement e
                    ON e.id_equipement = m.id_equipement

                LEFT JOIN type_equipement te
                    ON te.id_type_equipement = e.id_type_equipement

                LEFT JOIN centrale c
                    ON c.id_centrale = e.id_centrale

                LEFT JOIN site s
                    ON s.id_site = e.id_site

                LEFT JOIN technicien t
                    ON t.id_utilisateur = m.id_technicien

                LEFT JOIN utilisateurs u
                    ON u.id = t.id_utilisateur

                WHERE m.id_session = ?

                ORDER BY
                    m.id_maintenance ASC
            ",
            [$idSession]
        )->toArray();

        foreach ($rows as &$row) {
            $row['pieces_jointes'] =
                $this->findPiecesJointes(
                    (int) $row['id_maintenance']
                );
        }

        unset($row);

        return $rows;
    }

    /**
     * Affecte un technicien à toute la session.
     */
    public function affecterTechnicienSession(
        int $idSession,
        ?int $idTechnicien
    ): void {
        if ($idSession <= 0) {
            throw new InvalidArgumentException(
                'Session invalide.'
            );
        }

        if (
            $idTechnicien !== null
            && $idTechnicien <= 0
        ) {
            throw new InvalidArgumentException(
                'Technicien invalide.'
            );
        }

        /*
         * Vérification du technicien.
         */
        if ($idTechnicien !== null) {
            $technicien = $this->adapter->query(
                "
                    SELECT id
                    FROM utilisateurs
                    WHERE id = ?
                      AND LOWER(TRIM(role)) = 'technicien'
                ",
                [$idTechnicien]
            )->current();

            if (!$technicien) {
                throw new InvalidArgumentException(
                    'Le technicien sélectionné n’existe pas.'
                );
            }
        }

        /*
         * Récupère toutes les maintenances de la session.
         */
        $maintenances = $this->adapter->query(
            "
                SELECT
                    id_maintenance,
                    id_equipement,
                    id_technicien,
                    statut
                FROM maintenance
                WHERE id_session = ?
                ORDER BY id_maintenance ASC
            ",
            [$idSession]
        )->toArray();

        if (!$maintenances) {
            throw new InvalidArgumentException(
                'Aucun équipement n’est associé à cette maintenance.'
            );
        }

        foreach ($maintenances as $maintenance) {
            $idMaintenance = (int) (
                $maintenance['id_maintenance'] ?? 0
            );

            $ancienTechnicien = isset(
                $maintenance['id_technicien']
            )
                ? (int) $maintenance['id_technicien']
                : 0;

            if ($idTechnicien === null) {
                $this->adapter->query(
                    "
                        UPDATE maintenance
                        SET
                            id_technicien = NULL,
                            statut = CASE
                                WHEN statut = 'affectee'
                                THEN 'planifiee'
                                ELSE statut
                            END
                        WHERE id_maintenance = ?
                    ",
                    [$idMaintenance]
                );
            } else {
                $this->adapter->query(
                    "
                        UPDATE maintenance
                        SET
                            id_technicien = ?,
                            statut = CASE
                                WHEN statut = 'planifiee'
                                THEN 'affectee'
                                ELSE statut
                            END
                        WHERE id_maintenance = ?
                    ",
                    [
                        $idTechnicien,
                        $idMaintenance,
                    ]
                );
            }

            /*
             * Notification au technicien.
             */
            if (
                $idTechnicien !== null
                && $ancienTechnicien !== $idTechnicien
            ) {
                $this->adapter->query(
                    "
                        INSERT INTO notification
                        (
                            type,
                            statut,
                            id_utilisateur,
                            id_equipement,
                            id_maintenance
                        )
                        VALUES
                        (
                            'maintenance_affectee',
                            'non lue',
                            ?,
                            ?,
                            ?
                        )
                    ",
                    [
                        $idTechnicien,
                        (int) $maintenance['id_equipement'],
                        $idMaintenance,
                    ]
                );
            }
        }

        $this->synchroniserStatutSession(
            $idSession
        );
    }

    /**
     * Change le statut de toutes les maintenances
     * d'une session.
     */
    public function changerStatutSession(
        int $idSession,
        string $nouveauStatut,
        ?string $justification = null,
        ?string $diagnosticFinal = null,
        ?string $compteRendu = null,
        ?string $commentaire = null
    ): void {
        if ($idSession <= 0) {
            throw new InvalidArgumentException(
                'Session invalide.'
            );
        }

        if (
            !in_array(
                $nouveauStatut,
                self::STATUTS,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Statut invalide.'
            );
        }

        $rows = $this->adapter->query(
            "
                SELECT
                    id_maintenance,
                    statut,
                    id_technicien
                FROM maintenance
                WHERE id_session = ?
                ORDER BY id_maintenance ASC
            ",
            [$idSession]
        )->toArray();

        if (!$rows) {
            throw new InvalidArgumentException(
                'Aucune maintenance associée à cette session.'
            );
        }

        $transitions = [
            'planifiee' => ['affectee'],
            'affectee' => ['en_cours'],
            'en_cours' => [
                'suspendue',
                'arretee',
                'terminee',
            ],
            'suspendue' => [
                'en_cours',
                'arretee',
            ],
            'arretee' => [],
            'terminee' => [],
        ];

        /*
         * Toutes les transitions sont vérifiées
         * avant modification.
         */
        foreach ($rows as $row) {
            $ancien = (string) (
                $row['statut'] ?? ''
            );

            if (
                $nouveauStatut !== $ancien
                && !in_array(
                    $nouveauStatut,
                    $transitions[$ancien] ?? [],
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    'La transition vers « '
                    . $nouveauStatut
                    . ' » n’est pas autorisée depuis « '
                    . $ancien
                    . ' ».'
                );
            }
        }

        if (
            in_array(
                $nouveauStatut,
                ['suspendue', 'arretee'],
                true
            )
            && trim((string) $justification) === ''
        ) {
            throw new InvalidArgumentException(
                'Une justification est obligatoire pour suspendre ou arrêter.'
            );
        }

        if ($nouveauStatut === 'terminee') {
            if (
                trim((string) $diagnosticFinal) === ''
            ) {
                throw new InvalidArgumentException(
                    'Le deuxième diagnostic est obligatoire pour terminer.'
                );
            }

            if (
                trim((string) $compteRendu) === ''
            ) {
                throw new InvalidArgumentException(
                    'Le compte rendu est obligatoire pour terminer.'
                );
            }
        }

        $dateFin = in_array(
            $nouveauStatut,
            ['arretee', 'terminee'],
            true
        )
            ? date('Y-m-d')
            : null;

        foreach ($rows as $row) {
            $this->adapter->query(
                "
                    UPDATE maintenance
                    SET
                        statut = ?,
                        date_fin = ?,
                        justification = COALESCE(?, justification),
                        diagnostic_final = COALESCE(?, diagnostic_final),
                        compte_rendu = COALESCE(?, compte_rendu),
                        commentaire = COALESCE(?, commentaire)
                    WHERE id_maintenance = ?
                ",
                [
                    $nouveauStatut,
                    $dateFin,
                    $justification,
                    $diagnosticFinal,
                    $compteRendu,
                    $commentaire,
                    (int) $row['id_maintenance'],
                ]
            );
        }

        $this->synchroniserStatutSession(
            $idSession
        );
    }

    /**
     * Historique des maintenances d'un équipement.
     *
     * IMPORTANT :
     * le site vient maintenant directement de
     * equipement.id_site.
     */
    public function getMaintenancesByEquipement(
        int $idEquipement
    ): array {
        if ($idEquipement <= 0) {
            return [];
        }

        $sql = "
            SELECT
                m.*,

                e.id_site,
                e.id_centrale,

                c.type_centrale,

                s.nom_site,

                u.nom AS technicien_nom,
                u.prenom AS technicien_prenom

            FROM maintenance m

            LEFT JOIN equipement e
                ON e.id_equipement = m.id_equipement

            LEFT JOIN centrale c
                ON c.id_centrale = e.id_centrale

            /*
             * CORRECTION IMPORTANTE :
             * le site appartient à l'équipement.
             */
            LEFT JOIN site s
                ON s.id_site = e.id_site

            LEFT JOIN technicien t
                ON t.id_utilisateur = m.id_technicien

            LEFT JOIN utilisateurs u
                ON u.id = t.id_utilisateur

            WHERE m.id_equipement = ?

            ORDER BY
                m.date_maintenance DESC,
                m.id_maintenance DESC
        ";

        return $this->adapter->query(
            $sql,
            [$idEquipement]
        )->toArray();
    }

    /**
     * Retourne les pièces jointes d'une maintenance.
     */
    public function findPiecesJointes(
        int $idMaintenance
    ): array {
        if ($idMaintenance <= 0) {
            return [];
        }

        return $this->adapter->query(
            "
                SELECT *
                FROM piece_jointe_intervention
                WHERE id_maintenance = ?
                ORDER BY date_ajout DESC
            ",
            [$idMaintenance]
        )->toArray();
    }

    /**
     * Ajoute une pièce jointe.
     */
    public function ajouterPieceJointe(
        int $idMaintenance,
        string $urlFichier,
        string $typeFichier,
        ?string $legende = null,
        string $phase = 'intervention'
    ): void {
        if (
            $idMaintenance <= 0
            || trim($urlFichier) === ''
        ) {
            throw new InvalidArgumentException(
                'Pièce jointe invalide.'
            );
        }

        $this->adapter->query(
            "
                INSERT INTO piece_jointe_intervention
                (
                    id_maintenance,
                    url_fichier,
                    type_fichier,
                    legende,
                    phase
                )
                VALUES (?, ?, ?, ?, ?)
            ",
            [
                $idMaintenance,
                $urlFichier,
                $typeFichier,
                $legende,
                $phase,
            ]
        );
    }
}