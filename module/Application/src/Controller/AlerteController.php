<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;

/**
 * Placeholder — page des alertes/notifications (échéances de maintenance,
 * stock bas, etc. — table `notification`). Permet au lien 🔔 de la
 * sidebar de fonctionner sans 404 en attendant le développement réel.
 */
class AlerteController extends AbstractActionController
{
    private Adapter $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function indexAction(): ViewModel
    {
        $sql = "
            SELECT id_notification, type, date_creation, statut,
                   id_utilisateur, id_equipement, id_maintenance
            FROM notification
            WHERE statut = 'non lue'
            ORDER BY date_creation DESC
            LIMIT 50
        ";

        $notifications = $this->adapter
            ->query($sql, Adapter::QUERY_MODE_EXECUTE)
            ->toArray();

        return new ViewModel([
            'notifications' => $notifications,
        ]);
    }
}