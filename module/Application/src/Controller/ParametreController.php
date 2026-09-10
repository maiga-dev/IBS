<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * Page "Paramètres" : point d'entrée unique vers les données de
 * référence (Famille, Centrale, Pays, Ville, Site, Équipement,
 * Type d'équipement, Utilisateurs). Pas de logique métier ici, juste
 * l'affichage des cases qui renvoient vers les pages de gestion
 * existantes.
 */
class ParametreController extends AbstractActionController
{
    public function indexAction(): ViewModel
    {
        return new ViewModel();
    }
}
