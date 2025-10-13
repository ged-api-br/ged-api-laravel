# Changelog

All notable changes to `ged-api-laravel` will be documented in this file.

## [2.0.0] - 2025-10-13

### Added - PAdES Assinatura Digital em 3 Fases 🎉
- **FASE 1 (Starter):** Implementação completa do padrão de 3 fases para assinatura PAdES
- `PadesSignatureStarter` - Classe principal para iniciar assinatura (FASE 1)
- `SignatureAlgorithmParameters` - Modelo para parâmetros de assinatura
- `FileReference` - Abstração para manipulação de arquivos (PDF)
- `CertificateHelper` - Utilitários para trabalhar com certificados digitais
- `StandardSignaturePolicies` - Constantes de políticas ICP-Brasil e genéricas
- `Oids` - Constantes de Object Identifiers (SHA-256, CMS, etc.)

### Features
- ✅ Padrão de assinatura em 3 fases (segurança máxima)
- ✅ Suporte a políticas oficiais ICP-Brasil (DOC-ICP-15.03 e 15.04)
- ✅ Identificadores descritivos e legíveis
- ✅ OIDs oficiais documentados inline
- ✅ Extração de informações de certificados (CPF, CNPJ, validade)
- ✅ Conversão de formatos (PEM ↔ DER)
- ✅ Suporte a certificados A1 (PFX/P12)
- ✅ Representação visual de assinatura (opcional)
- ✅ Documentação completa com exemplos

### Changed
- **BREAKING:** Substituído Guzzle por Laravel Http Client nativo
- Refatorado `GedApiClient` para usar `Illuminate\Support\Facades\Http`
- Melhorado tratamento de erros com `GedApiException`

### Políticas de Assinatura
#### ICP-Brasil (Oficiais)
- `PADES_ICPBR_ADR_BASICA` → OID: 2.16.76.1.7.1.11.1.1
- `PADES_ICPBR_ADR_TEMPO` → OID: 2.16.76.1.7.1.11.1.2
- `CADES_ICPBR_ADR_BASICA` → OID: 2.16.76.1.7.1.1.2.1
- `CADES_ICPBR_ADR_TEMPO` → OID: 2.16.76.1.7.1.2.2.1

#### Genéricas
- `PADES_BASIC` - PAdES básico (uso geral)
- `PADES_WITH_TIMESTAMP` - PAdES com carimbo de tempo
- `PADES_ADOBE_COMPATIBLE` - Compatível com Adobe Reader

### Tested
- ✅ StandardSignaturePolicies - 100% testado
- ✅ Oids - 100% testado
- ✅ FileReference - 100% testado com PDF 482KB
- ✅ CertificateHelper - 100% testado com certificado ICP-Brasil real
- ✅ Extração de chave pública de PFX - Validado

### Documentation
- README.md atualizado com documentação completa da FASE 1
- Exemplos práticos em `examples/PadesSignaturePhase1Example.php`
- PHPDoc completo em todas as classes
- Referências aos documentos oficiais ITI

---

## [1.1.0] - 2025-10-09

### Added
- `CmsBuilder` class - Constrói CMS/PKCS#7 completo com phpseclib3
- Suporte para montagem correta de SignedData
- Adicionada dependência `phpseclib/phpseclib ^3.0`

### Fixed
- CMS agora assina SignedAttributes ao invés de messageDigest
- Estrutura CMS compatível com Adobe Reader

## [1.0.0] - 2025-10-08

### Added
- Initial release
- `GedApiServiceProvider` - Laravel Service Provider
- `GedApi` Facade for easy access
- Configuration file `ged-api.php`
- Auto-discovery support for Laravel 10+
- Environment variables configuration
- Complete documentation

### Features
- Seamless integration with Laravel
- Facade support for clean syntax
- Dependency injection support
- Configuration via `.env`
- Automatic service provider registration

### Dependencies
- Laravel >= 9.0
- PHP >= 8.0

