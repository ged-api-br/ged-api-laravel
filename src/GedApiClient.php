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

    // ===== ASSINATURA DIGITAL =====

    /**
     * Assinar PDF com certificado A1 (PFX/P12)
     *
     * @param string $pdfPath Caminho do arquivo PDF
     * @param string $pfxPath Caminho do certificado PFX/P12
     * @param string $password Senha do certificado
     * @param array|null $visualData Dados visuais da assinatura (opcional)
     * @return array ['success', 'signed_pdf_base64', 'download_url']
     */
    public function sign(string $pdfPath, string $pfxPath, string $password, ?array $visualData = null): array
    {
        if (!file_exists($pdfPath)) {
            throw new GedApiException("PDF nao encontrado: {$pdfPath}");
        }
        if (!file_exists($pfxPath)) {
            throw new GedApiException("Certificado nao encontrado: {$pfxPath}");
        }

        // Extrair certificado publico e chave privada do PFX (local)
        $pfxContent = file_get_contents($pfxPath);
        $certs = [];
        if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new GedApiException('Falha ao abrir certificado. Verifique a senha.');
        }

        $privateKey = openssl_pkey_get_private($certs['pkey']);
        if (!$privateKey) {
            throw new GedApiException('Falha ao extrair chave privada do certificado.');
        }

        // Converter cert PEM para DER base64
        openssl_x509_export($certs['cert'], $certPemExport);
        preg_match('/-----BEGIN CERTIFICATE-----(.+?)-----END CERTIFICATE-----/s', $certPemExport, $matches);
        $certDerBase64 = str_replace(["\r", "\n", " "], '', $matches[1] ?? '');

        // Etapa 1: Enviar PDF + cert publico ao servidor
        $startPayload = [
            'fileBase64' => base64_encode(file_get_contents($pdfPath)),
            'signerCertBase64' => $certDerBase64,
        ];
        if ($visualData !== null) {
            $startPayload['visible'] = true;
            $startPayload['visual_data'] = $visualData;
        }

        $startResult = $this->post('pades/sign/start', $startPayload);

        if (!($startResult['success'] ?? false)) {
            throw new GedApiException($startResult['message'] ?? 'Erro ao iniciar assinatura');
        }

        // Etapa 2: Assinar hash localmente (chave privada nunca sai)
        $digest = hex2bin($startResult['hash']);
        $digestInfo = hex2bin('3031300d060960864801650304020105000420') . $digest;

        $signature = '';
        if (!openssl_private_encrypt($digestInfo, $signature, $privateKey, OPENSSL_PKCS1_PADDING)) {
            throw new GedApiException('Falha ao assinar com a chave privada.');
        }

        // Etapa 3: Enviar assinatura ao servidor
        $completeResult = $this->post('pades/sign/complete', [
            'token' => $startResult['token'],
            'signature' => bin2hex($signature),
        ]);

        if (!($completeResult['success'] ?? false)) {
            throw new GedApiException($completeResult['message'] ?? 'Erro ao completar assinatura');
        }

        return $completeResult;
    }

    /**
     * Assinar PDF a partir de conteudo base64
     *
     * @param string $pdfBase64 PDF em base64
     * @param string $pfxContent Conteudo binario do PFX
     * @param string $password Senha do certificado
     * @param array|null $visualData Dados visuais da assinatura (opcional)
     * @return array ['success', 'signed_pdf_base64', 'download_url']
     */
    public function signFromBase64(string $pdfBase64, string $pfxContent, string $password, ?array $visualData = null): array
    {
        $tempPfx = tempnam(sys_get_temp_dir(), 'pfx_');
        $tempPdf = tempnam(sys_get_temp_dir(), 'pdf_');

        try {
            file_put_contents($tempPfx, $pfxContent);
            file_put_contents($tempPdf, base64_decode($pdfBase64));
            return $this->sign($tempPdf, $tempPfx, $password, $visualData);
        } finally {
            @unlink($tempPfx);
            @unlink($tempPdf);
        }
    }

    // ===== EMISSAO DE CERTIFICADOS =====

    /**
     * Emitir certificado digital
     */
    public function issueCertificate(string $name, string $cpf, string $email, string $password, ?int $validityDays = null): array
    {
        $payload = compact('name', 'cpf', 'email', 'password');
        if ($validityDays !== null) {
            $payload['validity_days'] = $validityDays;
        }
        return $this->post('certificate/issue', $payload);
    }

    /**
     * Listar certificados emitidos
     */
    public function listCertificates(int $page = 1, int $perPage = 15, ?string $status = null, ?string $search = null): array
    {
        $query = ['page' => $page, 'per_page' => $perPage];
        if ($status) { $query['status'] = $status; }
        if ($search) { $query['search'] = $search; }
        return $this->get('certificate/list', $query);
    }

    /**
     * Revogar certificado por serial
     */
    public function revokeCertificate(string $serial, ?string $reason = null): array
    {
        $payload = ['serial' => $serial];
        if ($reason) { $payload['reason'] = $reason; }
        return $this->post('certificate/revoke', $payload);
    }

    /**
     * Verificar validade de um certificado
     */
    public function verifyCertificate(string $serial): array
    {
        return $this->get("certificate/verify/{$serial}");
    }

    // ===== HTTP =====

    private function get(string $endpoint, array $query = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(300)
            ->get($this->baseUri . $endpoint, $query);

            if ($response->failed()) {
                throw new GedApiException(
                    $response->json('message') ?? 'Erro na requisicao',
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

    private function post(string $endpoint, array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(300)
            ->post($this->baseUri . $endpoint, $payload);

            if ($response->failed()) {
                throw new GedApiException(
                    $response->json('message') ?? 'Erro na requisicao',
                    $response->status()
                );
            }

            $result = $response->json();
            if (!is_array($result)) {
                throw new GedApiException('Resposta invalida da API');
            }

            return $result;
        } catch (GedApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new GedApiException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
