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

## 🎯 Uso Básico

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

## 📋 Métodos Disponíveis

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

