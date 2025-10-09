<?php

namespace Ged\ApiLaravel;

use GuzzleHttp\Client;
use Ged\ApiLaravel\Exceptions\GedApiException;

class GedApiClient
{
    protected Client $http;

    public function __construct(string $baseUri, string $apiKey)
    {
        $this->http = new Client([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'headers' => [
                'X-API-KEY' => $apiKey,
                'Accept' => 'application/json',
            ],
            'timeout' => 60,
        ]);
    }

    /**
     * Inicia o processo de assinatura digital
     */
    public function startSignature(string $pdfBase64, string $policyOid): array
    {
        return $this->post('sign/start', [
            'pdfBase64' => $pdfBase64,
            'policyOid' => $policyOid,
        ]);
    }

    /**
     * Finaliza o processo de assinatura digital
     */
    public function completeSignature(string $pdfId, string $signatureBase64, string $certBase64): array
    {
        return $this->post('sign/complete', [
            'pdfId' => $pdfId,
            'signatureBase64' => $signatureBase64,
            'certBase64' => $certBase64,
        ]);
    }

    /**
     * Verifica a validade de um PDF assinado
     */
    public function verifySignature(string $pdfBase64): array
    {
        return $this->post('sign/verify', [
            'pdfBase64' => $pdfBase64,
        ]);
    }

    /**
     * Método interno para padronizar requisições e erros
     */
    private function post(string $endpoint, array $payload): array
    {
        try {
            $response = $this->http->post($endpoint, ['json' => $payload]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            throw new GedApiException($e->getMessage(), $e->getCode());
        }
    }
}

