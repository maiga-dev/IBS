<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Session\Container;

/**
 * Protection CSRF minimale basée sur la session Laminas — un jeton par
 * navigateur, régénéré si absent, comparé en temps constant (hash_equals).
 */
trait CsrfTrait
{
    protected function genererCsrfToken(): string
    {
        $session = new Container('csrf');

        if (empty($session->token)) {
            $session->token = bin2hex(random_bytes(32));
        }

        return $session->token;
    }

    protected function csrfValide(?string $token): bool
    {
        $session = new Container('csrf');

        if ($token === null || empty($session->token)) {
            return false;
        }

        return hash_equals($session->token, $token);
    }
}
