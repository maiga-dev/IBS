<?php

declare(strict_types=1);

namespace Application\Model;

use ArrayObject;

/**
 * Selon l'environnement (version du driver PDO, config de l'Adapter),
 * Laminas\Db peut renvoyer ->current() sous forme d'ArrayObject plutôt
 * que de tableau PHP simple. Ce trait normalise systématiquement vers
 * un tableau (ou null si la ligne n'existe pas), pour que tout le code
 * qui déclare un retour ?array reste fiable quel que soit l'environnement.
 */
trait RowHydrationTrait
{
    protected function normaliserLigne(mixed $ligne): ?array
    {
        if ($ligne === false || $ligne === null) {
            return null;
        }

        if ($ligne instanceof ArrayObject) {
            return $ligne->getArrayCopy();
        }

        return (array) $ligne;
    }
}
