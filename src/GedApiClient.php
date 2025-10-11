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
                // Preferimos Bearer; manter compat X-API-KEY se necessário
                'Authorization' => 'Bearer ' . $apiKey,
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

    // ===== PAdES (novo fluxo) =====

    /** Prepare (FASE 1) */
    public function padesPrepareFromBase64(string $pdfBase64, bool $visible = false): array
    {
        return $this->post('pades/prepare', [
            'fileBase64' => $pdfBase64,
            'visible' => $visible,
        ]);
    }

    /** Prepare com multipart (arquivo local) */
    public function padesPrepareFromFile(string $filePath, bool $visible = false): array
    {
        try {
            $multipart = [
                ['name' => 'file', 'contents' => fopen($filePath, 'r'), 'filename' => basename($filePath)],
                ['name' => 'visible', 'contents' => $visible ? '1' : '0'],
            ];
            $response = $this->http->post('pades/prepare', ['multipart' => $multipart]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            throw new GedApiException($e->getMessage(), $e->getCode());
        }
    }

    /** CMS Params (FASE 2) */
    public function padesCmsParams(string $documentId, ?string $fieldName = null): array
    {
        $payload = ['document_id' => $documentId];
        if ($fieldName) { $payload['field_name'] = $fieldName; }
        return $this->post('pades/cms-params', $payload);
    }

    /** Inject (FASE 3) */
    public function padesInject(string $documentId, string $fieldName, string $signatureDerHex): array
    {
        return $this->post('pades/inject', [
            'document_id' => $documentId,
            'field_name' => $fieldName,
            'signature_der_hex' => $signatureDerHex,
        ]);
    }

    /** Finalize (FASE 4) */
    public function padesFinalize(string $documentId): array
    {
        return $this->post('pades/finalize', ['document_id' => $documentId]);
    }
}

