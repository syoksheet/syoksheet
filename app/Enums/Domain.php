<?php

namespace App\Enums;

use RuntimeException;

/**
 * The four hosts this application serves. Each one has its own routing group, its own
 * rendering style and its own authentication.
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
     * This throws instead of returning null on purpose. Laravel treats
     * Route::domain(null) as "match any host" rather than as an error, so a missing
     * value would quietly serve this domain's routes on every hostname.
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
