<?php

namespace App\Models;

/**
 * @deprecated Use Company instead.
 *
 * Compatibility alias only: this model deliberately uses the SAME `companies`
 * table so no code path can read/write a separate transport_names master.
 */
class TransportName extends Company
{
    protected $table = 'companies';
}
