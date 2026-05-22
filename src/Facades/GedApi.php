<?php

namespace Ged\ApiLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array sign(string $pdfPath, string $pfxPath, string $password, ?array $options = null)
 * @method static array signFromBase64(string $pdfBase64, string $pfxContent, string $password, ?array $options = null)
 * @method static array issueCertificate(string $name, string $cpf, string $email, string $password, ?int $validityDays = null)
 * @method static array listCertificates(int $page = 1, int $perPage = 15, ?string $status = null, ?string $search = null)
 * @method static array revokeCertificate(string $serial, ?string $reason = null)
 * @method static array verifyCertificate(string $serial)
 *
 * @see \Ged\ApiLaravel\GedApiClient
 */
class GedApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ged-api';
    }
}
