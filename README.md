# 📦 GED API Laravel Package

Laravel Package oficial para integração com o **GED.API.BR**
Sistema de Assinatura Digital PAdES com padrão ICP-Brasil;
Suporte a Assinaturas Incrementais;
Controle de Atualizações Incrementais - DocMDP - (Modification Detection and Prevention)
Suporte a Posicionamento da Representação Visual da Assinatura;

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ged/api-laravel.svg)](https://packagist.org/packages/ged/api-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/ged/api-laravel.svg)](https://packagist.org/packages/ged/api-laravel)

## 🚀 Instalação

```bash
composer require ged/api-laravel
```

O Service Provider será registrado automaticamente.

## ⚙️ Configuração

Publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag=ged-api-config
```

Configure no seu `.env` a chave da API:

```env
GED_API_KEY=sua-chave-api
```

> A URL base já está configurada por padrão. Apenas configure a chave da API se necessário.

## 📖 Uso Básico

```php
use Ged\ApiLaravel\Facades\GedApi;

// Preparar PDF para assinatura
$result = GedApi::padesPrepareFromFile('/path/to/document.pdf');

// Injetar assinatura PKCS#1
$result = GedApi::padesInjectPkcs1($documentId, $signatureBase64, $certificateBase64);

// Finalizar documento
$result = GedApi::padesFinalize($documentId);
```

## 🤝 Suporte

- Email: suporte@ged.api.br
- Email: welinaldo@gmail.com

*Disponível apenas para Laravel com Certificado A1 (por enquanto...)

