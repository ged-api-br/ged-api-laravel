<?php

namespace Ged\ApiLaravel\Constants;

/**
 * ============================================================================
 * Oids - Object Identifiers para Assinatura Digital
 * ============================================================================
 *
 * Define os OIDs (Object Identifiers) padronizados utilizados em assinaturas
 * digitais conforme RFC 5652 (CMS), RFC 5126 (CAdES) e ISO 32000 (PDF).
 *
 * OIDs são identificadores únicos que definem algoritmos, tipos de dados e
 * políticas de assinatura em estruturas ASN.1/DER.
 *
 * Compatível com:
 * - PKCS#7/CMS (Cryptographic Message Syntax)
 * - ICP-Brasil
 * - Adobe PDF Signatures
 * ============================================================================
 */
class Oids
{
    // ========== Algoritmos de Hash (Digest) ==========
    
    /**
     * MD5
     * @deprecated Obsoleto, não usar em produção
     */
    public const MD5 = '1.2.840.113549.2.5';
    
    /**
     * SHA-1
     * OID: 1.3.14.3.2.26
     */
    public const SHA1 = '1.3.14.3.2.26';
    
    /**
     * SHA-256
     * OID: 2.16.840.1.101.3.4.2.1
     * Padrão recomendado para assinaturas ICP-Brasil
     */
    public const SHA256 = '2.16.840.1.101.3.4.2.1';
    
    /**
     * SHA-384
     * OID: 2.16.840.1.101.3.4.2.2
     */
    public const SHA384 = '2.16.840.1.101.3.4.2.2';
    
    /**
     * SHA-512
     * OID: 2.16.840.1.101.3.4.2.3
     */
    public const SHA512 = '2.16.840.1.101.3.4.2.3';
    
    // ========== Algoritmos de Assinatura ==========
    
    /**
     * RSA Encryption
     * OID: 1.2.840.113549.1.1.1
     * Algoritmo de criptografia RSA
     */
    public const RSA_ENCRYPTION = '1.2.840.113549.1.1.1';
    
    /**
     * MD5 with RSA
     * @deprecated Obsoleto
     */
    public const MD5_WITH_RSA = '1.2.840.113549.1.1.4';
    
    /**
     * SHA-1 with RSA
     * OID: 1.2.840.113549.1.1.5
     */
    public const SHA1_WITH_RSA = '1.2.840.113549.1.1.5';
    
    /**
     * SHA-256 with RSA
     * OID: 1.2.840.113549.1.1.11
     * Padrão recomendado ICP-Brasil
     */
    public const SHA256_WITH_RSA = '1.2.840.113549.1.1.11';
    
    /**
     * SHA-384 with RSA
     * OID: 1.2.840.113549.1.1.12
     */
    public const SHA384_WITH_RSA = '1.2.840.113549.1.1.12';
    
    /**
     * SHA-512 with RSA
     * OID: 1.2.840.113549.1.1.13
     */
    public const SHA512_WITH_RSA = '1.2.840.113549.1.1.13';
    
    // ========== PKCS#7 / CMS Content Types ==========
    
    /**
     * id-data
     * OID: 1.2.840.113549.1.7.1
     * Tipo de conteúdo: dados brutos
     */
    public const ID_DATA = '1.2.840.113549.1.7.1';
    
    /**
     * id-signedData
     * OID: 1.2.840.113549.1.7.2
     * Tipo de conteúdo: dados assinados (CMS/PKCS#7)
     */
    public const SIGNED_DATA = '1.2.840.113549.1.7.2';
    
    /**
     * id-envelopedData
     * OID: 1.2.840.113549.1.7.3
     */
    public const ENVELOPED_DATA = '1.2.840.113549.1.7.3';
    
    /**
     * id-digestedData
     * OID: 1.2.840.113549.1.7.5
     */
    public const DIGESTED_DATA = '1.2.840.113549.1.7.5';
    
    /**
     * id-encryptedData
     * OID: 1.2.840.113549.1.7.6
     */
    public const ENCRYPTED_DATA = '1.2.840.113549.1.7.6';
    
    // ========== PKCS#9 Attributes ==========
    
    /**
     * contentType
     * OID: 1.2.840.113549.1.9.3
     * Atributo obrigatório em SignedAttributes
     */
    public const CONTENT_TYPE = '1.2.840.113549.1.9.3';
    
    /**
     * messageDigest
     * OID: 1.2.840.113549.1.9.4
     * Atributo obrigatório: hash do conteúdo assinado
     */
    public const MESSAGE_DIGEST = '1.2.840.113549.1.9.4';
    
    /**
     * signingTime
     * OID: 1.2.840.113549.1.9.5
     * Atributo opcional: data/hora da assinatura
     */
    public const SIGNING_TIME = '1.2.840.113549.1.9.5';
    
    /**
     * signingCertificate (v1)
     * OID: 1.2.840.113549.1.9.16.2.12
     * Referência ao certificado do signatário
     */
    public const SIGNING_CERTIFICATE = '1.2.840.113549.1.9.16.2.12';
    
    /**
     * signingCertificateV2
     * OID: 1.2.840.113549.1.9.16.2.47
     * Versão 2 (suporta SHA-256)
     */
    public const SIGNING_CERTIFICATE_V2 = '1.2.840.113549.1.9.16.2.47';
    
