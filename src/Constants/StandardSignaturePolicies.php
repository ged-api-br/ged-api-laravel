<?php

namespace Ged\ApiLaravel\Constants;

/**
 * ============================================================================
 * StandardSignaturePolicies - Políticas de Assinatura Digital
 * ============================================================================
 *
 * Define as políticas de assinatura digital suportadas pela GED API.
 *
 * POLÍTICAS ICP-BRASIL (OFICIAIS):
 * Baseadas nos documentos DOC-ICP-15.03 e DOC-ICP-15.04 do ITI
 * (Instituto Nacional de Tecnologia da Informação)
 *
 * POLÍTICAS GENÉRICAS:
 * Para uso geral, fora do âmbito da ICP-Brasil
 *
 * Referências:
 * - DOC-ICP-15.03: Políticas de Assinatura Digital na ICP-Brasil (CAdES)
 * - DOC-ICP-15.04: Políticas de Assinatura Digital na ICP-Brasil (PAdES)
 * - RFC 5652: Cryptographic Message Syntax (CMS)
 * - ISO 32000: PDF Specification
 * ============================================================================
 */
class StandardSignaturePolicies
{
    // ============================================================================
    // POLÍTICAS ICP-BRASIL - PAdES (PDF Advanced Electronic Signatures)
    // ============================================================================
    // Estas políticas seguem os padrões oficiais do ITI e possuem OIDs registrados
    // ============================================================================
    
    /**
     * PAdES ICP-Brasil - Assinatura Digital com Referências Básicas (AD-RB)
     * 
     * OID Oficial: 2.16.76.1.7.1.11.1.1
     * Documento: DOC-ICP-15.04 v1.1
     * 
     * Características:
     * - Assinatura digital básica ICP-Brasil
     * - Validação de cadeia de certificação completa
     * - Informações de revogação (CRL/OCSP)
     * - Homologada pelo ITI
     * 
     * Uso: Documentos oficiais que requerem conformidade ICP-Brasil
     */
    public const PADES_ICPBR_ADR_BASICA = 'pades-icpbr-adr-basica';
    
    /**
     * PAdES ICP-Brasil - Assinatura Digital com Referências de Tempo (AD-RT)
     * 
     * OID Oficial: 2.16.76.1.7.1.11.1.2
     * Documento: DOC-ICP-15.04 v1.1
     * 
     * Características:
     * - Inclui todas as características da AD-RB
     * - Carimbo de tempo (timestamp) obrigatório
     * - Validade de longo prazo
     * - Homologada pelo ITI
     * 
     * Uso: Documentos que precisam comprovar data/hora da assinatura
     */
    public const PADES_ICPBR_ADR_TEMPO = 'pades-icpbr-adr-tempo';
    
    // ============================================================================
    // POLÍTICAS ICP-BRASIL - CAdES (CMS Advanced Electronic Signatures)
    // ============================================================================
    
    /**
     * CAdES ICP-Brasil - Assinatura Digital com Referências Básicas (AD-RB)
     * 
     * OID Oficial: 2.16.76.1.7.1.1.2.1 (v2.1)
     * Documento: DOC-ICP-15.03 v2.1
     * 
     * Características:
     * - Assinatura CMS básica ICP-Brasil
     * - Para arquivos não-PDF (XML, TXT, etc.)
     * - Validação de cadeia completa
     * - Homologada pelo ITI
     * 
     * Uso: Assinatura de documentos XML, notas fiscais eletrônicas, etc.
     */
    public const CADES_ICPBR_ADR_BASICA = 'cades-icpbr-adr-basica';
    
    /**
     * CAdES ICP-Brasil - Assinatura Digital com Referências de Tempo (AD-RT)
     * 
     * OID Oficial: 2.16.76.1.7.1.2.2.1 (v2.1)
     * Documento: DOC-ICP-15.03 v2.1
     * 
     * Características:
     * - Inclui todas as características da AD-RB
     * - Carimbo de tempo obrigatório
     * - Validade de longo prazo
     * - Homologada pelo ITI
     * 
     * Uso: Documentos CMS com comprovação de data/hora
     */
    public const CADES_ICPBR_ADR_TEMPO = 'cades-icpbr-adr-tempo';
    
    // ============================================================================
    // POLÍTICAS GENÉRICAS - PAdES (Uso Geral)
    // ============================================================================
    // Estas políticas NÃO são homologadas pela ICP-Brasil
    // Use apenas quando a conformidade ICP-Brasil não for necessária
    // ============================================================================
    
    /**
     * PAdES Básico
     * 
     * SEM OID oficial (uso interno)
     * 
     * Características:
     * - Assinatura PAdES padrão
     * - Sem validação ICP-Brasil
     * - Mais rápida e leve
     * 
     * Uso: Documentos internos, contratos privados, comprovantes
     */
    public const PADES_BASIC = 'pades-basic';
    
    /**
     * PAdES com Timestamp
     * 
     * SEM OID oficial (uso interno)
     * 
     * Características:
     * - Assinatura PAdES com carimbo de tempo
     * - Sem validação ICP-Brasil
     * - Validade de longo prazo
     * 
     * Uso: Documentos que precisam comprovar data/hora (sem ICP-Brasil)
     */
    public const PADES_WITH_TIMESTAMP = 'pades-with-timestamp';
    
