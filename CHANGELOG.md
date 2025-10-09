# Changelog

All notable changes to `ged-api-laravel` will be documented in this file.

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
- Laravel >= 10.0
- ged/api-client ^1.0
- PHP >= 8.1

