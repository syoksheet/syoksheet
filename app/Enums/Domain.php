<?php

namespace App\Enums;

use RuntimeException;

/**
 * The four hosts one application serves, each with its own routing group, rendering
 * style and authentication. Named for `Route::domain()`, which consumes it, and for
 * `config/domains.php`, which supplies the values.
 */
enum Domain: string
{
    case App = 'app';
    case Admin = 'admin';
    case Api = 'api';
    case Public = 'public';

    /**
     * The hostname this domain answers on, per environment.
     *
     * Throws rather than returning null because `Route::domain(null)` is not an error
     * in Laravel: it means "no host constraint", so an unset value would quietly serve
     * this domain's routes on every hostname. A missing host has to be fatal.
     */
    public function host(): string
    {
        $host = config("domains.{$this->value}");

        if (! is_string($host) || $host === '') {
            throw new RuntimeException(sprintf(
                'No host configured for the %s domain. Set DOMAIN_%s in the environment.',
                $this->value,
                strtoupper($this->value),
            ));
        }

        return $host;
    }
}
