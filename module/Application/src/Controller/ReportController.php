<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Db\Adapter\Adapter;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class ReportController extends AbstractActionController
{
    public function __construct(
        private Adapter $adapter
    ) {
    }

    /**
     * Exécute une requête SQL et retourne les résultats sous forme de tableau.
     */
    private function fetchAll(string $sql, array $params = []): array
    {
        return $this->adapter
            ->query($sql, $params)
            ->toArray();
    }

    /**
     * Compte les lignes d'une table autorisée.
     */
    private function countRows(string $table): ?int
    {
        $tablesAutorisees = [
            'famille',
            'centrale',
            'pays',
            'ville',
            'site',
            'equipement',
            'utilisateurs',
            'maintenance',
        ];

        if (!in_array($table, $tablesAutorisees, true)) {
            return null;
        }

        try {
            $rows = $this->fetchAll(
                "SELECT COUNT(*) AS total FROM {$table}"
            );

            return (int) ($rows[0]['total'] ?? 0);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Page d'accueil des rapports.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'nombreFamilles' => $this->countRows('famille'),
            'nombreCentrales' => $this->countRows('centrale'),
            'nombreSites' => $this->countRows('site'),
            'nombreEquipements' => $this->countRows('equipement'),
            'nombreMaintenances' => $this->countRows('maintenance'),
        ]);
    }

    /**
     * Synthèse complète du parc biomédical.
     */
    public function explorerAction(): ViewModel
    {
        $data = [
            'familles' => $this->fetchAll("
                SELECT
                    f.id_famille,
                    f.nom_famille,
                    COUNT(DISTINCT c.id_centrale) AS nombre_centrales
                FROM famille f
                LEFT JOIN centrale c
                    ON c.id_famille = f.id_famille
                GROUP BY
                    f.id_famille,
                    f.nom_famille
                ORDER BY f.nom_famille
            "),

            'centrales' => $this->fetchAll("
                SELECT
                    c.id_centrale,
                    c.type_centrale,
                    f.nom_famille,
                    COUNT(DISTINCT e.id_equipement) AS nombre_equipements
                FROM centrale c
                LEFT JOIN famille f
                    ON f.id_famille = c.id_famille
                LEFT JOIN equipement e
                    ON e.id_centrale = c.id_centrale
                GROUP BY
                    c.id_centrale,
                    c.type_centrale,
                    f.nom_famille
                ORDER BY c.type_centrale
            "),

            'pays' => $this->fetchAll("
                SELECT
                    p.id_pays,
                    p.nom_pays,
                    COUNT(DISTINCT v.id_ville) AS nombre_villes
                FROM pays p
                LEFT JOIN ville v
                    ON v.id_pays = p.id_pays
                GROUP BY
                    p.id_pays,
                    p.nom_pays
                ORDER BY p.nom_pays
            "),

            'villes' => $this->fetchAll("
                SELECT
                    v.id_ville,
                    v.nom_ville,
                    p.nom_pays,
                    COUNT(DISTINCT s.id_site) AS nombre_sites
                FROM ville v
                LEFT JOIN pays p
                    ON p.id_pays = v.id_pays
                LEFT JOIN site s
                    ON s.id_ville = v.id_ville
                GROUP BY
                    v.id_ville,
                    v.nom_ville,
                    p.nom_pays
                ORDER BY
                    p.nom_pays,
                    v.nom_ville
            "),

            'sites' => $this->fetchAll("
                SELECT
                    s.id_site,
                    s.nom_site,
                    s.adresse,
                    s.telephone,
                    v.nom_ville,
                    p.nom_pays,
                    COUNT(DISTINCT e.id_equipement) AS nombre_equipements
                FROM site s
                LEFT JOIN ville v
                    ON v.id_ville = s.id_ville
                LEFT JOIN pays p
                    ON p.id_pays = v.id_pays
                LEFT JOIN equipement e
                    ON e.id_site = s.id_site
                GROUP BY
                    s.id_site,
                    s.nom_site,
                    s.adresse,
                    s.telephone,
                    v.nom_ville,
                    p.nom_pays
                ORDER BY s.nom_site
            "),

            'equipements' => $this->fetchAll("
                SELECT
                    e.id_equipement,
                    e.designation,
                    e.numero_serie,
                    e.marque,
                    e.modele,
                    e.date_mise_en_service,
                    te.nom_type,
                    c.type_centrale,
                    s.nom_site,
                    v.nom_ville,
                    p.nom_pays
                FROM equipement e
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
                ORDER BY
                    e.designation,
                    e.id_equipement
            "),

            'utilisateurs' => $this->fetchAll("
                SELECT
                    u.id,
                    u.prenom,
                    u.nom
                FROM utilisateurs u
                ORDER BY
                    u.nom,
                    u.prenom
            "),
        ];

        return new ViewModel($data);
    }

    /**
     * Liste toutes les maintenances, quel que soit leur statut.
     */
    public function maintenanceAction(): ViewModel
    {
        $sql = "
            SELECT
                m.id_maintenance,
                m.id_session,
                m.date_maintenance,
                m.date_fin,
                m.type_maintenance,
                m.statut,
                m.diagnostic,
                m.diagnostic_final,
                m.compte_rendu,
                m.commentaire,

                sm.date_session,

                c.type_centrale,

                s.nom_site,
                v.nom_ville,
                p.nom_pays,

                COALESCE(
                    NULLIF(
                        TRIM(
                            CONCAT_WS(
                                ' ',
                                u.prenom,
                                u.nom
                            )
                        ),
                        ''
                    ),
                    'Non renseigné'
                ) AS technicien_nom,

                pj.documents_finaux

            FROM maintenance m

            LEFT JOIN session_maintenance sm
                ON sm.id_session = m.id_session

            LEFT JOIN centrale c
                ON c.id_centrale = sm.id_centrale

            LEFT JOIN equipement e
                ON e.id_equipement = m.id_equipement

            LEFT JOIN site s
                ON s.id_site = e.id_site

            LEFT JOIN ville v
                ON v.id_ville = s.id_ville

            LEFT JOIN pays p
                ON p.id_pays = v.id_pays

            LEFT JOIN technicien t
                ON t.id_utilisateur = COALESCE(
                    m.id_technicien,
                    sm.id_technicien
                )

            LEFT JOIN utilisateurs u
                ON u.id = t.id_utilisateur

            LEFT JOIN LATERAL (
                SELECT
                    STRING_AGG(
                        pi.url_fichier,
                        '||' ORDER BY pi.date_ajout DESC
                    ) AS documents_finaux
                FROM piece_jointe_intervention pi
                WHERE pi.id_maintenance = m.id_maintenance
                  AND pi.phase = 'finale'
            ) pj ON TRUE

            ORDER BY
                m.date_maintenance DESC,
                m.id_maintenance DESC
        ";

        $maintenances = $this->fetchAll($sql);

        return new ViewModel([
            'maintenances' => $maintenances,
            'nombreMaintenances' => count($maintenances),
        ]);
    }
}