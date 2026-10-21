<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;

class IndexController extends AbstractActionController
{
    use RoleTrait;

    private Adapter $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function indexAction(): ViewModel
    {
        /*
         * ============================================================
         * VERIFICATION DE CONNEXION
         * ============================================================
         */

        if ($redirect = $this->exigerConnexion()) {
            return $redirect;
        }


        /*
         * ============================================================
         * TOTAL DES EQUIPEMENTS
         * ============================================================
         */

        $result = $this->adapter->query(
            "
            SELECT COUNT(*) AS total
            FROM equipement
            ",
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $totalEquipements = (int) ($result['total'] ?? 0);


        /*
         * ============================================================
         * EQUIPEMENTS A JOUR
         * ============================================================
         */

        $result = $this->adapter->query(
            "
            SELECT COUNT(*) AS total
            FROM equipement
            WHERE date_prochaine_maintenance IS NULL
               OR date_prochaine_maintenance >= CURRENT_DATE
            ",
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $equipementsActifs = (int) ($result['total'] ?? 0);


        /*
         * ============================================================
         * MAINTENANCES EN ATTENTE
         * ============================================================
         */

        $result = $this->adapter->query(
            "
            SELECT COUNT(*) AS total
            FROM maintenance
            WHERE statut IN (
                'planifiee',
                'affectee',
                'en_cours'
            )
            ",
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $maintenancesEnAttente = (int) ($result['total'] ?? 0);


        /*
         * ============================================================
         * SANTE DU PARC
         * ============================================================
         */

        if ($totalEquipements > 0) {

            $santeParc = (int) round(
                ($equipementsActifs / $totalEquipements) * 100
            );

        } else {

            $santeParc = 100;

        }


        /*
         * ============================================================
         * MAINTENANCES RECENTES
         *
         * Chaîne de localisation :
         *
         * maintenance
         *      ↓
         * equipement
         *      ↓
         * centrale
         *
         * Pour la localisation :
         *
         * equipement
         *      ↓
         * site
         *      ↓
         * ville
         *      ↓
         * pays
         *
         * IMPORTANT :
         * La table centrale ne possède pas id_site.
         * Le site est relié directement à l'équipement par id_site.
         * ============================================================
         */

        $sql = "
            SELECT

                m.id_maintenance,
                m.id_session,
                m.date_maintenance,
                m.type_maintenance,
                m.statut,
                m.compte_rendu,

                e.id_equipement,
                te.nom_type AS type_equipement,
                e.marque,
                e.modele,
                e.numero_serie,

                c.id_centrale,
                c.type_centrale,

                s.id_site,
                s.nom_site,

                v.id_ville,
                v.nom_ville,

                p.id_pays,
                p.nom_pays,

                u.nom,
                u.prenom

            FROM maintenance m

            INNER JOIN equipement e
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

            LEFT JOIN technicien t
                ON t.id_utilisateur = m.id_technicien

            LEFT JOIN utilisateurs u
                ON u.id = t.id_utilisateur

            ORDER BY
                m.date_maintenance DESC NULLS LAST,
                m.id_maintenance DESC
        ";

        $maintenancesRecentes = $this->adapter
            ->query(
                $sql,
                Adapter::QUERY_MODE_EXECUTE
            )
            ->toArray();


        /*
         * ============================================================
         * NOMBRE DE PAYS
         * ============================================================
         */

        $result = $this->adapter->query(
            "
            SELECT COUNT(*) AS total
            FROM pays
            ",
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $nombrePays = (int) ($result['total'] ?? 0);


        /*
         * ============================================================
         * NOMBRE DE SITES
         * ============================================================
         */

        $result = $this->adapter->query(
            "
            SELECT COUNT(*) AS total
            FROM site
            ",
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $nombreSites = (int) ($result['total'] ?? 0);


        /*
         * ============================================================
         * FILTRE PAYS
         *
         * Relation :
         *
         * pays
         *   ↓
         * ville
         *   ↓
         * site
         *   ↓
         * equipement
         *   ↓
         * centrale
         *   ↓
         * maintenance
         * ============================================================
         */

        $repartitionPays = $this->adapter->query(
            "
            SELECT

                p.id_pays,
                p.nom_pays AS libelle,

                COUNT(DISTINCT m.id_maintenance) AS total

            FROM pays p

            LEFT JOIN ville v
                ON v.id_pays = p.id_pays

            LEFT JOIN site s
                ON s.id_ville = v.id_ville

            LEFT JOIN equipement e
                ON e.id_site = s.id_site

            LEFT JOIN centrale c
                ON c.id_centrale = e.id_centrale

            LEFT JOIN maintenance m
                ON m.id_equipement = e.id_equipement

            GROUP BY
                p.id_pays,
                p.nom_pays

            ORDER BY
                p.nom_pays ASC
            ",
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();


        /*
         * ============================================================
         * FILTRE VILLE
         * ============================================================
         */

        $repartitionVille = $this->adapter->query(
            "
            SELECT

                v.id_ville,
                v.nom_ville AS libelle,

                COUNT(DISTINCT m.id_maintenance) AS total

            FROM ville v

            LEFT JOIN site s
                ON s.id_ville = v.id_ville

            LEFT JOIN equipement e
                ON e.id_site = s.id_site

            LEFT JOIN centrale c
                ON c.id_centrale = e.id_centrale

            LEFT JOIN maintenance m
                ON m.id_equipement = e.id_equipement

            GROUP BY
                v.id_ville,
                v.nom_ville

            ORDER BY
                v.nom_ville ASC
            ",
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();


        /*
         * ============================================================
         * FILTRE SITE
         * ============================================================
         */

        $repartitionSite = $this->adapter->query(
            "
            SELECT

                s.id_site,
                s.nom_site AS libelle,

                COUNT(DISTINCT m.id_maintenance) AS total

            FROM site s

            LEFT JOIN equipement e
                ON e.id_site = s.id_site

            LEFT JOIN centrale c
                ON c.id_centrale = e.id_centrale

            LEFT JOIN maintenance m
                ON m.id_equipement = e.id_equipement

            GROUP BY
                s.id_site,
                s.nom_site

            ORDER BY
                s.nom_site ASC
            ",
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();


        /*
         * ============================================================
         * FILTRE CENTRALE
         *
         * Ici, on part directement de centrale puisque la centrale
         * est reliée à l'équipement par id_centrale.
         * ============================================================
         */

        $repartitionCentrale = $this->adapter->query(
            "
            SELECT

                c.id_centrale,
                c.type_centrale AS libelle,

                COUNT(DISTINCT m.id_maintenance) AS total

            FROM centrale c

            LEFT JOIN equipement e
                ON e.id_centrale = c.id_centrale

            LEFT JOIN maintenance m
                ON m.id_equipement = e.id_equipement

            GROUP BY
                c.id_centrale,
                c.type_centrale

            ORDER BY
                c.type_centrale ASC
            ",
            Adapter::QUERY_MODE_EXECUTE
        )->toArray();


        /*
         * ============================================================
         * DONNEES ENVOYEES A LA VUE
         * ============================================================
         */

        return new ViewModel([
            'totalEquipements'
                => $totalEquipements,

            'equipementsActifs'
                => $equipementsActifs,

            'maintenancesEnAttente'
                => $maintenancesEnAttente,

            'santeParc'
                => $santeParc,

            'maintenancesRecentes'
                => $maintenancesRecentes,

            'nombrePays'
                => $nombrePays,

            'nombreSites'
                => $nombreSites,

            'repartitionPays'
                => $repartitionPays,

            'repartitionVille'
                => $repartitionVille,

            'repartitionSite'
                => $repartitionSite,

            'repartitionCentrale'
                => $repartitionCentrale,
        ]);
    }
}