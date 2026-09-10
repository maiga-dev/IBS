<?php

declare(strict_types=1);

namespace Application;

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\Session as SessionStorage;
use Laminas\EventManager\EventInterface;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\Mvc\MvcEvent;

class Module implements BootstrapListenerInterface
{
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';

        return $config;
    }

    /**
     * Configuration des services.
     */
    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                AuthenticationService::class => function ($container) {
                    /*
                     * Stockage uniquement en session.
                     *
                     * Le cookie de session n'est pas persistant :
                     * il disparaît lorsque le navigateur est fermé.
                     */
                    $storage = new SessionStorage(
                        'IBS-Pulse',
                        'auth'
                    );

                    return new AuthenticationService($storage);
                },
            ],
        ];
    }

    /**
     * Branche le contrôle d'authentification
     * sur chaque requête.
     */
    public function onBootstrap(EventInterface $e): void
    {
        /** @var MvcEvent $e */
        $eventManager = $e->getApplication()->getEventManager();

        $eventManager->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'verifierAuthentification'],
            -100
        );
    }

    public function verifierAuthentification(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();

        if (! $routeMatch) {
            return null;
        }

        // Login, inscription et déconnexion restent accessibles.
        if ($routeMatch->getMatchedRouteName() === 'auth') {
            return null;
        }

        /** @var AuthenticationService $authService */
        $authService = $e->getApplication()
            ->getServiceManager()
            ->get(AuthenticationService::class);

        if ($authService->hasIdentity()) {
            return null;
        }

        $router = $e->getRouter();
        $url = $router->assemble([], ['name' => 'auth']);

        $response = $e->getResponse();
        $response->getHeaders()->addHeaderLine(
            'Location',
            $url
        );
        $response->setStatusCode(302);

        $e->stopPropagation(true);

        return $response;
    }
}
