# Release v2.2.0 - GitHub Instructions

## 🔐 v2.2.0 - Adobe Reader Certificate Validation Fix

### ✅ Fixed
- **Adobe Reader Validation**: Corrigido erro "certificado do assinante é inválido"
- **Complete Certificate Chain**: Cadeia completa de certificados agora incluída no CMS
- **Example Updated**: SignatureController.php atualizado com implementação correta

### 🔍 Problem
Adobe Reader rejeitava assinaturas válidas com erro "O certificado do assinante é inválido", enquanto ITI Verificador validava normalmente.

### 💡 Root Cause
A cadeia de certificados intermediários não estava sendo extraída do PFX e enviada ao backend, resultando em um CMS incompleto.

### ✅ Solution
- Usar `CertificateHelper::loadPfxFromContent()` para extrair cadeia completa
- Enviar array `$chainBase64` via método `padesInjectPkcs1()`
- Backend já estava preparado para receber e incluir no CMS

### 📊 Technical Details
- Certificados ICP-Brasil geralmente contêm 3-4 certificados:
  1. Signatário (end-entity)
  2. Intermediário(s) (1 ou mais ACs)
  3. Raiz (AC Raiz Brasileira)
- Adobe Reader exige cadeia completa para validação
- ITI Verificador é mais tolerante com cadeias incompletas

### 🔧 How to Use

```php
// Carregar PFX e extrair cadeia completa
$certHelper = new \Ged\ApiLaravel\Support\CertificateHelper();
$certData = $certHelper->loadPfxFromContent($pfxData, $password);

// Converter cadeia para base64
$chainBase64 = array_map('base64_encode', $certData['chain']);

// Assinar
$toBeSignedDer = hex2bin($params['to_be_signed_der_hex']);
openssl_sign($toBeSignedDer, $cmsDer, $certData['privateKey'], OPENSSL_ALGO_SHA256);

// Enviar com cadeia completa
$inject = GedApi::padesInjectPkcs1(
    $documentId, 
    $params['field_name'], 
    bin2hex($cmsDer),
    base64_encode($certData['certificate']),
    $chainBase64  // ← Cadeia completa!
);
```

### 📚 References
- Example: `examples/SignatureController.php`
- Full changelog: `CHANGELOG.md`
- Documentation: `SOLUCAO_ADOBE_CERTIFICADO_INVALIDO.md` (no projeto principal)

### 🧪 Testing
```bash
# Extrair e verificar cadeia de certificados
python fix_certificate_chain.py --pfx certificado.pfx --password senha

# Validar assinatura
- ITI Verificador: https://verificador.iti.gov.br/
- Adobe Reader: Abrir PDF e verificar painel de assinaturas
```

### 📦 Installation

```bash
composer require ged/api-laravel:^2.2
```

Or update existing installation:

```bash
composer update ged/api-laravel
```

---

**Validates in**: ✅ Adobe Reader | ✅ ITI Verificador | ✅ ICP-Brasil compliant

**Tested with**: MUNICIPIO DE RIO DOS BOIS (AC SOLUTI Multipla v5)

---

## 📋 Checklist for Release

- [x] Code changes committed
- [x] CHANGELOG.md updated
- [x] Version tagged (v2.2.0)
- [x] Pushed to GitHub
- [ ] Create GitHub Release
- [ ] Verify Packagist sync
- [ ] Test in production project

