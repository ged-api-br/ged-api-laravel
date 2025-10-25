<?php

namespace Ged\ApiLaravel;

use Ged\ApiLaravel\GedApiClient;
use Ged\ApiLaravel\Models\FileReference;
use Ged\ApiLaravel\Models\SignatureAlgorithmParameters;
use Ged\ApiLaravel\Constants\StandardSignaturePolicies;
use Ged\ApiLaravel\Exceptions\GedApiException;

/**
 * ============================================================================
 * PadesSignatureStarter - Iniciador de Assinatura PAdES (FASE 1)
 * ============================================================================
 *
 * Responsável pela primeira fase do processo de assinatura PAdES em 3 fases:
 * 
 * FASE 1 (esta classe):
 *   - Envia o PDF para o servidor
 *   - Envia o certificado público do signatário
 *   - Define a política de assinatura
 *   - Recebe o token e o hash para assinar
 *
 * Fluxo de uso:
 *   1. Criar instância: new PadesSignatureStarter($client)
 *   2. Configurar PDF: setPdfToSignFromPath() ou setPdfToSignFromContent*()
 *   3. Configurar certificado: setSignerCertificateRaw() ou setSignerCertificateBase64()
 *   4. Configurar política: setSignaturePolicy()
 *   5. Iniciar: $params = start()
 *   6. Usar $params para assinar localmente (FASE 2)
 *
 * Padrão de 3 fases - Máxima segurança para assinatura digital
 * ============================================================================
 */
class PadesSignatureStarter
{
    /**
     * Cliente da API
     * @var GedApiClient
     */
    private GedApiClient $client;
    
    /**
     * Referência ao PDF a ser assinado
     * @var FileReference|null
     */
    private ?FileReference $pdfToSign = null;
    
    /**
     * Certificado do signatário (formato DER)
     * @var string|null
     */
    private ?string $signerCertificate = null;
    
    /**
     * Política de assinatura
     * @var string
     */
    private string $signaturePolicy = StandardSignaturePolicies::PADES_BASIC;
    
    /**
     * Representação visual da assinatura (opcional)
     * @var array|null
     */
    private ?array $visualRepresentation = null;
    
    /**
     * ID do contexto de segurança (opcional)
     * @var string|null
     */
    private ?string $securityContextId = null;
    
    /**
     * Argumento de callback (opcional)
     * Será retornado na resposta sem modificações
     * @var mixed
     */
    private $callbackArgument = null;
    
    /**
     * Construtor
     * 
     * @param GedApiClient $client Cliente da API configurado
     */
    public function __construct(GedApiClient $client)
    {
        $this->client = $client;
    }
    
    // ========== Configuração do PDF ==========
    
    /**
     * Define o PDF a partir de um caminho de arquivo
     * 
     * @param string $path Caminho do arquivo PDF
     * @return self Para encadeamento de métodos
     * @throws GedApiException Se o arquivo não existir ou não for PDF
     */
    public function setPdfToSignFromPath(string $path): self
    {
        $this->pdfToSign = FileReference::fromFile($path);
        
        if (!$this->pdfToSign->isPdf()) {
            throw new GedApiException("O arquivo especificado não é um PDF válido");
        }
        
        return $this;
    }
    
    /**
     * Define o PDF a partir de conteúdo em base64
     * 
     * @param string $base64 Conteúdo do PDF em base64
     * @return self Para encadeamento de métodos
     */
    public function setPdfToSignFromContentBase64(string $base64): self
    {
        $this->pdfToSign = FileReference::fromContentBase64($base64);
        return $this;
    }
    
    /**
     * Define o PDF a partir de conteúdo bruto (binário)
     * 
     * @param string $content Conteúdo bruto do PDF
     * @return self Para encadeamento de métodos
     */
    public function setPdfToSignFromContentRaw(string $content): self
    {
        $this->pdfToSign = FileReference::fromContentRaw($content);
        return $this;
    }
    
    /**
     * Define o PDF a partir de resultado de operação anterior
     * 
     * @param string $token Token do resultado anterior
     * @return self Para encadeamento de métodos
     */
    public function setPdfToSignFromResult(string $token): self
    {
        $this->pdfToSign = FileReference::fromResult($token);
        return $this;
    }
    
    /**
     * Define o PDF a partir de URL remota
     * 
     * @param string $url URL do PDF
     * @return self Para encadeamento de métodos
     */
    public function setPdfToSignFromUrl(string $url): self
    {
        $this->pdfToSign = FileReference::fromUrl($url);
        return $this;
    }
    
    // ========== Configuração do Certificado ==========
    
    /**
     * Define o certificado do signatário (formato DER bruto)
     * 
     * @param string $certDer Certificado em formato DER (binário)
     * @return self Para encadeamento de métodos
     */
    public function setSignerCertificateRaw(string $certDer): self
    {
        $this->signerCertificate = $certDer;
        return $this;
    }
    