    /**
     * PAdES Adobe Reader
     * 
     * SEM OID oficial (uso interno)
     * 
     * Características:
     * - Compatibilidade máxima com Adobe Reader
     * - Assinatura visível por padrão
     * - Não requer plugins adicionais
     * 
     * Uso: Máxima compatibilidade com visualizadores PDF
     */
    public const PADES_ADOBE_COMPATIBLE = 'pades-adobe-compatible';
    
    // ============================================================================
    // MÉTODOS AUXILIARES
    // ============================================================================
    
    /**
     * Retorna o OID oficial da política (somente para políticas ICP-Brasil)
     * 
     * @param string $policyId Identificador da política
     * @return string|null OID oficial ou null se não for política ICP-Brasil
     * 
     * @example
     * StandardSignaturePolicies::getOid('pades-icpbr-adr-basica');
     * // Retorna: '2.16.76.1.7.1.11.1.1'
     */
    public static function getOid(string $policyId): ?string
    {
        $oidMapping = [
            // PAdES ICP-Brasil
            self::PADES_ICPBR_ADR_BASICA => '2.16.76.1.7.1.11.1.1',
            self::PADES_ICPBR_ADR_TEMPO  => '2.16.76.1.7.1.11.1.2',
            
            // CAdES ICP-Brasil
            self::CADES_ICPBR_ADR_BASICA => '2.16.76.1.7.1.1.2.1',
            self::CADES_ICPBR_ADR_TEMPO  => '2.16.76.1.7.1.2.2.1',
        ];
        
        return $oidMapping[$policyId] ?? null;
    }
    
    /**
     * Retorna o nome legível da política
     * 
     * @param string $policyId Identificador da política
     * @return string Nome descritivo da política
     */
    public static function getName(string $policyId): string
    {
        $names = [
            // PAdES ICP-Brasil
            self::PADES_ICPBR_ADR_BASICA => 'PAdES ICP-Brasil AD-RB (Assinatura Digital com Referências Básicas)',
            self::PADES_ICPBR_ADR_TEMPO  => 'PAdES ICP-Brasil AD-RT (Assinatura Digital com Referências de Tempo)',
            
            // CAdES ICP-Brasil
            self::CADES_ICPBR_ADR_BASICA => 'CAdES ICP-Brasil AD-RB (Assinatura Digital com Referências Básicas)',
            self::CADES_ICPBR_ADR_TEMPO  => 'CAdES ICP-Brasil AD-RT (Assinatura Digital com Referências de Tempo)',
            
            // PAdES Genérico
            self::PADES_BASIC            => 'PAdES Básico',
            self::PADES_WITH_TIMESTAMP   => 'PAdES com Timestamp',
            self::PADES_ADOBE_COMPATIBLE => 'PAdES Adobe Reader Compatível',
        ];
        
        return $names[$policyId] ?? 'Política Desconhecida';
    }
    
    /**
     * Verifica se a política requer carimbo de tempo (timestamp)
     * 
     * @param string $policyId Identificador da política
     * @return bool True se a política requer timestamp
     */
    public static function requiresTimestamp(string $policyId): bool
    {
        $policiesWithTimestamp = [
            self::PADES_ICPBR_ADR_TEMPO,
            self::PADES_WITH_TIMESTAMP,
            self::CADES_ICPBR_ADR_TEMPO,
        ];
        
        return in_array($policyId, $policiesWithTimestamp, true);
    }
    
    /**
     * Verifica se a política é homologada pela ICP-Brasil
     * 
     * @param string $policyId Identificador da política
     * @return bool True se for política oficial ICP-Brasil
     */
    public static function isIcpBrasil(string $policyId): bool
    {
        return str_contains($policyId, '-icpbr-');
    }
    
    /**
     * Retorna todas as políticas PAdES disponíveis
     * 
     * @return array<string> Lista de identificadores de políticas PAdES
     */
    public static function getPadesPolicies(): array
    {
        return [
            // ICP-Brasil
            self::PADES_ICPBR_ADR_BASICA,
            self::PADES_ICPBR_ADR_TEMPO,
            
            // Genéricas
            self::PADES_BASIC,
            self::PADES_WITH_TIMESTAMP,
            self::PADES_ADOBE_COMPATIBLE,
        ];
    }
    
    /**
     * Retorna todas as políticas CAdES disponíveis
     * 
     * @return array<string> Lista de identificadores de políticas CAdES
     */
    public static function getCadesPolicies(): array
    {
        return [
            self::CADES_ICPBR_ADR_BASICA,
            self::CADES_ICPBR_ADR_TEMPO,
        ];
    }
    
    /**
     * Retorna apenas as políticas homologadas pela ICP-Brasil
     * 
     * @return array<string> Lista de políticas ICP-Brasil
     */
    public static function getIcpBrasilPolicies(): array
    {
        return [
            self::PADES_ICPBR_ADR_BASICA,
            self::PADES_ICPBR_ADR_TEMPO,
            self::CADES_ICPBR_ADR_BASICA,
            self::CADES_ICPBR_ADR_TEMPO,
        ];
    }
    
    /**
     * Retorna a descrição completa da política
     * 
     * @param string $policyId Identificador da política
     * @return array Informações detalhadas da política
     */
    public static function getInfo(string $policyId): array
    {
        return [
            'id' => $policyId,
            'name' => self::getName($policyId),
            'oid' => self::getOid($policyId),
            'requiresTimestamp' => self::requiresTimestamp($policyId),
            'isIcpBrasil' => self::isIcpBrasil($policyId),
            'type' => str_starts_with($policyId, 'pades-') ? 'PAdES' : 'CAdES',
        ];
    }
}


