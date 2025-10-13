<?php

namespace Ged\ApiLaravel\Support;

use Ged\ApiLaravel\Exceptions\GedApiException;
use phpseclib3\File\X509;

/**
 * ============================================================================
 * CertificateHelper - Auxiliar para Certificados Digitais
 * ============================================================================
 *
 * Utilitário para trabalhar com certificados digitais X.509:
 * - Carregar certificados A1 (PFX/P12)
 * - Extrair informações (nome, CPF, email, validade)
 * - Converter entre formatos (DER, PEM, base64)
 * - Validar certificados
 *
 * Suporta certificados ICP-Brasil padrão A1 e A3
 * ============================================================================
 */
class CertificateHelper
{
    /**
     * Carrega certificado de arquivo PFX/P12 (A1)
     * 
     * @param string $pfxPath Caminho do arquivo PFX
     * @param string $password Senha do certificado
     * @return array ['certificate' => string (DER), 'privateKey' => resource, 'chain' => array]
     * @throws GedApiException Se não conseguir carregar
     */
    public function loadPfx(string $pfxPath, string $password): array
    {
        if (!file_exists($pfxPath)) {
            throw new GedApiException("Arquivo PFX não encontrado: {$pfxPath}");
        }
        
        $pfxContent = file_get_contents($pfxPath);
        
        if ($pfxContent === false) {
            throw new GedApiException("Erro ao ler arquivo PFX");
        }
        
        return $this->loadPfxFromContent($pfxContent, $password);
    }
    
    /**
     * Carrega certificado de conteúdo PFX/P12
     * 
     * @param string $pfxContent Conteúdo do PFX
     * @param string $password Senha do certificado
     * @return array ['certificate' => string (DER), 'privateKey' => resource, 'chain' => array]
     * @throws GedApiException Se não conseguir carregar
     */
    public function loadPfxFromContent(string $pfxContent, string $password): array
    {
        $certs = [];
        
        if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new GedApiException("Erro ao ler certificado PFX. Senha incorreta ou arquivo corrompido.");
        }
        
        // Converter certificado de PEM para DER
        $certDer = $this->pemToDer($certs['cert']);
        
        // Carregar chave privada
        $privateKey = openssl_pkey_get_private($certs['pkey']);
        
        if ($privateKey === false) {
            throw new GedApiException("Erro ao carregar chave privada");
        }
        
        // Cadeia de certificados (se disponível)
        $chain = [];
        if (isset($certs['extracerts']) && is_array($certs['extracerts'])) {
            foreach ($certs['extracerts'] as $extraCert) {
                $chain[] = $this->pemToDer($extraCert);
            }
        }
        
