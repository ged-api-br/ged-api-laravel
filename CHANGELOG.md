# Changelog - ged/api-laravel

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
