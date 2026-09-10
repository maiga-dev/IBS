<?php

declare(strict_types=1);

namespace Application;

use Application\Model\SiteTable;
use Application\Model\EquipementTable;
use Application\Model\TypeEquipementTable;
use Application\Model\CentraleTable;
use Application\Model\MaintenanceTable;
use Application\Model\ReferenceTable;
use Application\Model\FamilleTable;
use Application\Model\PaysTable;
use Application\Model\VilleTable;
use Application\Model\UtilisateurTable;

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\Session as AuthSessionStorage;
use Laminas\Db\Adapter\Adapter;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [

    /*
    |--------------------------------------------------------------------------
    | ROUTES
    |--------------------------------------------------------------------------
    */

    'router' => [

        'routes' => [

            /*
            |--------------------------------------------------------------------------
            | ACCUEIL
            |--------------------------------------------------------------------------
            */

            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            'application' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | PARAMÈTRES
            |--------------------------------------------------------------------------
            */

            'parametre' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/parametres',
                    'defaults' => [
                        'controller' => Controller\ParametreController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | FAMILLE
            |--------------------------------------------------------------------------
            */

            'famille' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/familles[/:action[/:id]]',

                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],

                    'defaults' => [
                        'controller' => Controller\FamilleController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | CENTRALE
            |--------------------------------------------------------------------------
            */

            'centrale' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/centrales[/:action[/:id]]',

                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],

                    'defaults' => [
                        'controller' => Controller\CentraleController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | PAYS
            |--------------------------------------------------------------------------
            */

            'pays' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/pays[/:action[/:id]]',

                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],

                    'defaults' => [
                        'controller' => Controller\PaysController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | VILLE
            |--------------------------------------------------------------------------
            */

            'ville' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/villes[/:action[/:id]]',

                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],

                    'defaults' => [
                        'controller' => Controller\VilleController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | SITE
            |--------------------------------------------------------------------------
            */

            'site' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/sites[/:action[/:id]]',

                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],

                    'defaults' => [
                        'controller' => Controller\SiteController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | TYPE D'ÉQUIPEMENT
            |--------------------------------------------------------------------------
            */
            'type_equipement' => [
                'type' => Segment::class,

                'options' => [
                    'route' => '/types-equipement[/:action[/:id]]',

                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],

                    'defaults' => [
                        'controller' => Controller\TypeEquipementController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | ÉQUIPEMENT
            |--------------------------------------------------------------------------
            */

            'equipement' => [
                'type' => Segment::class,

                'options' => [
                    'route' => '/equipements[/:action[/:id]]',

                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],

                    'defaults' => [
                        'controller' => Controller\EquipementController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            'equipement_reseau_fluide' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/reseaux-fluides[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\EquipementReseauFluideController::class,
                        'action'     => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE
            |--------------------------------------------------------------------------
            */

            'maintenance' => [
                'type' => Segment::class,

                'options' => [
                    'route' => '/maintenances[/:action[/:id]]',

                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],

                    'defaults' => [
                        'controller' => Controller\MaintenanceController::class,
                        'action' => 'index',
                    ],
                ],
            ],

           
            /*
            |--------------------------------------------------------------------------
            | AUTHENTIFICATION
            |--------------------------------------------------------------------------
            */

            'auth' => [
                'type' => Segment::class,

                'options' => [
                    'route' => '/auth[/:action]',

                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],

                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'login',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | UTILISATEURS
            |--------------------------------------------------------------------------
            */

            'utilisateur' => [
                'type' => Segment::class,

                'options' => [
                    'route' => '/utilisateurs[/:action[/:id]]',

                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],

                    'defaults' => [
                        'controller' => Controller\UtilisateurController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | RAPPORT
            |--------------------------------------------------------------------------
            */

            'rapport' => [
                'type' => Literal::class,

                'options' => [
                    'route' => '/rapports',

                    'defaults' => [
                        'controller' => Controller\RapportController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | ALERTES
            |--------------------------------------------------------------------------
            */

            'alerte' => [
                'type' => Literal::class,

                'options' => [
                    'route' => '/alertes',

                    'defaults' => [
                        'controller' => Controller\AlerteController::class,
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SERVICE MANAGER
    |--------------------------------------------------------------------------
    */

    'service_manager' => [

        'factories' => [

            /*
            |--------------------------------------------------------------------------
            | Maintenance
            |--------------------------------------------------------------------------
            */

            Model\MaintenanceTable::class => function ($container) {
                return new MaintenanceTable(
                    $container->get(Adapter::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Authentification
            |--------------------------------------------------------------------------
            */

            AuthenticationService::class => function ($container) {
                return new AuthenticationService(
                    new AuthSessionStorage('ibs_pulse_auth')
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Famille
            |--------------------------------------------------------------------------
            */

            Model\FamilleTable::class => function ($container) {
                return new FamilleTable(
                    $container->get(Adapter::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Pays
            |--------------------------------------------------------------------------
            */

            Model\PaysTable::class => function ($container) {
                return new PaysTable(
                    $container->get(Adapter::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Ville
            |--------------------------------------------------------------------------
            */

            Model\VilleTable::class => function ($container) {
                return new VilleTable(
                    $container->get(Adapter::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Site
            |--------------------------------------------------------------------------
            */

            Model\SiteTable::class => function ($container) {
                return new SiteTable(
                    $container->get(Adapter::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Centrale
            |--------------------------------------------------------------------------
            */

            Model\CentraleTable::class => function ($container) {
                return new CentraleTable(
                    $container->get(Adapter::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Type équipement
            |--------------------------------------------------------------------------
            */

            Model\TypeEquipementTable::class => function ($container) {
                return new TypeEquipementTable(
                    $container->get(Adapter::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Équipement
            |--------------------------------------------------------------------------
            */

            Model\EquipementTable::class => function ($container) {
                return new EquipementTable(
                    $container->get(Adapter::class)
                );
            },

            Model\EquipementReseauFluideTable::class => function ($container) {
                return new Model\EquipementReseauFluideTable($container->get(Adapter::class));
            },

            /*
            |--------------------------------------------------------------------------
            | Utilisateur
            |--------------------------------------------------------------------------
            */

            Model\UtilisateurTable::class => function ($container) {
                return new UtilisateurTable(
                    $container->get(Adapter::class)
                );
            },
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CONTROLLERS
    |--------------------------------------------------------------------------
    */

    'controllers' => [

        'factories' => [

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            Controller\IndexController::class => function ($container) {
                return new Controller\IndexController(
                    $container->get(Adapter::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | ÉQUIPEMENT
            |--------------------------------------------------------------------------
            */

           Controller\EquipementController::class => function ($container) {

                return new Controller\EquipementController(

                    $container->get(
                        Model\EquipementTable::class
                    ),

                    $container->get(
                        Model\TypeEquipementTable::class
                    ),

                    $container->get(
                        Model\CentraleTable::class
                    ),

                    $container->get(
                        Model\SiteTable::class
                    ),

                    $container->get(
                        Model\MaintenanceTable::class
                    )
                );
            },


            /*
            |--------------------------------------------------------------------------
            | Maintenance
            |--------------------------------------------------------------------------
            */

            Controller\MaintenanceController::class => function ($container) {
                return new Controller\MaintenanceController(
                    $container->get(
                        Model\MaintenanceTable::class
                    )
                );
            },

           

            /*
            |--------------------------------------------------------------------------
            | Auth
            |--------------------------------------------------------------------------
            */

            Controller\AuthController::class => function ($container) {
                return new Controller\AuthController(
                    $container->get(Adapter::class),
                    $container->get(AuthenticationService::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Utilisateur
            |--------------------------------------------------------------------------
            */

            Controller\UtilisateurController::class => function ($container) {
                return new Controller\UtilisateurController(
                    $container->get(
                        Model\UtilisateurTable::class
                    )
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Rapport
            |--------------------------------------------------------------------------
            */

            Controller\RapportController::class => function ($container) {
                return new Controller\RapportController(
                    $container->get(Adapter::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Alerte
            |--------------------------------------------------------------------------
            */

            Controller\AlerteController::class => function ($container) {
                return new Controller\AlerteController(
                    $container->get(Adapter::class)
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Paramètres
            |--------------------------------------------------------------------------
            */

            Controller\ParametreController::class => function ($container) {
                return new Controller\ParametreController();
            },

            /*
            |--------------------------------------------------------------------------
            | Famille
            |--------------------------------------------------------------------------
            */

            Controller\FamilleController::class => function ($container) {
                return new Controller\FamilleController(
                    $container->get(
                        Model\FamilleTable::class
                    )
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Pays
            |--------------------------------------------------------------------------
            */

            Controller\PaysController::class => function ($container) {
                return new Controller\PaysController(
                    $container->get(
                        Model\PaysTable::class
                    )
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Ville
            |--------------------------------------------------------------------------
            */

            Controller\VilleController::class => function ($container) {
                return new Controller\VilleController(
                    $container->get(
                        Model\VilleTable::class
                    ),
                    $container->get(
                        Model\PaysTable::class
                    )
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Site
            |--------------------------------------------------------------------------
            */

            Controller\SiteController::class => function ($container) {
                return new Controller\SiteController(
                    $container->get(
                        Model\SiteTable::class
                    ),
                    $container->get(
                        Model\VilleTable::class
                    )
                );
            },

            /*
            |--------------------------------------------------------------------------
            | Centrale
            |--------------------------------------------------------------------------
            */

            Controller\CentraleController::class => function ($container) {
                return new Controller\CentraleController(
                    $container->get(
                        Model\CentraleTable::class
                    ),
                    $container->get(
                        Model\SiteTable::class
                    ),
                    $container->get(
                        Model\FamilleTable::class
                    )
                );
            },

             /*
            |--------------------------------------------------------------------------
            | Type équipement
            |--------------------------------------------------------------------------
            */
            Controller\TypeEquipementController::class => function ($container) {
                return new Controller\TypeEquipementController(
                    $container->get(
                        Model\TypeEquipementTable::class
                    )
                );
            },

            Controller\EquipementReseauFluideController::class => function ($container) {
                return new Controller\EquipementReseauFluideController(
                    $container->get(Model\EquipementReseauFluideTable::class),
                    $container->get(Model\CentraleTable::class),
                    $container->get(Model\TypeEquipementTable::class)
                );
            },
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | VIEW HELPERS
    |--------------------------------------------------------------------------
    */

    'view_helpers' => [

        'aliases' => [
            'url' => \Laminas\View\Helper\Url::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | VIEW MANAGER
    |--------------------------------------------------------------------------
    */

    'view_manager' => [

        'display_not_found_reason' => true,
        'display_exceptions' => true,

        'doctype' => 'HTML5',

        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',

        /*
        |--------------------------------------------------------------------------
        | TEMPLATE MAP
        |--------------------------------------------------------------------------
        */

        'template_map' => [

            /*
            |--------------------------------------------------------------------------
            | Layout
            |--------------------------------------------------------------------------
            */

            'layout/layout-auth'
                => __DIR__ . '/../view/layout/layout-auth.phtml',

            'layout/layout'
                => __DIR__ . '/../view/layout/layout.phtml',

            /*
            |--------------------------------------------------------------------------
            | Auth
            |--------------------------------------------------------------------------
            */

            'application/auth/login'
                => __DIR__ . '/../view/application/auth/login.phtml',

            'application/auth/register'
                => __DIR__ . '/../view/application/auth/register.phtml',

            /*
            |--------------------------------------------------------------------------
            | Accueil
            |--------------------------------------------------------------------------
            */

            'application/index/index'
                => __DIR__ . '/../view/application/index/index.phtml',

            /*
            |--------------------------------------------------------------------------
            | Famille
            |--------------------------------------------------------------------------
            */

            'application/famille/index'
                => __DIR__ . '/../view/application/parametre/famille/index.phtml',

            'application/famille/add'
                => __DIR__ . '/../view/application/parametre/famille/add.phtml',

            'application/famille/edit'
                => __DIR__ . '/../view/application/parametre/famille/edit.phtml',

            'application/famille/form'
                => __DIR__ . '/../view/application/parametre/famille/form.phtml',

            /*
            |--------------------------------------------------------------------------
            | Centrale
            |--------------------------------------------------------------------------
            */

            'application/centrale/index'
                => __DIR__ . '/../view/application/parametre/centrale/index.phtml',

            'application/centrale/add'
                => __DIR__ . '/../view/application/parametre/centrale/add.phtml',

            'application/centrale/edit'
                => __DIR__ . '/../view/application/parametre/centrale/edit.phtml',

            'application/centrale/form'
                => __DIR__ . '/../view/application/parametre/centrale/form.phtml',

            /*
            |--------------------------------------------------------------------------
            | Pays
            |--------------------------------------------------------------------------
            */

            'application/pays/index'
                => __DIR__ . '/../view/application/parametre/pays/index.phtml',

            'application/pays/add'
                => __DIR__ . '/../view/application/parametre/pays/add.phtml',

            'application/pays/edit'
                => __DIR__ . '/../view/application/parametre/pays/edit.phtml',

            'application/pays/form'
                => __DIR__ . '/../view/application/parametre/pays/form.phtml',

            /*
            |--------------------------------------------------------------------------
            | Ville
            |--------------------------------------------------------------------------
            */

            'application/ville/index'
                => __DIR__ . '/../view/application/parametre/ville/index.phtml',

            'application/ville/add'
                => __DIR__ . '/../view/application/parametre/ville/add.phtml',

            'application/ville/edit'
                => __DIR__ . '/../view/application/parametre/ville/edit.phtml',

            'application/ville/form'
                => __DIR__ . '/../view/application/parametre/ville/form.phtml',

            /*
            |--------------------------------------------------------------------------
            | Site
            |--------------------------------------------------------------------------
            */

            'application/site/index'
                => __DIR__ . '/../view/application/parametre/site/index.phtml',

            'application/site/add'
                => __DIR__ . '/../view/application/parametre/site/add.phtml',

            'application/site/edit'
                => __DIR__ . '/../view/application/parametre/site/edit.phtml',

            'application/site/form'
                => __DIR__ . '/../view/application/parametre/site/form.phtml',

            /*
            |--------------------------------------------------------------------------
            | TYPE D'ÉQUIPEMENT
            |--------------------------------------------------------------------------
            |
            | ATTENTION :
            |
            | Laminas cherche :
            |
            | application/type-equipement/index
            |
            | donc les clés utilisent un TIRET.
            |
            */

            'application/type-equipement/index'
                => __DIR__ . '/../view/application/parametre/type-equipement/index.phtml',

            'application/type-equipement/add'
                => __DIR__ . '/../view/application/parametre/type-equipement/add.phtml',

            'application/type-equipement/edit'
                => __DIR__ . '/../view/application/parametre/type-equipement/edit.phtml',

            'application/type-equipement/form'
                => __DIR__ . '/../view/application/parametre/type-equipement/form.phtml',

            /*
            |--------------------------------------------------------------------------
            | ÉQUIPEMENT
            |--------------------------------------------------------------------------
            */

            'application/equipement/index'
                => __DIR__ . '/../view/application/parametre/equipement/index.phtml',

            'application/equipement/add'
                => __DIR__ . '/../view/application/parametre/equipement/add.phtml',

            'application/equipement/edit'
                => __DIR__ . '/../view/application/parametre/equipement/edit.phtml',

            'application/equipement/view'
                => __DIR__ . '/../view/application/parametre/equipement/view.phtml',

            'application/equipement/form'
                => __DIR__ . '/../view/application/parametre/equipement/form.phtml',

            'application/equipement/historique'
                => __DIR__ . '/../view/application/parametre/equipement/historique.phtml',


            /*
            |--------------------------------------------------------------------------
            | Utilisateur
            |--------------------------------------------------------------------------
            */

            'application/utilisateur/index'
                => __DIR__ . '/../view/application/parametre/utilisateur/index.phtml',

            'application/utilisateur/add'
                => __DIR__ . '/../view/application/parametre/utilisateur/add.phtml',

            'application/utilisateur/edit'
                => __DIR__ . '/../view/application/parametre/utilisateur/edit.phtml',

            'application/utilisateur/form'
                => __DIR__ . '/../view/application/parametre/utilisateur/form.phtml',

            /*
            |--------------------------------------------------------------------------
            | Paramètres
            |--------------------------------------------------------------------------
            */

            'application/parametre/index'
                => __DIR__ . '/../view/application/parametre/index.phtml',

            /*
            |--------------------------------------------------------------------------
            | Maintenance
            |--------------------------------------------------------------------------
            */

            'application/maintenance/index'
                => __DIR__ . '/../view/application/maintenance/index.phtml',

            'application/maintenance/add'
                => __DIR__ . '/../view/application/maintenance/add.phtml',

            'application/maintenance/detail'
                => __DIR__ . '/../view/application/maintenance/detail.phtml',

            'application/maintenance/rapport'
                => __DIR__ . '/../view/application/maintenance/rapport.phtml',

            'application/maintenance/not-found'
                => __DIR__ . '/../view/application/maintenance/not-found.phtml',

            /*
            |--------------------------------------------------------------------------
            | Rapport
            |--------------------------------------------------------------------------
            */

            'application/rapport/index'
                => __DIR__ . '/../view/application/rapport/index.phtml',

            /*
            |--------------------------------------------------------------------------
            | Alerte
            |--------------------------------------------------------------------------
            */

            'application/alerte/index'
                => __DIR__ . '/../view/application/alerte/index.phtml',

            /*
            |--------------------------------------------------------------------------
            | Erreurs
            |--------------------------------------------------------------------------
            */

            'error/404'
                => __DIR__ . '/../view/error/404.phtml',

            'error/index'
                => __DIR__ . '/../view/error/index.phtml',
        ],

        /*
        |--------------------------------------------------------------------------
        | TEMPLATE PATH STACK
        |--------------------------------------------------------------------------
        */

        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
