# 🔧 Suporte a Certificados Legacy (OpenSSL 3.x)

## 📋 Problema

Certificados ICP-Brasil antigos usam algoritmos de criptografia deprecados (3DES, RC2) que são **bloqueados por padrão** no OpenSSL 3.x.

**Erro comum:**
```
Erro ao ler certificado PFX. Senha incorreta ou arquivo corrompido.
```

Mesmo com senha correta! ❌

---

## ✅ Solução Automática

Este SDK **já inclui suporte automático** via arquivo `config/openssl_legacy.cnf`.

### 🎯 Ordem de Busca:

1. **Projeto raiz** (se publicado): `base_path('openssl_legacy.cnf')`
2. **Vendor do SDK** (fallback automático): `vendor/ged/api-laravel/config/openssl_legacy.cnf`
3. **Sistema**: `/etc/ssl/openssl_legacy.cnf`

**Funciona sem configuração adicional!** 🎉

---

## 🔄 Publicar Configuração (Opcional)

Se quiser customizar, publique o arquivo para a raiz do projeto:

```bash
php artisan vendor:publish --tag=ged-api-openssl
```

Isso cria: `openssl_legacy.cnf` na raiz do projeto Laravel.

---

## 🧪 Testar

```php
use Ged\ApiLaravel\Support\CertificateHelper;

$helper = new CertificateHelper();

// Carrega PFX com algoritmos legacy (3DES, RC2, etc)
$pfxData = $helper->loadPfx('/path/to/cert.pfx', 'senha');

echo "Certificado carregado com sucesso!";
```

---

## 📝 Detalhes Técnicos

O arquivo `openssl_legacy.cnf` ativa os providers:
- **default**: Algoritmos modernos (AES, SHA-256, etc)
- **legacy**: Algoritmos antigos (3DES, RC2, MD5, etc)

**Ambos ficam ativos simultaneamente**, garantindo compatibilidade total! ✨

---

## 🐛 Troubleshooting

### Erro persiste?

Verifique se o arquivo existe:

```bash
# Fallback automático (sempre disponível)
ls -la vendor/ged/api-laravel/config/openssl_legacy.cnf

# Ou publicado
ls -la openssl_legacy.cnf
```

### OpenSSL não encontra o arquivo?

Force manualmente:

```php
putenv("OPENSSL_CONF=" . base_path('openssl_legacy.cnf'));
```

---

## 📚 Referências

- [OpenSSL 3.0 Migration Guide](https://www.openssl.org/docs/man3.0/man7/migration_guide.html)
- [OpenSSL Legacy Provider](https://www.openssl.org/docs/man3.0/man7/OSSL_PROVIDER-legacy.html)

