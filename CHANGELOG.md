# Changelog - ged/api-laravel

## [2.4.8] - 2025-01-30

### Changed
- ✅ **Removido menu "Caixa de Entrada"** da sidebar do frontend
  - Item "Meus Documentos" removido junto com o grupo
  - Simplifica navegação e remove itens não utilizados

### Notes
- Limpeza de interface, removendo funcionalidades não utilizadas

## [2.4.7] - 2025-10-26

### Changed
- ✅ **padesPrepareFromFile()** - Simplificado para usar base64
  - Agora converte arquivo para base64 e chama padesPrepareFromBase64()
  - Remove complexidade de multipart/form-data
  - Mais confiável e compatível

### Fixed
- ✅ **post()** - Validação de retorno
  - Garante que sempre retorna array
  - Adiciona logs detalhados quando falha
  - Lança exceção clara quando retorno não é array

### Notes
- Soluciona problemas com multipart no Laravel HTTP Client
- Mantém compatibilidade total com versões anteriores

## [2.4.6] - 2025-10-26

### Changed
- ✅ **GedApiClient** - Timeout aumentado para arquivos grandes
  - Todos os métodos HTTP agora usam timeout de 300 segundos (5 minutos)
  - Suporta upload/download de arquivos até 100MB
  - Resolve problema de timeout com arquivos grandes em base64

### Notes
- Necessário para processar documentos > 15MB em base64 (~20MB payload)
- Compatível com configurações de servidor (upload_max_filesize, post_max_size)

## [2.4.5] - 2025-01-27

### Changed
- ✅ **README.md** - Formatação melhorada
  - Lista de recursos com bullets padronizados
  - Melhor legibilidade da lista de recursos

### Notes
- Pequeno ajuste de formatação para melhor apresentação

## [2.4.4] - 2025-01-27

### Fixed
- ✅ **padesPrepareFromFileWithVisual()** - Corrigido envio de visual_data
  - Agora usa JSON payload ao invés de multipart/form-data
  - visual_data enviado como array (não JSON string)
  - Corrige erro "The visual data must be an array"

### Notes
- Usa fileBase64 + JSON ao invés de attach() + multipart
- Permite envio correto de arrays aninhados

## [2.4.1] - 2025-10-18

### Added
- ✅ **GedApiClient::padesPrepareFromFileWithVisual()** - Novo método para preparar PDF com visual_data
  - Aceita array visual_data com coordenadas de retângulo
  - Envia automaticamente como multipart/form-data
  - Marca assinatura como visível automaticamente

### Notes
- Método compatível com PadesStarterController do CamaraTech
- Retrocompatível com `padesPrepareFromFile()` (sem visual)

## [2.4.0] - 2025-10-18

### Added
- ✅ **Visual Signature Support (Optional)** - Suporte completo para assinaturas visuais opcionais
  - Novo método `setVisualRepresentationWithRect()` em `PadesSignatureStarter`
  - Aceita coordenadas de retângulo (x, y, width, height, page)
  - Parâmetros opcionais: signer_name, reason, location, contact
  - Flags de exibição: show_signer_name, show_date, show_reason
- ✅ **Backend Integration** - Scripts Python atualizados
  - `prepare_pdf.py` - Aceita `--visual-data` com coordenadas
  - `add_field_endesive_clone.py` - Suporte a assinaturas visuais incrementais
  - Conversão automática de coordenadas (top-left → bottom-left)
- ✅ **Full Compatibility** - Retrocompatível com formato anterior (`anots`)

### Changed
- 📝 **PadesController** - Atualizado para processar `visual_data` opcional
- 📝 **SignatureDialog** - Frontend envia coordenadas do retângulo de assinatura

### Notes
- Assinaturas podem ser **invisíveis** (padrão) ou **visíveis** (com coordenadas)
- 100% opcional - usuário decide se adiciona aparência visual
- Coordenadas são convertidas automaticamente para o sistema PDF

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
