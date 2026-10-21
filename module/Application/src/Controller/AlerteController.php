<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Db\Adapter\Adapter;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class AlerteController extends AbstractActionController
{
    private Adapter $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function indexAction(): ViewModel
    {
        // Générer les alertes d'échéance avant l'affichage.
        $this->genererAlertesEcheances();

        $sql = "
            SELECT
                n.id_notification,
                n.type AS type_notification,
                n.date_creation,
                n.statut AS statut_notification,
                n.id_utilisateur,
                n.id_equipement,
                n.id_maintenance,

                destinataire.nom AS nom_destinataire,
                destinataire.prenom AS prenom_destinataire,

                e.id_equipement AS equipement_id,
                e.designation,
                e.numero_serie,
                e.marque,
                e.modele,
                e.date_prochaine_maintenance,
                te.nom_type,

                c.type_centrale,

                s.nom_site,
                s.adresse AS adresse_site,
                s.telephone AS telephone_site,

                v.nom_ville,
                p.nom_pays,

                m.date_maintenance,
                m.date_fin,
                m.type_maintenance,
                m.statut AS statut_maintenance,
                m.diagnostic,
                m.diagnostic_final,
                m.commentaire,
                m.compte_rendu,
                m.justification,

                COALESCE(
                    tech_user.prenom || ' ' || tech_user.nom,
                    tech_user.nom,
                    tech_user.prenom
                ) AS nom_technicien,

                tech.specialite AS specialite_technicien

            FROM notification n

            LEFT JOIN utilisateurs destinataire
                ON destinataire.id = n.id_utilisateur

            LEFT JOIN maintenance m
                ON m.id_maintenance = n.id_maintenance

            LEFT JOIN equipement e
                ON e.id_equipement = COALESCE(
                    n.id_equipement,
                    m.id_equipement
                )

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

            LEFT JOIN technicien tech
                ON tech.id_utilisateur = m.id_technicien

            LEFT JOIN utilisateurs tech_user
                ON tech_user.id = tech.id_utilisateur

            WHERE n.statut = 'non lue'

            ORDER BY n.date_creation DESC NULLS LAST,
                     n.id_notification DESC

            LIMIT 50
        ";

        $notifications = $this->adapter
            ->query($sql, Adapter::QUERY_MODE_EXECUTE)
            ->toArray();

        return new ViewModel([
            'notifications' => $notifications,
        ]);
    }

    /**
     * Crée les alertes d'échéance à J-7, J-3 et J-1.
     *
     * Une même alerte ne sera pas recréée pour le même
     * équipement, le même type et la même journée.
     */
    private function genererAlertesEcheances(): void
    {
        $echeances = [
            7 => 'maintenance_a_faire_j7',
            3 => 'maintenance_a_faire_j3',
            1 => 'maintenance_a_faire_j1',
        ];

        foreach ($echeances as $jours => $type) {
            $sql = "
                INSERT INTO notification (
                    type,
                    date_creation,
                    statut,
                    id_utilisateur,
                    id_equipement,
                    id_maintenance
                )
                SELECT
                    ?,
                    CURRENT_TIMESTAMP,
                    'non lue',
                    NULL,
                    e.id_equipement,
                    NULL
                FROM equipement e
                WHERE e.date_prochaine_maintenance =
                      CURRENT_DATE + CAST(? AS INTEGER)

                  AND NOT EXISTS (
                      SELECT 1
                      FROM notification n
                      WHERE n.type = ?
                        AND n.id_equipement = e.id_equipement
                        AND n.date_creation::date = CURRENT_DATE
                  )
            ";

            $this->adapter->query(
                $sql,
                [
                    $type,
                    $jours,
                    $type,
                ]
            );
        }
    }
}
