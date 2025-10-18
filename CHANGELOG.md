# Changelog - ged/api-laravel

## [2.3.0] - 2025-10-18

### Added
- ✅ **OID Support - AC Safeweb RFB** - Adicionados OIDs para certificados Safeweb RFB
  - `Oids::ICPBR_CERT_A1_SAFEWEB_RFB` (2.16.76.1.2.1.51) - Certificado A1
  - `Oids::ICPBR_CERT_A3_SAFEWEB_RFB` (2.16.76.1.2.3.48) - Certificado A3
- ✅ **Certificate Type Detection** - Novos métodos utilitários:
  - `Oids::isCertificateA1($oid)` - Verifica se é certificado A1
  - `Oids::isCertificateA3($oid)` - Verifica se é certificado A3
  - `Oids::getCertificateType($oid)` - Retorna 'A1', 'A3' ou null
  - `Oids::getCertificateDescription($oid)` - Retorna descrição do certificado

### Changed
- 📝 **Oids Class** - Expandida com suporte a políticas de certificados ICP-Brasil
- 📝 **Documentation** - Adicionada seção para OIDs de certificados

### Notes
- Certificados A1 da AC Safeweb RFB agora são reconhecidos automaticamente
- Preparado para futura implementação de suporte A3 (Hardware Token/Smartcard)

## [2.2.0] - 2025-10-17

### Fixed
- ✅ **Adobe Reader Validation** - Corrigido erro "certificado do assinante é inválido"
- ✅ **Complete Certificate Chain** - Cadeia completa de certificados agora incluída no CMS
- ✅ **SignatureController Example** - Atualizado para usar CertificateHelper e extrair cadeia

### Changed
- 📝 **examples/SignatureController.php** - Exemplo atualizado para incluir cadeia completa
  - Usa `CertificateHelper::loadPfxFromContent()` para extrair cadeia
  - Envia cadeia via `padesInjectPkcs1()` com parâmetro `$chainBase64`
  - Certificados ICP-Brasil agora validam no Adobe Reader e ITI

### Technical Details
- O problema afetava apenas Adobe Reader (ITI validava normalmente)
- Causa: Cadeia de certificados intermediários não estava sendo enviada
- Solução: Extrair `extracerts` do PFX e incluir no payload de injeção
- Certificados ICP-Brasil geralmente contêm 3-4 certificados na cadeia

## [2.1.0] - 2025-10-15

### Added
- ✅ **CertificateHelper::extractPublicKeyDerBase64()** - Extração de chave pública multi-formato
- ✅ **CertificateHelper::extractPublicKeyFromFile()** - Extração direta de arquivo
- ✅ **GedApiClient::extractPublicKey()** - Método no client para chamar GED
- ✅ **GedApiClient::extractPublicKeyFromFile()** - Extração via GED API
- ✅ **GedApiClient::get()** - Método GET genérico
- ✅ **Suporte OpenSSL 3.x** - Compatibilidade com algoritmos legacy

### Supported Formats
- PFX/P12 (PKCS#12) - Com senha
- PEM (texto) - Sem senha
- CER/DER (binário) - Sem senha
- CRT (auto-detecta) - Sem senha

### Changed
- Melhorado tratamento de arquivos PEM com Bag Attributes
- Regex para extrair apenas bloco CERTIFICATE do PEM

## [2.0.3] - 2025-10-15
### Added
- GedApiClient::get() method

## [2.0.2] - 2025-10-14
### Initial PAdES implementation