    // ========== ICP-Brasil Policy OIDs ==========
    
    /**
     * Política ICP-Brasil PAdES ADR Básica
     * OID: 2.16.76.1.7.1.1.1
     */
    public const ICPBR_PADES_ADR_BASICA = '2.16.76.1.7.1.1.1';
    
    /**
     * Política ICP-Brasil PAdES ADR Tempo
     * OID: 2.16.76.1.7.1.1.2
     */
    public const ICPBR_PADES_ADR_TEMPO = '2.16.76.1.7.1.1.2';
    
    /**
     * Política ICP-Brasil CAdES ADR Básica
     * OID: 2.16.76.1.7.1.2.1
     */
    public const ICPBR_CADES_ADR_BASICA = '2.16.76.1.7.1.2.1';
    
    /**
     * Política ICP-Brasil CAdES ADR Tempo
     * OID: 2.16.76.1.7.1.2.2
     */
    public const ICPBR_CADES_ADR_TEMPO = '2.16.76.1.7.1.2.2';
    
    // ========== Adobe PDF Signature ==========
    
    /**
     * Adobe PDF Signature Dictionary
     * OID: 1.2.840.113583.1.1.8
     */
    public const ADOBE_PDF_SIGNATURE = '1.2.840.113583.1.1.8';
    
    /**
     * Adobe Revocation Information
     * OID: 1.2.840.113583.1.1.8.1
     */
    public const ADOBE_REVOCATION_INFO = '1.2.840.113583.1.1.8.1';
    
    /**
     * Adobe Archive Timestamp
     * OID: 1.2.840.113583.1.1.9.2
     */
    public const ADOBE_ARCHIVE_TIMESTAMP = '1.2.840.113583.1.1.9.2';
    
    // ========== Métodos Auxiliares ==========
    
    /**
     * Retorna o OID do algoritmo de hash pelo nome
     * 
     * @param string $algorithm Nome do algoritmo (sha1, sha256, sha384, sha512)
     * @return string OID do algoritmo
     * @throws \InvalidArgumentException Se algoritmo não for suportado
     */
    public static function getDigestAlgorithmOid(string $algorithm): string
    {
        $algorithms = [
            'sha1' => self::SHA1,
            'sha256' => self::SHA256,
            'sha384' => self::SHA384,
            'sha512' => self::SHA512,
        ];
        
        $algorithm = strtolower($algorithm);
        
        if (!isset($algorithms[$algorithm])) {
            throw new \InvalidArgumentException("Algoritmo de hash não suportado: {$algorithm}");
        }
        
        return $algorithms[$algorithm];
    }
    
    /**
     * Retorna o OID do algoritmo de assinatura pelo nome
     * 
     * @param string $algorithm Nome do algoritmo (sha1WithRSA, sha256WithRSA, etc.)
     * @return string OID do algoritmo
     * @throws \InvalidArgumentException Se algoritmo não for suportado
     */
    public static function getSignatureAlgorithmOid(string $algorithm): string
    {
        $algorithms = [
            'sha1WithRSA' => self::SHA1_WITH_RSA,
            'sha256WithRSA' => self::SHA256_WITH_RSA,
            'sha384WithRSA' => self::SHA384_WITH_RSA,
            'sha512WithRSA' => self::SHA512_WITH_RSA,
        ];
        
        if (!isset($algorithms[$algorithm])) {
            throw new \InvalidArgumentException("Algoritmo de assinatura não suportado: {$algorithm}");
        }
        
        return $algorithms[$algorithm];
    }
    
    /**
     * Retorna o nome do algoritmo pelo OID
     * 
     * @param string $oid OID do algoritmo
     * @return string|null Nome do algoritmo ou null se não for encontrado
     */
    public static function getAlgorithmName(string $oid): ?string
    {
        $names = [
            self::MD5 => 'MD5',
            self::SHA1 => 'SHA-1',
            self::SHA256 => 'SHA-256',
            self::SHA384 => 'SHA-384',
            self::SHA512 => 'SHA-512',
            self::SHA256_WITH_RSA => 'SHA-256 with RSA',
            self::SHA384_WITH_RSA => 'SHA-384 with RSA',
            self::SHA512_WITH_RSA => 'SHA-512 with RSA',
        ];
        
        return $names[$oid] ?? null;
    }
    
    /**
     * Verifica se um OID é de um algoritmo de hash
     * 
     * @param string $oid OID a verificar
     * @return bool True se for algoritmo de hash
     */
    public static function isDigestAlgorithm(string $oid): bool
    {
        return in_array($oid, [
            self::MD5,
            self::SHA1,
            self::SHA256,
            self::SHA384,
            self::SHA512,
        ]);
    }
    
    /**
     * Verifica se um OID é de uma política ICP-Brasil
     * 
     * @param string $oid OID a verificar
     * @return bool True se for política ICP-Brasil
     */
    public static function isIcpBrasilPolicy(string $oid): bool
    {
        return in_array($oid, [
            self::ICPBR_PADES_ADR_BASICA,
            self::ICPBR_PADES_ADR_TEMPO,
            self::ICPBR_CADES_ADR_BASICA,
            self::ICPBR_CADES_ADR_TEMPO,
        ]);
    }
}