        return [
            'certificate' => $certDer,
            'certificatePem' => $certs['cert'],
            'privateKey' => $privateKey,
            'privateKeyPem' => $certs['pkey'],
            'chain' => $chain,
        ];
    }
    
    /**
     * Extrai informações do certificado
     * 
     * @param string $certDer Certificado em formato DER
     * @return array Informações do certificado
     * @throws GedApiException Se não conseguir extrair
     */
    public function extractInfo(string $certDer): array
    {
        $x509 = new X509();
        $cert = $x509->loadX509($certDer);
        
        if (!$cert) {
            throw new GedApiException("Erro ao carregar certificado para extração");
        }
        
        $subject = $cert['tbsCertificate']['subject'] ?? [];
        $issuer = $cert['tbsCertificate']['issuer'] ?? [];
        $validity = $cert['tbsCertificate']['validity'] ?? [];
        $extensions = $cert['tbsCertificate']['extensions'] ?? [];
        
        return [
            'subjectName' => $this->formatDistinguishedName($subject),
            'issuerName' => $this->formatDistinguishedName($issuer),
            'serialNumber' => $this->formatSerialNumber($cert['tbsCertificate']['serialNumber'] ?? null),
            'validityStart' => $this->formatDate($validity['notBefore'] ?? null),
            'validityEnd' => $this->formatDate($validity['notAfter'] ?? null),
            'emailAddress' => $this->extractEmail($subject, $extensions),
            'cpf' => $this->extractCpf($subject),
            'cnpj' => $this->extractCnpj($subject),
            'commonName' => $this->extractField($subject, 'id-at-commonName'),
            'organization' => $this->extractField($subject, 'id-at-organizationName'),
            'organizationalUnit' => $this->extractField($subject, 'id-at-organizationalUnitName'),
            'country' => $this->extractField($subject, 'id-at-countryName'),
            'state' => $this->extractField($subject, 'id-at-stateOrProvinceName'),
            'locality' => $this->extractField($subject, 'id-at-localityName'),
        ];
    }
    
    /**
     * Extrai informações de certificado em formato PEM
     * 
     * @param string $certPem Certificado em formato PEM
     * @return array Informações do certificado
     */
    public function extractInfoFromPem(string $certPem): array
    {
        $certDer = $this->pemToDer($certPem);
        return $this->extractInfo($certDer);
    }
    
    /**
     * Converte certificado de PEM para DER
     * 
     * @param string $pem Certificado em PEM
     * @return string Certificado em DER
     */
    public function pemToDer(string $pem): string
    {
        // Remover header/footer e whitespace
        $pem = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r", ' '], '', $pem);
        
        return base64_decode($pem);
    }
    
    /**
     * Converte certificado de DER para PEM
     * 
     * @param string $der Certificado em DER
     * @return string Certificado em PEM
     */
    public function derToPem(string $der): string
    {
        $base64 = base64_encode($der);
        $pem = "-----BEGIN CERTIFICATE-----\n";
        $pem .= chunk_split($base64, 64, "\n");
        $pem .= "-----END CERTIFICATE-----";
        
        return $pem;
    }
    
    /**
     * Valida se o certificado está dentro do período de validade
     * 
     * @param string $certDer Certificado em DER
     * @return bool True se válido
     */
    public function isValid(string $certDer): bool
    {
        $info = $this->extractInfo($certDer);
        
        $now = time();
        $start = strtotime($info['validityStart']);
        $end = strtotime($info['validityEnd']);
        
        return $now >= $start && $now <= $end;
    }
    
    /**
     * Retorna os dias restantes de validade do certificado
     * 
     * @param string $certDer Certificado em DER
     * @return int Dias restantes (negativo se expirado)
     */
    public function getDaysUntilExpiration(string $certDer): int
    {
        $info = $this->extractInfo($certDer);
        $end = strtotime($info['validityEnd']);
        
        $diff = $end - time();
        
        return (int) floor($diff / 86400);
    }
    
    /**
     * Formata Distinguished Name (DN)
     * 
     * @param array $dn Distinguished Name
     * @return string DN formatado
     */
    private function formatDistinguishedName(array $dn): string
    {
        $parts = [];
        
        foreach ($dn['rdnSequence'] ?? [] as $rdn) {
            foreach ($rdn as $attribute) {
                $type = $attribute['type'] ?? '';
                $value = $attribute['value']['utf8String'] ?? 
                         $attribute['value']['printableString'] ?? 
                         $attribute['value'] ?? '';
                
                $parts[] = $this->getAttributeName($type) . '=' . $value;
            }
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Converte OID de atributo para nome legível
     * 
     * @param string $oid OID do atributo
     * @return string Nome do atributo
     */
    private function getAttributeName(string $oid): string
    {
        $names = [
            'id-at-commonName' => 'CN',
            'id-at-countryName' => 'C',
            'id-at-localityName' => 'L',
            'id-at-stateOrProvinceName' => 'ST',
            'id-at-organizationName' => 'O',
            'id-at-organizationalUnitName' => 'OU',
            'id-emailAddress' => 'E',
        ];
        
        return $names[$oid] ?? $oid;
    }
    
    /**
     * Extrai campo específico do subject
     * 
     * @param array $subject Subject do certificado
     * @param string $fieldOid OID do campo
     * @return string|null Valor do campo
     */
    private function extractField(array $subject, string $fieldOid): ?string
    {
        foreach ($subject['rdnSequence'] ?? [] as $rdn) {
            foreach ($rdn as $attribute) {
                if (($attribute['type'] ?? '') === $fieldOid) {
                    return $attribute['value']['utf8String'] ?? 
                           $attribute['value']['printableString'] ?? 
                           $attribute['value'] ?? null;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extrai email do certificado
     * 
     * @param array $subject Subject do certificado
     * @param array $extensions Extensões do certificado
     * @return string|null Email
     */
    private function extractEmail(array $subject, array $extensions): ?string
    {
        // Tentar extrair do subject
        $email = $this->extractField($subject, 'id-emailAddress');
        
        if ($email) {
            return $email;
        }
        
        // Tentar extrair das extensões (subjectAltName)
        foreach ($extensions as $extension) {
            if (($extension['extnId'] ?? '') === 'id-ce-subjectAltName') {
                // TODO: Implementar extração de email do subjectAltName
            }
        }
        
        return null;
    }
    
    /**
     * Extrai CPF do certificado ICP-Brasil
     * 
     * @param array $subject Subject do certificado
     * @return string|null CPF
     */
    private function extractCpf(array $subject): ?string
    {
        $cn = $this->extractField($subject, 'id-at-commonName');
        
        if (!$cn) {
            return null;
        }
        
        // CPF geralmente vem no formato: "Nome:12345678900" no CN
        if (preg_match('/:(\d{11})/', $cn, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Extrai CNPJ do certificado ICP-Brasil
     * 
     * @param array $subject Subject do certificado
     * @return string|null CNPJ
     */
    private function extractCnpj(array $subject): ?string
    {
        $cn = $this->extractField($subject, 'id-at-commonName');
        
        if (!$cn) {
            return null;
        }
        
        // CNPJ geralmente vem no formato: "Empresa:12345678000190" no CN
        if (preg_match('/:(\d{14})/', $cn, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Formata número serial do certificado
     * 
     * @param mixed $serial Número serial
     * @return string Serial formatado
     */
    private function formatSerialNumber($serial): string
    {
        if ($serial === null) {
            return '';
        }
        
        if (is_string($serial)) {
            return strtoupper(bin2hex($serial));
        }
        
        if (is_array($serial) && isset($serial['hex'])) {
            return strtoupper($serial['hex']);
        }
        
        return (string) $serial;
    }
    
    /**
     * Formata data do certificado
     * 
     * @param mixed $date Data em formato ASN.1
     * @return string Data formatada (ISO 8601)
     */
    private function formatDate($date): string
    {
        if ($date === null) {
            return '';
        }
        
        // UTCTime ou GeneralizedTime
        $dateStr = $date['utcTime'] ?? $date['generalTime'] ?? '';
        
        if (empty($dateStr)) {
            return '';
        }
        
        // Converter para timestamp
        $timestamp = strtotime($dateStr);
        
        if ($timestamp === false) {
            return $dateStr;
        }
        
        return date('Y-m-d\TH:i:s\Z', $timestamp);
    }
}