    /**
     * Define o certificado do signatário (formato base64)
     * 
     * @param string $certBase64 Certificado em base64
     * @return self Para encadeamento de métodos
     */
    public function setSignerCertificateBase64(string $certBase64): self
    {
        $this->signerCertificate = base64_decode($certBase64);
        return $this;
    }
    
    /**
     * Define o certificado do signatário (formato PEM)
     * 
     * @param string $certPem Certificado em formato PEM
     * @return self Para encadeamento de métodos
     */
    public function setSignerCertificatePem(string $certPem): self
    {
        // Remover header/footer e decodificar
        $pem = str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r", ' '],
            '',
            $certPem
        );
        
        $this->signerCertificate = base64_decode($pem);
        return $this;
    }
    
    /**
     * Define o certificado a partir de arquivo
     * 
     * @param string $path Caminho do arquivo do certificado (.cer, .crt, .der)
     * @return self Para encadeamento de métodos
     * @throws GedApiException Se o arquivo não existir
     */
    public function setSignerCertificateFromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new GedApiException("Arquivo de certificado não encontrado: {$path}");
        }
        
        $content = file_get_contents($path);
        
        if ($content === false) {
            throw new GedApiException("Erro ao ler arquivo de certificado");
        }
        
        // Detectar se é PEM ou DER
        if (str_contains($content, '-----BEGIN CERTIFICATE-----')) {
            $this->setSignerCertificatePem($content);
        } else {
            $this->setSignerCertificateRaw($content);
        }
        
        return $this;
    }
    
    // ========== Configuração da Política ==========
    
    /**
     * Define a política de assinatura
     * 
     * @param string $policyId ID da política (use constantes de StandardSignaturePolicies)
     * @return self Para encadeamento de métodos
     * 
     * @example
     * $starter->setSignaturePolicy(StandardSignaturePolicies::PADES_ICPBR_ADR_BASICA);
     */
    public function setSignaturePolicy(string $policyId): self
    {
        $this->signaturePolicy = $policyId;
        return $this;
    }
    
    // ========== Configuração da Representação Visual (Opcional) ==========
    
    /**
     * Define a representação visual da assinatura
     * 
     * @param array $visual Configuração da representação visual
     * @return self Para encadeamento de métodos
     * 
     * @example
     * $starter->setVisualRepresentation([
     *     'text' => [
     *         'text' => 'Assinado digitalmente por {{name}}',
     *         'fontSize' => 10,
     *         'includeSigningTime' => true,
     *     ],
     *     'position' => [
     *         'pageNumber' => -1, // última página
     *         'auto' => 'newPage', // ou 'leftMargin', 'rightMargin'
     *     ],
     * ]);
     */
    public function setVisualRepresentation(array $visual): self
    {
        $this->visualRepresentation = $visual;
        return $this;
    }
    
    /**
     * Define representação visual simples (texto apenas)
     * 
     * @param string $text Texto da assinatura (suporta placeholders: {{name}}, {{date}})
     * @param int $fontSize Tamanho da fonte (padrão: 10)
     * @return self Para encadeamento de métodos
     */
    public function setSimpleVisualRepresentation(string $text = 'Assinado digitalmente por {{name}}', int $fontSize = 10): self
    {
        $this->visualRepresentation = [
            'text' => [
                'text' => $text,
                'fontSize' => $fontSize,
                'includeSigningTime' => true,
            ],
            'position' => [
                'pageNumber' => -1, // última página
                'auto' => 'newPage',
            ],
        ];
        
        return $this;
    }
    
    /**
     * Define representação visual com coordenadas específicas (retângulo)
     * 
     * @param array $rect Coordenadas do retângulo ['x' => float, 'y' => float, 'width' => float, 'height' => float, 'page' => int]
     * @param string|null $signerName Nome do assinante (opcional)
     * @param string|null $reason Razão da assinatura (opcional)
     * @param string|null $location Localização (opcional)
     * @param string|null $contact Contato (opcional)
     * @param bool $showSignerName Mostrar nome do assinante (padrão: true)
     * @param bool $showDate Mostrar data (padrão: true)
     * @param bool $showReason Mostrar razão (padrão: true)
     * @return self Para encadeamento de métodos
     * 
     * @example
     * $starter->setVisualRepresentationWithRect([
     *     'x' => 100,
     *     'y' => 200,
     *     'width' => 200,
     *     'height' => 80,
     *     'page' => 1
     * ], 'João Silva', 'Aprovação do documento', 'São Paulo', 'joao@example.com');
     */
    public function setVisualRepresentationWithRect(
        array $rect,
        ?string $signerName = null,
        ?string $reason = null,
        ?string $location = null,
        ?string $contact = null,
        bool $showSignerName = true,
        bool $showDate = true,
        bool $showReason = true
    ): self
    {
        $this->visualRepresentation = [
            'rect' => $rect,
            'signer_name' => $signerName,
            'reason' => $reason,
            'location' => $location,
            'contact' => $contact,
            'show_signer_name' => $showSignerName,
            'show_date' => $showDate,
            'show_reason' => $showReason,
        ];
        
        return $this;
    }
    
    // ========== Configuração Adicional ==========
    
    /**
     * Define o ID do contexto de segurança
     * 
     * @param string $contextId ID do contexto de segurança
     * @return self Para encadeamento de métodos
     */
    public function setSecurityContext(string $contextId): self
    {
        $this->securityContextId = $contextId;
        return $this;
    }
    
    /**
     * Define argumento de callback (será retornado sem modificações)
     * 
     * @param mixed $argument Qualquer dado que você queira receber de volta
     * @return self Para encadeamento de métodos
     */
    public function setCallbackArgument($argument): self
    {
        $this->callbackArgument = $argument;
        return $this;
    }
    
    // ========== Execução ==========
    
    /**
     * Inicia o processo de assinatura (FASE 1)
     * 
     * Envia PDF e certificado para o servidor, que:
     * 1. Prepara o PDF (adiciona campo de assinatura)
     * 2. Calcula o hash dos bytes cobertos pela assinatura
     * 3. Cria uma sessão (token) para rastrear o processo
     * 4. Retorna os parâmetros necessários para assinatura local
     * 
     * @return SignatureAlgorithmParameters Parâmetros para a FASE 2 (assinatura local)
     * @throws GedApiException Se configuração estiver incompleta ou erro na API
     */
    public function start(): SignatureAlgorithmParameters
    {
        // Validar configuração
        $this->validate();
        
        // Preparar payload
        $payload = [
            'pdfToSign' => $this->pdfToSign->getContentBase64(),
            'certificate' => base64_encode($this->signerCertificate),
            'signaturePolicyId' => $this->signaturePolicy,
        ];
        
        // Adicionar campos opcionais
        if ($this->visualRepresentation !== null) {
            $payload['visualRepresentation'] = $this->visualRepresentation;
        }
        
        if ($this->securityContextId !== null) {
            $payload['securityContextId'] = $this->securityContextId;
        }
        
        if ($this->callbackArgument !== null) {
            $payload['callbackArgument'] = $this->callbackArgument;
        }
        
        // Fazer requisição
        try {
            $response = $this->client->post('pades/prepare', $payload);
        } catch (\Throwable $e) {
            throw new GedApiException("Erro ao iniciar assinatura: " . $e->getMessage(), $e->getCode(), $e);
        }
        
        // Validar resposta
        if (!isset($response['success']) || $response['success'] !== true) {
            $errorMessage = $response['message'] ?? 'Erro desconhecido';
            throw new GedApiException("Falha ao iniciar assinatura: {$errorMessage}");
        }
        
        // Criar objeto de parâmetros
        return SignatureAlgorithmParameters::fromApiResponse($response);
    }
    
    /**
     * Valida se a configuração está completa
     * 
     * @throws GedApiException Se alguma configuração obrigatória estiver faltando
     */
    private function validate(): void
    {
        if ($this->pdfToSign === null) {
            throw new GedApiException("PDF não foi configurado. Use setPdfToSignFromPath() ou métodos similares.");
        }
        
        if ($this->signerCertificate === null) {
            throw new GedApiException("Certificado do signatário não foi configurado. Use setSignerCertificateRaw() ou métodos similares.");
        }
        
        if (empty($this->signaturePolicy)) {
            throw new GedApiException("Política de assinatura não foi configurada. Use setSignaturePolicy().");
        }
    }
    
    /**
     * Retorna o tamanho do PDF em bytes
     * 
     * @return int|null Tamanho em bytes ou null se PDF não configurado
     */
    public function getPdfSize(): ?int
    {
        return $this->pdfToSign !== null ? $this->pdfToSign->getSize() : null;
    }
    
    /**
     * Retorna o tamanho do PDF formatado
     * 
     * @return string|null Tamanho formatado ou null se PDF não configurado
     */
    public function getPdfFormattedSize(): ?string
    {
        return $this->pdfToSign !== null ? $this->pdfToSign->getFormattedSize() : null;
    }
    
    /**
     * Retorna o nome da política de assinatura configurada
     * 
     * @return string Nome da política
     */
    public function getSignaturePolicyName(): string
    {
        return StandardSignaturePolicies::getName($this->signaturePolicy);
    }
    
    /**
     * Verifica se a política requer timestamp
     * 
     * @return bool True se requer timestamp
     */
    public function requiresTimestamp(): bool
    {
        return StandardSignaturePolicies::requiresTimestamp($this->signaturePolicy);
    }
}

