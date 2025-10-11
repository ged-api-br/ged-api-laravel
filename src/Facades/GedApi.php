<?php

namespace Ged\ApiLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array startSignature(string $pdfBase64, string $policyOid)
 * @method static array completeSignature(string $pdfId, string $signatureBase64, string $certBase64)
 * @method static array verifySignature(string $pdfBase64)
 *
 * // PAdES
 * @method static array padesPrepareFromBase64(string $pdfBase64, bool $visible = false)
 * @method static array padesPrepareFromFile(string $filePath, bool $visible = false)
 * @method static array padesCmsParams(string $documentId, ?string $fieldName = null)
 * @method static array padesInject(string $documentId, string $fieldName, string $signatureDerHex)
 * @method static array padesFinalize(string $documentId)
 *
 * @see \Ged\ApiLaravel\GedApiClient
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

