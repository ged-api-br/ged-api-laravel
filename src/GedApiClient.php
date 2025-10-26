<?php

namespace Ged\ApiLaravel;

use Illuminate\Support\Facades\Http;
use Ged\ApiLaravel\Exceptions\GedApiException;

class GedApiClient
{
    protected string $baseUri;
    protected string $apiKey;

    public function __construct(string $baseUri, string $apiKey)
    {
        $this->baseUri = rtrim($baseUri, '/') . '/';
        $this->apiKey = $apiKey;
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
     * Método para padronizar requisições GET
     */
    public function get(string $endpoint, array $query = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'X-API-KEY' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(180)
            ->get($this->baseUri . $endpoint, $query);
            
            if ($response->failed()) {
                throw new GedApiException(
                    $response->json('message') ?? 'Erro na requisição',
                    $response->status()
                );
            }
            
            return $response->json();
            
        } catch (GedApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new GedApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Método para padronizar requisições POST
     */
    public function post(string $endpoint, array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'X-API-KEY' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(300)
            ->post($this->baseUri . $endpoint, $payload);
            
            if ($response->failed()) {
                \Log::error('❌ GED API POST FAILED:', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                ]);
                
                throw new GedApiException(
                    $response->json('message') ?? 'Erro na requisição',
                    $response->status()
                );
            }
            
            $result = $response->json();
            
            // Garantir que sempre retorna array
            if (!is_array($result)) {
                \Log::error('❌ GED API retornou não-array:', [
                    'endpoint' => $endpoint,
                    'result' => $result,
                    'body' => $response->body(),
                ]);
                throw new GedApiException('Resposta inválida da API (não é array)');
            }
            
            return $result;
            
        } catch (GedApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new GedApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    // ===== PAdES (novo fluxo) =====

    /** Prepare (FASE 1) */
    public function padesPrepareFromBase64(string $pdfBase64, bool $visible = false, ?array $anots = null): array
    {
        $payload = [
            'fileBase64' => $pdfBase64,
            'visible' => $visible,
        ];
        if ($anots !== null) {
            $payload['anots'] = $anots; // futuras anotações/visuais
        }
        return $this->post('pades/prepare', $payload);
    }

    /** Prepare com multipart (arquivo local) */
    public function padesPrepareFromFile(string $filePath, bool $visible = false, ?array $anots = null): array
    {
        // Converter arquivo para base64 e usar o método base64 (mais confiável)
        $fileContent = file_get_contents($filePath);
        $fileBase64 = base64_encode($fileContent);
        
        return $this->padesPrepareFromBase64($fileBase64, $visible, $anots);
    }
    
    /**
     * Preparar PDF com visual_data (novo formato v2.4.0)
     * 
     * @param string $filePath Caminho do arquivo PDF
     * @param array $visualData Dados de aparência visual ['rect' => [...], 'signer_name' => ..., etc]
     * @return array Resposta da API
     * @throws GedApiException
     */
    public function padesPrepareFromFileWithVisual(string $filePath, array $visualData): array
    {
        try {
            // Para multipart/form-data com arrays, precisamos enviar cada campo do visual_data separadamente
            // ou enviar fileBase64 ao invés de attach()
            
            // Ler arquivo e converter para base64
            $fileContent = file_get_contents($filePath);
            $fileBase64 = base64_encode($fileContent);
            
            // Montar payload como JSON (não multipart)
            $payload = [
                'fileBase64' => $fileBase64,
                'visible' => true,
                'visual_data' => $visualData  // Enviar como array, não JSON string (inclui background_color)
            ];
            
            // LOG: Visual data sendo enviado para GED API
            \Log::info('🎨 SDK → GED API - Payload Completo:', [
                'url' => $this->baseUri . 'pades/prepare',
                'payload' => [
                    'fileBase64_length' => strlen($fileBase64),
                    'visible' => true,
                    'visual_data' => $visualData
                ]
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'X-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(300) // 5 minutos para arquivos grandes (TESTE)
            ->post($this->baseUri . 'pades/prepare', $payload);
            
            \Log::info('📥 GED API → SDK - Resposta:', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);
            
            if ($response->failed()) {
                \Log::error('❌ GED API ERROR:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json()
                ]);
                
                throw new GedApiException(
                    $response->json('message') ?? 'Erro na requisição',
                    $response->status()
                );
            }
            
            return $response->json();
            
        } catch (GedApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new GedApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** CMS Params (FASE 2) */
    public function padesCmsParams(string $documentId, string $signerCertDerBase64, ?string $fieldName = null): array
    {
        $payload = [
            'document_id' => $documentId,
            'signer_cert_der_base64' => $signerCertDerBase64,
        ];
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

    // ===== CERTIFICADOS =====

    /**
     * Extrair chave pública de um certificado
     * 
     * Suporta múltiplos formatos: PFX, P12, PEM, CER, DER, CRT
     * 
     * @param string $certificateContent - Conteúdo binário do certificado
     * @param string|null $password - Senha (obrigatória para PFX/P12)
     * @param string|null $fileName - Nome do arquivo (para detectar formato)
     * @return array - ['success' => true, 'data' => ['public_key_der_base64' => '...']]
     */
    public function extractPublicKey(string $certificateContent, ?string $password = null, ?string $fileName = null): array
    {
        $payload = [
            'certificateBase64' => base64_encode($certificateContent),
        ];

        if ($password) {
            $payload['password'] = $password;
        }

        if ($fileName) {
            $payload['fileName'] = $fileName;
        }

        return $this->post('certificate/extract-public-key', $payload);
    }

    /**
     * Extrair chave pública de um arquivo de certificado
     * 
     * @param string $filePath - Caminho do arquivo
     * @param string|null $password - Senha (obrigatória para PFX/P12)
     * @return array - ['success' => true, 'data' => ['public_key_der_base64' => '...']]
     */
    public function extractPublicKeyFromFile(string $filePath, ?string $password = null): array
    {
        if (!file_exists($filePath)) {
            throw new GedApiException("Arquivo não encontrado: {$filePath}", 404);
        }

        $content = file_get_contents($filePath);
        $fileName = basename($filePath);

        return $this->extractPublicKey($content, $password, $fileName);
    }

    /** Inject (FASE 3) com assinatura crua PKCS#1 e certificado do signatário */
    public function padesInjectPkcs1(
        string $documentId,
        string $fieldName,
        string $signaturePkcs1DerHex,
        string $signerCertDerBase64,
        ?array $signerChainDerBase64 = null
    ): array {
        $payload = [
            'document_id' => $documentId,
            'field_name' => $fieldName,
            'signature_pkcs1_der_hex' => $signaturePkcs1DerHex,
            'signer_cert_der_base64' => $signerCertDerBase64,
        ];
        if ($signerChainDerBase64) {
            $payload['signer_chain_der_base64'] = $signerChainDerBase64;
        }
        return $this->post('pades/inject', $payload);
    }
}

