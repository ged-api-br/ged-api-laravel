# GED API Laravel Package

Laravel Package para integracao com GED API.

## Instalacao

```bash
composer require ged/api-laravel
php artisan vendor:publish --tag=ged-api-config
```

## Configuracao (.env)

```env
GED_API_KEY=sua-chave-api
GED_API_URL=https://ged.api.br/api
```

## Assinatura Digital

```php
use Ged\ApiLaravel\Facades\GedApi;

// Assinar PDF com certificado A1
$result = GedApi::sign('/caminho/documento.pdf', '/caminho/certificado.pfx', 'senha');

// PDF assinado
file_put_contents('assinado.pdf', base64_decode($result['signed_pdf_base64']));

// Ou via download URL
// $result['download_url']
```

### Com assinatura visual

```php
$result = GedApi::sign('/caminho/doc.pdf', '/caminho/cert.pfx', 'senha', [
    'rect' => [100, 100, 300, 150],
    'page' => 1,
    'signer_name' => 'Joao da Silva',
]);
```

### A partir de base64

```php
$result = GedApi::signFromBase64($pdfBase64, $pfxContent, 'senha');
```

## Certificados

```php
// Emitir certificado
$result = GedApi::issueCertificate('Joao da Silva', '123.456.789-00', 'joao@email.com', 'senha123');

// Listar
$result = GedApi::listCertificates(page: 1, status: 'active');

// Revogar
$result = GedApi::revokeCertificate(serial: '12345', reason: 'Comprometido');

// Verificar validade
$result = GedApi::verifyCertificate(serial: '12345');
```

## Requisitos

- PHP >= 8.0
- Laravel >= 9.0
- ext-openssl

## Suporte

- suporte@ged.api.br
