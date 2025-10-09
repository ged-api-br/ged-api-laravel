<?php

namespace Ged\ApiLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array startSignature(string $pdfBase64, string $policyOid)
 * @method static array completeSignature(string $pdfId, string $signatureBase64, string $certBase64)
 * @method static array verifySignature(string $pdfBase64)
 *
 * @see \Ged\ApiClient\GedApiClient
 */
class GedApi extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'ged-api';
    }
}

