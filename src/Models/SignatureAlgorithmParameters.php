<?php

namespace Ged\ApiLaravel\Models;

/**
 * ============================================================================
 * SignatureAlgorithmParameters - Parâmetros de Algoritmo de Assinatura
 * ============================================================================
 *
 * Representa os parâmetros retornados pela API na FASE 1 (start) do processo
 * de assinatura PAdES.
 *
 * Estes parâmetros são necessários para que o cliente:
 * 1. Assine o hash localmente (FASE 2)
 * 2. Envie a assinatura de volta para finalizar (FASE 3)
 *
 * Compatível com:
 * - Padrões ICP-Brasil (DOC-ICP-15.03 e 15.04)
 * - Adobe PDF Signatures
 * ============================================================================
 */
class SignatureAlgorithmParameters
{
    /**
     * Token único que identifica a sessão de assinatura
     * @var string
     */
    public string $token;
    
    /**
     * Dados que serão assinados (em base64)
     * Geralmente os SignedAttributes em formato DER
     * @var string
     */
    public string $toSignData;
    
    /**
     * Hash dos dados a serem assinados (em base64)
     * Este é o valor que será assinado com a chave privada
     * @var string
     */
    public string $toSignHash;
    
    /**
     * OID do algoritmo de digest (hash)
     * Ex: 2.16.840.1.101.3.4.2.1 (SHA-256)
     * @var string
     */
    public string $digestAlgorithmOid;
    
    /**
     * Algoritmo OpenSSL para usar com openssl_sign()
     * Ex: OPENSSL_ALGO_SHA256
     * @var int
     */
    public int $openSslSignatureAlgorithm;
    
    /**
     * Informações do certificado extraídas (opcional)
     * @var array|null
     */
    public ?array $certificate = null;
    
    /**
     * Argumentos adicionais (callback, metadata, etc.)
     * @var mixed
     */
    public $callbackArgument = null;
    
    /**
     * Cria uma instância a partir da resposta da API
     * 
     * @param array $response Resposta da API (POST /api/pades/start)
     * @return self
     */
    public static function fromApiResponse(array $response): self
    {
        $params = new self();
        
        $params->token = $response['token'] ?? '';
        $params->toSignData = $response['toSignData'] ?? '';
        $params->toSignHash = $response['toSignHash'] ?? '';
        $params->digestAlgorithmOid = $response['digestAlgorithmOid'] ?? '';
        $params->openSslSignatureAlgorithm = self::getOpenSslAlgorithm($response['digestAlgorithmOid'] ?? '');
        $params->certificate = $response['certificate'] ?? null;
        $params->callbackArgument = $response['callbackArgument'] ?? null;
        
        return $params;
    }
    
    /**
     * Converte para array (útil para serialização)
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'toSignData' => $this->toSignData,
            'toSignHash' => $this->toSignHash,
            'digestAlgorithmOid' => $this->digestAlgorithmOid,
            'openSslSignatureAlgorithm' => $this->openSslSignatureAlgorithm,
            'certificate' => $this->certificate,
            'callbackArgument' => $this->callbackArgument,
        ];
    }
    
    /**
     * Retorna os dados para assinar em formato bruto (decoded base64)
     * 
     * @return string Dados brutos
     */
    public function getToSignDataRaw(): string
    {
        return base64_decode($this->toSignData);
    }
    
    /**
     * Retorna o hash para assinar em formato bruto (decoded base64)
     * 
     * @return string Hash bruto
     */
    public function getToSignHashRaw(): string
    {
        return base64_decode($this->toSignHash);
    }
    
    /**
     * Retorna o nome do algoritmo de digest
     * 
     * @return string Nome do algoritmo (SHA-256, SHA-384, etc.)
     */
    public function getDigestAlgorithmName(): string
    {
        $algorithms = [
            '1.3.14.3.2.26' => 'SHA-1',
            '2.16.840.1.101.3.4.2.1' => 'SHA-256',
            '2.16.840.1.101.3.4.2.2' => 'SHA-384',
            '2.16.840.1.101.3.4.2.3' => 'SHA-512',
        ];
        
        return $algorithms[$this->digestAlgorithmOid] ?? 'Desconhecido';
    }
    
    /**
     * Converte OID do algoritmo de digest para constante OpenSSL
     * 
     * @param string $digestOid OID do algoritmo de digest
     * @return int Constante OPENSSL_ALGO_*
     */
    private static function getOpenSslAlgorithm(string $digestOid): int
    {
        $algorithms = [
            '1.3.14.3.2.26' => OPENSSL_ALGO_SHA1,
            '2.16.840.1.101.3.4.2.1' => OPENSSL_ALGO_SHA256,
            '2.16.840.1.101.3.4.2.2' => OPENSSL_ALGO_SHA384,
            '2.16.840.1.101.3.4.2.3' => OPENSSL_ALGO_SHA512,
        ];
        
        return $algorithms[$digestOid] ?? OPENSSL_ALGO_SHA256;
    }
    
    /**
     * Valida se todos os campos obrigatórios estão presentes
     * 
     * @return bool True se válido
     */
    public function isValid(): bool
    {
        return !empty($this->token) 
            && !empty($this->toSignData)
            && !empty($this->toSignHash)
            && !empty($this->digestAlgorithmOid);
    }
    
    /**
     * Retorna informações do certificado do signatário
     * 
     * @return array|null Informações do certificado
     */
    public function getCertificateInfo(): ?array
    {
        return $this->certificate;
    }
    
    /**
     * Retorna o nome do signatário extraído do certificado
     * 
     * @return string|null Nome do signatário
     */
    public function getSignerName(): ?string
    {
        return $this->certificate['subjectName'] ?? null;
    }
    
    /**
     * Retorna o email do signatário extraído do certificado
     * 
     * @return string|null Email do signatário
     */
    public function getSignerEmail(): ?string
    {
        return $this->certificate['emailAddress'] ?? null;
    }
    
    /**
     * Retorna a validade do certificado
     * 
     * @return array|null ['start' => string, 'end' => string]
     */
    public function getCertificateValidity(): ?array
    {
        if (!isset($this->certificate['validityStart']) || !isset($this->certificate['validityEnd'])) {
            return null;
        }
        
        return [
            'start' => $this->certificate['validityStart'],
            'end' => $this->certificate['validityEnd'],
        ];
    }
    
    /**
     * Verifica se o certificado está dentro do período de validade
     * 
     * @return bool True se válido
     */
    public function isCertificateValid(): bool
    {
        $validity = $this->getCertificateValidity();
        
        if (!$validity) {
            return false;
        }
        
        $now = time();
        $start = strtotime($validity['start']);
        $end = strtotime($validity['end']);
        
        return $now >= $start && $now <= $end;
    }
    
    /**
     * Retorna representação em string para debug
     * 
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            "SignatureAlgorithmParameters(token=%s, algorithm=%s, signer=%s)",
            substr($this->token, 0, 8) . '...',
            $this->getDigestAlgorithmName(),
            $this->getSignerName() ?? 'Desconhecido'
        );
    }
}

