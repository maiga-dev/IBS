<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;

/**
 * Placeholder — la page Rapports (§5.6 du cahier des charges : export
 * PDF/Excel, indicateurs de performance) n'est pas encore implémentée.
 * Ce contrôleur permet juste au lien de la sidebar de fonctionner sans
 * 404 en attendant le développement réel de la fonctionnalité.
 */
class RapportController extends AbstractActionController
{
    private Adapter $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function indexAction(): ViewModel
    {
        return new ViewModel();
    }
}