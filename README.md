# 📦 GED API Laravel Package

Laravel Package oficial para integração com o **GED.API.BR** — Sistema de Assinatura Digital ICP-Brasil.

---

## 🚀 Instalação

```bash
composer require ged/api-laravel
```

O Service Provider será registrado automaticamente.

---

## ⚙️ Configuração

Publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag=ged-api-config
```

Configure as variáveis de ambiente no `.env`:

```env
GED_API_BASE_URL=https://ged.api.br/api/
GED_API_KEY=pk_live_seu_token_aqui
GED_API_POLICY_OID=2.16.76.1.7.1.11.1.1
GED_API_POLICY_URI=https://iti.gov.br/politica/pa.pdf
GED_API_POLICY_HASH=a1b2c3d4...
```

---

## 🎯 Uso Básico (legado)

### Usando a Facade

```php
use Ged\ApiLaravel\Facades\GedApi;

// Inicia assinatura
$start = GedApi::startSignature(
    base64_encode(file_get_contents('contrato.pdf')),
    config('ged-api.default_policy_oid')
);

// Assina localmente
openssl_pkcs12_read(file_get_contents('certificado.pfx'), $certs, 'senha');
openssl_sign(
    base64_decode($start['signedAttrsDerBase64']), 
    $signature, 
    $certs['pkey'], 
    OPENSSL_ALGO_SHA256
);

// Finaliza assinatura
$complete = GedApi::completeSignature(
    $start['pdfId'],
    base64_encode($signature),
    base64_encode($certs['cert'])
);

// Salva PDF assinado
Storage::put('assinado.pdf', base64_decode($complete['signedPdfBase64']));
```

### Usando Dependency Injection

```php
use Ged\ApiClient\GedApiClient;

class DocumentController extends Controller
{
    public function __construct(
        protected GedApiClient $gedApi
    ) {}

    public function sign(Request $request)
    {
        $start = $this->gedApi->startSignature(
            $request->input('pdf_base64'),
            config('ged-api.default_policy_oid')
        );

        return response()->json($start);
    }
}
```

---

## 📋 Métodos Disponíveis (legado)

Todos os métodos do `ged/api-client` estão disponíveis através da Facade:

### `GedApi::startSignature(string $pdfBase64, string $policyOid): array`
Inicia o processo de assinatura

### `GedApi::completeSignature(string $pdfId, string $signatureBase64, string $certBase64): array`
Finaliza a assinatura

### `GedApi::verifySignature(string $pdfBase64): array`
Verifica PDF assinado

---

## 🔐 Políticas ICP-Brasil

Configure no `.env`:

| Política | OID | ENV |
|----------|-----|-----|
| **AD-RB** | 2.16.76.1.7.1.11.1.1 | `GED_API_POLICY_OID=2.16.76.1.7.1.11.1.1` |
| **AD-RT** | 2.16.76.1.7.1.11.1.2 | `GED_API_POLICY_OID=2.16.76.1.7.1.11.1.2` |
| **AD-RC** | 2.16.76.1.7.1.11.1.3 | `GED_API_POLICY_OID=2.16.76.1.7.1.11.1.3` |

---

## 🛠️ Requisitos

- PHP >= 8.1
- Laravel >= 10.0
- ext-openssl

---

## 📄 Licença

MIT License - veja [LICENSE](LICENSE) para detalhes.

---

## 🤝 Suporte

- **Website**: https://ged.api.br
- **Documentação**: https://docs.ged.api.br
- **Email**: contato@ged.api.br

---

**Desenvolvido pela equipe do GED.API.BR**

## 📄 Licença

MIT

---

## ✨ **Assinatura Digital PAdES em 3 Fases** (Novo - Recomendado)

O SDK agora suporta o **padrão de 3 fases** para assinatura digital PAdES, garantindo máxima segurança ao manter a chave privada sempre no cliente.

### 🎯 Por Que 3 Fases?

- ✅ **Segurança Máxima:** Chave privada nunca sai do cliente
- ✅ **Compatibilidade:** Funciona com certificados A1 e A3 (token/smartcard)
- ✅ **ICP-Brasil:** Suporta políticas oficiais homologadas pelo ITI
- ✅ **Flexibilidade:** Permite assinatura remota e visual

---

### 📋 Fluxo das 3 Fases

```
┌──────────────────────────────────────────────────────┐
│ FASE 1: STARTER (Cliente → Servidor)                │
│ ────────────────────────────────────────────────     │
│ • Cliente envia: PDF + Certificado Público          │
│ • Servidor retorna: Token + Hash para assinar       │
└──────────────────────────────────────────────────────┘
                        ↓
