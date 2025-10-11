<?php

namespace Ged\ApiLaravel;

use phpseclib3\File\ASN1;
use phpseclib3\File\X509;

/**
 * ============================================================================
 * CmsBuilder - Construtor de CMS/PKCS#7 para PAdES
 * ============================================================================
 *
 * Monta a estrutura CMS (Cryptographic Message Syntax) completa conforme:
 * - RFC 5652 (CMS - Cryptographic Message Syntax)
 * - RFC 5126 (CAdES - CMS Advanced Electronic Signatures)
 * - ISO 32000-1:2008 (PDF Digital Signatures)
 *
 * Estrutura gerada:
 *   ContentInfo
 *   ├─ contentType: 1.2.840.113549.1.7.2 (signedData)
 *   └─ content [0]: SignedData
 *       ├─ version: 1
 *       ├─ digestAlgorithms: SET { sha256 }
 *       ├─ encapContentInfo: { contentType: id-data }
 *       ├─ certificates [0]: { certificado DER }
 *       └─ signerInfos: SET {
 *           SignerInfo:
 *             ├─ version: 1
 *             ├─ sid: issuerAndSerialNumber
 *             ├─ digestAlgorithm: sha256
 *             ├─ signedAttrs [0]: SignedAttributes
 *             ├─ signatureAlgorithm: rsaEncryption
 *             └─ signature: OCTET STRING (assinatura RSA)
 *       }
 * ============================================================================
 */
class CmsBuilder
{
    private X509 $x509;

    public function __construct()
    {
        $this->x509 = new X509();
    }

    /**
     * Constrói CMS/PKCS#7 SignedData completo.
     *
     * @param string $signedAttributesDer SignedAttributes DER (da Fase 3)
     * @param string $signature           Assinatura RSA dos SignedAttributes
     * @param string $certDer             Certificado DER
     * @param string $certPem             Certificado PEM (para extrair issuer/serial)
     * @return string CMS DER completo
     * @throws \RuntimeException
     */
    public function build(
        string $signedAttributesDer,
        string $signature,
        string $certDer,
        string $certPem
    ): string {
        // === 1️⃣ Carregar certificado e extrair informações ===
        $cert = $this->x509->loadX509($certDer);
        
        if (!$cert) {
            throw new \RuntimeException('Falha ao carregar certificado');
        }

        $issuer = $cert['tbsCertificate']['issuer'];
        $serialNumber = $cert['tbsCertificate']['serialNumber'];

        // === 2️⃣ Montar IssuerAndSerialNumber ===
        $issuerAndSerialNumber = [
            'issuer' => $issuer,
            'serialNumber' => $serialNumber
        ];

        // === 3️⃣ Montar SignerInfo ===
        $signerInfo = [
            'version' => 1,
            'sid' => [
                'issuerAndSerialNumber' => $issuerAndSerialNumber
            ],
            'digestAlgorithm' => [
                'algorithm' => '2.16.840.1.101.3.4.2.1' // sha256
            ],
            'signedAttrs' => [
                'context' => 0,
                'value' => ASN1::decodeBER($signedAttributesDer)
            ],
            'signatureAlgorithm' => [
                'algorithm' => '1.2.840.113549.1.1.1' // rsaEncryption
            ],
            'signature' => $signature
        ];

        // === 4️⃣ Montar SignedData ===
        $signedData = [
            'version' => 1,
            'digestAlgorithms' => [
                [
                    'algorithm' => '2.16.840.1.101.3.4.2.1' // sha256
                ]
            ],
            'encapContentInfo' => [
                'eContentType' => '1.2.840.113549.1.7.1' // id-data
            ],
            'certificates' => [
                [
                    'context' => 0,
                    'value' => ASN1::decodeBER($certDer)
                ]
            ],
            'signerInfos' => [$signerInfo]
        ];

        // === 5️⃣ Montar ContentInfo ===
        $contentInfo = [
            'contentType' => '1.2.840.113549.1.7.2', // signedData
            'content' => [
                'context' => 0,
                'value' => $signedData
            ]
        ];

        // === 6️⃣ Definir schema e codificar ===
        $schema = $this->getContentInfoSchema();
        $der = ASN1::encodeDER($contentInfo, $schema);

        if (!$der) {
            throw new \RuntimeException('Falha ao codificar CMS em DER');
        }

        return $der;
    }

    /**
     * Retorna o schema ASN.1 para ContentInfo.
     */
    private function getContentInfoSchema(): array
    {
        return [
            'type' => ASN1::TYPE_SEQUENCE,
            'children' => [
                'contentType' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
                'content' => [
                    'type' => ASN1::TYPE_ANY,
                    'constant' => 0,
                    'optional' => true,
                    'explicit' => true
                ]
            ]
        ];
    }

    /**
     * Método simplificado usando comando openssl (fallback).
     * 
     * @deprecated Use build() para estrutura CMS completa
     */
    public function buildWithOpenssl(
        string $signedAttributesDer,
        string $certPem,
        string $keyPem
    ): string {
        $dataTempFile = tempnam(sys_get_temp_dir(), 'data_');
        $cmsTempFile = tempnam(sys_get_temp_dir(), 'cms_');
        $certTempFile = tempnam(sys_get_temp_dir(), 'cert_');
        $keyTempFile = tempnam(sys_get_temp_dir(), 'key_');

        try {
            file_put_contents($dataTempFile, $signedAttributesDer);
            file_put_contents($certTempFile, $certPem);
            file_put_contents($keyTempFile, $keyPem);

            $cmd = sprintf(
                'openssl cms -sign -binary -md sha256 ' .
                '-signer %s -inkey %s ' .
                '-in %s -out %s ' .
                '-outform DER -nodetach -nocerts 2>&1',
                escapeshellarg($certTempFile),
                escapeshellarg($keyTempFile),
                escapeshellarg($dataTempFile),
                escapeshellarg($cmsTempFile)
            );

            exec($cmd, $output, $rc);

            if ($rc !== 0) {
                throw new \RuntimeException(
                    'openssl cms falhou: ' . implode("\n", $output)
                );
            }

            return file_get_contents($cmsTempFile);

        } finally {
            @unlink($dataTempFile);
            @unlink($cmsTempFile);
            @unlink($certTempFile);
            @unlink($keyTempFile);
        }
    }
}

