<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;

class IndexController extends AbstractActionController
{
    private Adapter $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function indexAction(): ViewModel
    {
        /*
         * ============================================================
         * NOMBRE TOTAL D'EQUIPEMENTS
         * ============================================================
         */
        $result = $this->adapter->query(
            'SELECT COUNT(*) AS total FROM equipement',
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $totalEquipements = (int) ($result['total'] ?? 0);


        /*
         * ============================================================
         * EQUIPEMENTS A JOUR
         *
         * CORRIGÉ : equipement.statut n'existe plus. On calcule
         * désormais "à jour" à partir de date_prochaine_maintenance
         * (pas de date = pas encore planifié = considéré à jour).
         * ============================================================
         */
        $result = $this->adapter->query(
            "SELECT COUNT(*) AS total
             FROM equipement
             WHERE date_prochaine_maintenance IS NULL
                OR date_prochaine_maintenance >= CURRENT_DATE",
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $equipementsActifs = (int) ($result['total'] ?? 0);


        /*
         * ============================================================
         * MAINTENANCES EN ATTENTE
         * ============================================================
         */
        $result = $this->adapter->query(
            "SELECT COUNT(*) AS total
             FROM maintenance
             WHERE statut IN ('planifiee', 'en_cours')",
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $maintenancesEnAttente = (int) ($result['total'] ?? 0);


        /*
         * ============================================================
         * SANTE DU PARC
         *
         * Proportion d'équipements à jour (cf. ci-dessus).
         * Si aucun équipement n'existe : 100 %
         * ============================================================
         */
        if ($totalEquipements > 0) {

            $santeParc = round(
                ($equipementsActifs / $totalEquipements) * 100
            );

        } else {

            $santeParc = 100;
        }


        /*
         * ============================================================
         * MAINTENANCES RECENTES
         *
         * CORRIGÉ : e.type n'existe plus sur equipement — le libellé
         * du type vient maintenant de type_equipement.nom_type.
         * ============================================================
         */
        $sql = "
            SELECT
                m.id_maintenance,
                m.date_maintenance,
                m.type_maintenance,
                m.statut,
                m.compte_rendu,

                e.id_equipement,
                te.nom_type AS type_equipement,
                e.marque,
                e.modele,
                e.numero_serie,

                u.nom,
                u.prenom

            FROM maintenance m

            INNER JOIN equipement e
                ON e.id_equipement = m.id_equipement

            LEFT JOIN type_equipement te
                ON te.id_type_equipement = e.id_type_equipement

            LEFT JOIN technicien t
                ON t.id_utilisateur = m.id_technicien

            LEFT JOIN utilisateurs u
                ON u.id = t.id_utilisateur

            ORDER BY
                m.date_maintenance DESC,
                m.id_maintenance DESC

            LIMIT 6
        ";

        $maintenancesRecentes = $this->adapter
            ->query($sql, Adapter::QUERY_MODE_EXECUTE)
            ->toArray();


        /*
         * ============================================================
         * NOMBRE DE PAYS
         * ============================================================
         */
        $result = $this->adapter->query(
            'SELECT COUNT(*) AS total FROM pays',
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $nombrePays = (int) ($result['total'] ?? 0);


        /*
         * ============================================================
         * NOMBRE DE SITES
         * ============================================================
         */
        $result = $this->adapter->query(
            'SELECT COUNT(*) AS total FROM site',
            Adapter::QUERY_MODE_EXECUTE
        )->current();

        $nombreSites = (int) ($result['total'] ?? 0);


        /*
         * ============================================================
         * DONNEES ENVOYEES A LA VUE
         * ============================================================
         */
        return new ViewModel([
            'totalEquipements'      => $totalEquipements,
            'equipementsActifs'     => $equipementsActifs,
            'maintenancesEnAttente' => $maintenancesEnAttente,
            'santeParc'             => $santeParc,
            'maintenancesRecentes'  => $maintenancesRecentes,
            'nombrePays'            => $nombrePays,
            'nombreSites'           => $nombreSites,
        ]);
    }
}