┌──────────────────────────────────────────────────────┐
│ FASE 2: SIGN (Cliente local)                        │
│ ────────────────────────────────────────────────     │
│ • Cliente assina o hash com chave privada           │
│ • Constrói estrutura CMS/PKCS#7                      │
└──────────────────────────────────────────────────────┘
                        ↓
┌──────────────────────────────────────────────────────┐
│ FASE 3: FINISH (Cliente → Servidor)                 │
│ ────────────────────────────────────────────────────     │
│ • Cliente envia: CMS assinado                        │
│ • Servidor retorna: PDF assinado completo            │
└──────────────────────────────────────────────────────┘
```

---

## 🚀 FASE 1: Iniciar Assinatura (PadesSignatureStarter)

### Exemplo Rápido

```php
use Ged\ApiLaravel\GedApiClient;
use Ged\ApiLaravel\PadesSignatureStarter;
use Ged\ApiLaravel\Constants\StandardSignaturePolicies;
use Ged\ApiLaravel\Support\CertificateHelper;

// 1. Criar cliente da API
$client = new GedApiClient(
    baseUri: 'https://sua-api.com.br/api',
    apiKey: 'sua-api-key'
);

// 2. Carregar certificado digital (.pfx)
$certHelper = new CertificateHelper();
$certData = $certHelper->loadPfx('/path/to/certificado.pfx', 'senha123');

// 3. Configurar e iniciar assinatura
$starter = new PadesSignatureStarter($client);
$starter->setPdfToSignFromPath('/path/to/documento.pdf');
$starter->setSignerCertificateRaw($certData['certificate']);
$starter->setSignaturePolicy(StandardSignaturePolicies::PADES_ICPBR_ADR_BASICA);

// 4. Iniciar (retorna parâmetros para Fase 2)
$params = $starter->start();

// Resultado:
// $params->token           → Token único da sessão
// $params->toSignHash      → Hash que deve ser assinado
// $params->toSignData      → Dados completos para assinar
// $params->digestAlgorithmOid → OID do algoritmo (SHA-256, etc.)
```

### Políticas de Assinatura Disponíveis

#### **Políticas ICP-Brasil (Oficiais)**

```php
use Ged\ApiLaravel\Constants\StandardSignaturePolicies;

// PAdES ICP-Brasil - Assinatura Digital com Referências Básicas
// OID: 2.16.76.1.7.1.11.1.1 (DOC-ICP-15.04)
StandardSignaturePolicies::PADES_ICPBR_ADR_BASICA

// PAdES ICP-Brasil - Assinatura Digital com Referências de Tempo
// OID: 2.16.76.1.7.1.11.1.2 (DOC-ICP-15.04)
StandardSignaturePolicies::PADES_ICPBR_ADR_TEMPO

// CAdES ICP-Brasil - Assinatura Digital com Referências Básicas
// OID: 2.16.76.1.7.1.1.2.1 (DOC-ICP-15.03)
StandardSignaturePolicies::CADES_ICPBR_ADR_BASICA

// CAdES ICP-Brasil - Assinatura Digital com Referências de Tempo
// OID: 2.16.76.1.7.1.2.2.1 (DOC-ICP-15.03)
StandardSignaturePolicies::CADES_ICPBR_ADR_TEMPO
```

#### **Políticas Genéricas (Não ICP-Brasil)**

```php
// PAdES Básico (para uso geral)
StandardSignaturePolicies::PADES_BASIC

// PAdES com Timestamp
StandardSignaturePolicies::PADES_WITH_TIMESTAMP

// PAdES compatível com Adobe Reader
StandardSignaturePolicies::PADES_ADOBE_COMPATIBLE
```

### Métodos de Configuração

#### **Configurar PDF**

```php
// A partir de arquivo
$starter->setPdfToSignFromPath('/path/to/file.pdf');

// A partir de base64
$starter->setPdfToSignFromContentBase64($base64Content);

// A partir de conteúdo bruto
$starter->setPdfToSignFromContentRaw($binaryContent);

// A partir de URL
$starter->setPdfToSignFromUrl('https://example.com/document.pdf');

// A partir de resultado anterior
$starter->setPdfToSignFromResult($previousToken);
```

#### **Configurar Certificado**

```php
// Formato DER (binário)
$starter->setSignerCertificateRaw($certDer);

// Formato base64
$starter->setSignerCertificateBase64($certBase64);

// Formato PEM
$starter->setSignerCertificatePem($certPem);

// A partir de arquivo
$starter->setSignerCertificateFromFile('/path/to/cert.cer');
```

#### **Configurar Representação Visual (Opcional)**

```php
// Modo simples
$starter->setSimpleVisualRepresentation(
    text: 'Assinado digitalmente por {{name}} em {{date}}',
    fontSize: 10
);

// Modo avançado
$starter->setVisualRepresentation([
    'text' => [
        'text' => 'Assinado digitalmente por {{name}}',
        'fontSize' => 10,
        'includeSigningTime' => true,
    ],
    'position' => [
        'pageNumber' => -1, // última página
        'auto' => 'newPage', // ou 'leftMargin', 'rightMargin'
    ],
]);
```

### Informações sobre Políticas

```php
use Ged\ApiLaravel\Constants\StandardSignaturePolicies;

// Obter OID oficial
$oid = StandardSignaturePolicies::getOid('pades-icpbr-adr-basica');
// Retorna: '2.16.76.1.7.1.11.1.1'

// Verificar se requer timestamp
$requiresTimestamp = StandardSignaturePolicies::requiresTimestamp('pades-icpbr-adr-tempo');
// Retorna: true

// Verificar se é ICP-Brasil
$isIcpBrasil = StandardSignaturePolicies::isIcpBrasil('pades-icpbr-adr-basica');
// Retorna: true

// Obter informações completas
$info = StandardSignaturePolicies::getInfo('pades-icpbr-adr-basica');
// Retorna: ['id', 'name', 'oid', 'requiresTimestamp', 'isIcpBrasil', 'type']
```

### Trabalhando com Certificados

```php
use Ged\ApiLaravel\Support\CertificateHelper;

$certHelper = new CertificateHelper();

// Carregar certificado PFX/P12 (A1)
$certData = $certHelper->loadPfx('/path/to/cert.pfx', 'senha');
// Retorna: ['certificate', 'certificatePem', 'privateKey', 'privateKeyPem', 'chain']

// Extrair informações do certificado
$info = $certHelper->extractInfo($certData['certificate']);
// Retorna: ['subjectName', 'issuerName', 'serialNumber', 'validityStart', 
//           'validityEnd', 'emailAddress', 'cpf', 'cnpj', 'commonName', etc.]

// Verificar validade
$isValid = $certHelper->isValid($certData['certificate']);

// Converter formatos
$certPem = $certHelper->derToPem($certDer);
$certDer = $certHelper->pemToDer($certPem);
```

---

## 📚 Exemplos Completos

Veja os exemplos detalhados em:
- `examples/PadesSignaturePhase1Example.php` - FASE 1 (Starter)
- `examples/PadesSignaturePhase2Example.php` - FASE 2 (Sign) - Em breve
- `examples/PadesSignaturePhase3Example.php` - FASE 3 (Finish) - Em breve

---

## 🔄 Fluxo Legado (4 fases)

### Facade

```php
use Ged\ApiLaravel\Facades\GedApi;

// 1) Prepare (com opção de anotações futuras)
$prepare = GedApi::padesPrepareFromFile(storage_path('app/contrato.pdf'), false, $anots ?? null);
$documentId = $prepare['document_id'];

// 2) Cms Params (enviar certificado do signatário em DER/base64)
$signerCertDerBase64 = base64_encode($certDer);
$params = GedApi::padesCmsParams($documentId, $signerCertDerBase64);
// Assine $params['to_be_signed_der_hex'] localmente

// 3) Inject (duas opções)
// a) Enviando CMS DER pronto (modo atual)
$inject = GedApi::padesInject($documentId, $params['field_name'], $cmsDerHex);
// b) Enviando assinatura crua PKCS#1 + certificado (servidor monta CMS)
//$inject = GedApi::padesInjectPkcs1($documentId, $params['field_name'], $pkcs1DerHex, base64_encode($certDer));

// 4) Finalize
$final = GedApi::padesFinalize($documentId);
Storage::put('assinado_pades.pdf', base64_decode($final['pdf_base64']));
```

### Métodos da Facade (legado)

- `padesPrepareFromBase64(string $pdfBase64, bool $visible = false): array`
- `padesPrepareFromFile(string $filePath, bool $visible = false): array`
- `padesCmsParams(string $documentId, ?string $fieldName = null): array`
- `padesInject(string $documentId, string $fieldName, string $signatureDerHex): array`
- `padesFinalize(string $documentId): array`

