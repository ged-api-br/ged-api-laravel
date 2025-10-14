<?php

/**
 * ============================================================================
 * EXEMPLO: PAdES Assinatura Digital - FASE 1 (Starter)
 * ============================================================================
 * 
 * Este exemplo demonstra como usar o SDK GED API Laravel para iniciar
 * o processo de assinatura digital PAdES (FASE 1).
 * 
 * FASE 1: STARTER (Cliente → Servidor)
 * - Envia PDF e certificado para o servidor
 * - Servidor prepara o PDF e calcula o hash
 * - Retorna token e parâmetros para assinatura local
 * 
 * Requisitos:
 * - PHP 8.0+
 * - Composer com o pacote ged/api-laravel instalado
 * - Certificado digital A1 (.pfx) ou A3
 * - Conexão com a GED API
 * ============================================================================
 */

require __DIR__ . '/../vendor/autoload.php';

use Ged\ApiLaravel\GedApiClient;
use Ged\ApiLaravel\PadesSignatureStarter;
use Ged\ApiLaravel\Constants\StandardSignaturePolicies;
use Ged\ApiLaravel\Support\CertificateHelper;
use Ged\ApiLaravel\Exceptions\GedApiException;

// ============================================================================
// CONFIGURAÇÃO
// ============================================================================

$config = [
    // URL da sua GED API
    'api_url' => 'http://localhost:8000/api',
    
    // API Key (obtenha no painel da GED API)
    'api_key' => 'sua-api-key-aqui',
    
    // Caminho do PDF a ser assinado
    'pdf_path' => __DIR__ . '/documents/contrato.pdf',
    
    // Certificado Digital A1 (.pfx)
    'certificate_path' => __DIR__ . '/certificates/certificado.pfx',
    'certificate_password' => 'senha123',
    
    // Política de assinatura
    'signature_policy' => StandardSignaturePolicies::PADES_ICPBR_ADR_BASICA,
];

// ============================================================================
// EXEMPLO 1: Assinatura Básica (sem representação visual)
// ============================================================================

echo "============================================\n";
echo "EXEMPLO 1: Assinatura Básica\n";
echo "============================================\n\n";

try {
    // 1. Criar cliente da API
    echo "1. Conectando à GED API...\n";
    $client = new GedApiClient($config['api_url'], $config['api_key']);
    echo "   ✓ Conectado!\n\n";
    
    // 2. Carregar certificado
    echo "2. Carregando certificado digital...\n";
    $certHelper = new CertificateHelper();
    $certData = $certHelper->loadPfx(
        $config['certificate_path'],
        $config['certificate_password']
    );
    echo "   ✓ Certificado carregado!\n";
    
    // Extrair informações do certificado
    $certInfo = $certHelper->extractInfo($certData['certificate']);
    echo "   Titular: {$certInfo['commonName']}\n";
    echo "   CPF: {$certInfo['cpf']}\n";
    echo "   Validade: {$certInfo['validityStart']} até {$certInfo['validityEnd']}\n\n";
    
    // 3. Configurar a assinatura (FASE 1)
    echo "3. Configurando assinatura...\n";
    $starter = new PadesSignatureStarter($client);
    
    // Definir o PDF
    $starter->setPdfToSignFromPath($config['pdf_path']);
    echo "   ✓ PDF configurado: " . basename($config['pdf_path']) . "\n";
    echo "   ✓ Tamanho: " . $starter->getPdfFormattedSize() . "\n";
    
    // Definir o certificado (apenas parte pública)
    $starter->setSignerCertificateRaw($certData['certificate']);
    echo "   ✓ Certificado configurado\n";
    
    // Definir a política de assinatura
    $starter->setSignaturePolicy($config['signature_policy']);
    echo "   ✓ Política: " . $starter->getSignaturePolicyName() . "\n";
    
    if ($starter->requiresTimestamp()) {
        echo "   ⚠ Esta política requer timestamp!\n";
    }
    
    echo "\n";
    
    // 4. Iniciar o processo (FASE 1)
    echo "4. Iniciando processo de assinatura...\n";
    $params = $starter->start();
    echo "   ✓ Processo iniciado com sucesso!\n\n";
    
    // 5. Exibir parâmetros retornados
    echo "5. Parâmetros recebidos:\n";
    echo "   Token: " . $params->token . "\n";
    echo "   Algoritmo: " . $params->getDigestAlgorithmName() . "\n";
    echo "   Hash (primeiros 32 chars): " . substr($params->toSignHash, 0, 32) . "...\n";
    
    if ($params->getCertificateInfo()) {
        echo "   Signatário: " . $params->getSignerName() . "\n";
        echo "   Email: " . $params->getSignerEmail() . "\n";
    }
    
    echo "\n";
    echo "✓ FASE 1 CONCLUÍDA COM SUCESSO!\n";
    echo "\nPróximos passos:\n";
    echo "- FASE 2: Assinar o hash localmente com a chave privada\n";
    echo "- FASE 3: Enviar a assinatura de volta para finalizar\n\n";
    
    // Salvar parâmetros para uso posterior
    file_put_contents(
        __DIR__ . '/temp/signature_params.json',
        json_encode($params->toArray(), JSON_PRETTY_PRINT)
    );
    echo "Parâmetros salvos em: temp/signature_params.json\n";
    
} catch (GedApiException $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Exception $e) {
    echo "\n❌ ERRO INESPERADO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n============================================\n\n";

// ============================================================================
// EXEMPLO 2: Assinatura com Representação Visual
// ============================================================================

echo "============================================\n";
echo "EXEMPLO 2: Assinatura com Visual\n";
echo "============================================\n\n";

try {
    $client = new GedApiClient($config['api_url'], $config['api_key']);
    $certHelper = new CertificateHelper();
    $certData = $certHelper->loadPfx(
        $config['certificate_path'],
        $config['certificate_password']
    );
    
    $starter = new PadesSignatureStarter($client);
    $starter->setPdfToSignFromPath($config['pdf_path']);
    $starter->setSignerCertificateRaw($certData['certificate']);
    $starter->setSignaturePolicy(StandardSignaturePolicies::PADES_ICPBR_ADR_BASICA);
    
    // Configurar representação visual
    echo "Configurando representação visual...\n";
    $starter->setSimpleVisualRepresentation(
        text: 'Assinado digitalmente por {{name}} em {{date}}',
        fontSize: 10
    );
    echo "✓ Representação visual configurada!\n\n";
    
    // Ou usar configuração avançada:
    /*
    $starter->setVisualRepresentation([
        'text' => [
            'text' => 'Assinado digitalmente por {{name}}',
            'fontSize' => 10,
            'includeSigningTime' => true,
        ],
        'position' => [
            'pageNumber' => -1, // última página
            'auto' => 'newPage', // ou 'leftMargin', 'rightMargin'
            // Ou posição manual:
            // 'manual' => [
            //     'left' => 50,
            //     'bottom' => 50,
            //     'width' => 200,
            //     'height' => 100,
            // ],
        ],
        'image' => [
            'resource' => 'logo.png', // opcional
            'opacity' => 0.5,
        ],
    ]);
    */
    
    echo "Iniciando assinatura com visual...\n";
    $params = $starter->start();
    echo "✓ Sucesso! Token: " . $params->token . "\n";
    
} catch (GedApiException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n============================================\n\n";

// ============================================================================
// EXEMPLO 3: Usando Diferentes Políticas
// ============================================================================

echo "============================================\n";
echo "EXEMPLO 3: Diferentes Políticas\n";
echo "============================================\n\n";

$availablePolicies = [
    StandardSignaturePolicies::PADES_ICPBR_ADR_BASICA,
    StandardSignaturePolicies::PADES_ICPBR_ADR_TEMPO,
    StandardSignaturePolicies::PADES_BASIC,
    StandardSignaturePolicies::PADES_WITH_TIMESTAMP,
    StandardSignaturePolicies::PADES_ADOBE_COMPATIBLE,
];

echo "Políticas disponíveis:\n\n";

foreach ($availablePolicies as $policy) {
    $info = StandardSignaturePolicies::getInfo($policy);
    
    echo "• " . $info['name'] . "\n";
    echo "  ID: " . $info['id'] . "\n";
    
    if ($info['oid']) {
        echo "  OID: " . $info['oid'] . " (Oficial ICP-Brasil)\n";
    } else {
        echo "  OID: Não possui (política genérica)\n";
    }
    
    echo "  Tipo: " . $info['type'] . "\n";
    echo "  Timestamp: " . ($info['requiresTimestamp'] ? 'Sim' : 'Não') . "\n";
    echo "  ICP-Brasil: " . ($info['isIcpBrasil'] ? 'Sim' : 'Não') . "\n";
    echo "\n";
}

echo "============================================\n\n";

// ============================================================================
// EXEMPLO 4: Tratamento de Erros
// ============================================================================

echo "============================================\n";
echo "EXEMPLO 4: Tratamento de Erros\n";
echo "============================================\n\n";

try {
    $client = new GedApiClient($config['api_url'], $config['api_key']);
    $starter = new PadesSignatureStarter($client);
    
    // Tentar iniciar sem configurar nada (vai gerar erro)
    echo "Tentando iniciar sem configurar PDF...\n";
    $params = $starter->start();
    
} catch (GedApiException $e) {
    echo "✓ Erro capturado corretamente:\n";
    echo "  Mensagem: " . $e->getMessage() . "\n";
    echo "  Código: " . $e->getCode() . "\n";
}

echo "\n";

try {
    $client = new GedApiClient($config['api_url'], $config['api_key']);
    $starter = new PadesSignatureStarter($client);
    
    // Tentar usar arquivo inexistente
    echo "Tentando usar arquivo inexistente...\n";
    $starter->setPdfToSignFromPath('/caminho/inexistente.pdf');
    
} catch (GedApiException $e) {
    echo "✓ Erro capturado corretamente:\n";
    echo "  Mensagem: " . $e->getMessage() . "\n";
}

echo "\n============================================\n\n";

// ============================================================================
// EXEMPLO 5: Usando Conteúdo Base64 (ao invés de arquivo)
// ============================================================================

echo "============================================\n";
echo "EXEMPLO 5: PDF em Base64\n";
echo "============================================\n\n";

try {
    $client = new GedApiClient($config['api_url'], $config['api_key']);
    $certHelper = new CertificateHelper();
    $certData = $certHelper->loadPfx(
        $config['certificate_path'],
        $config['certificate_password']
    );
    
    // Ler PDF como base64
    $pdfContent = file_get_contents($config['pdf_path']);
    $pdfBase64 = base64_encode($pdfContent);
    
    echo "PDF em base64 (primeiros 50 chars): " . substr($pdfBase64, 0, 50) . "...\n\n";
    
    $starter = new PadesSignatureStarter($client);
    
    // Usar base64 ao invés de caminho
    $starter->setPdfToSignFromContentBase64($pdfBase64);
    $starter->setSignerCertificateRaw($certData['certificate']);
    $starter->setSignaturePolicy(StandardSignaturePolicies::PADES_BASIC);
    
    echo "Iniciando assinatura com base64...\n";
    $params = $starter->start();
    echo "✓ Sucesso! Token: " . $params->token . "\n";
    
} catch (GedApiException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n============================================\n\n";

// ============================================================================
// RESUMO
// ============================================================================

echo "============================================\n";
echo "RESUMO - FASE 1 (Starter)\n";
echo "============================================\n\n";

echo "O que foi demonstrado:\n";
echo "1. ✓ Conectar à GED API\n";
echo "2. ✓ Carregar certificado digital (.pfx)\n";
echo "3. ✓ Configurar PDF para assinatura\n";
echo "4. ✓ Escolher política de assinatura\n";
echo "5. ✓ Adicionar representação visual (opcional)\n";
echo "6. ✓ Iniciar o processo (obter token e hash)\n";
echo "7. ✓ Tratar erros adequadamente\n";
echo "8. ✓ Usar diferentes formatos (arquivo/base64)\n";
echo "\n";

echo "Próximas fases:\n";
echo "• FASE 2: Assinar o hash localmente (openssl_sign)\n";
echo "• FASE 3: Enviar assinatura de volta (PadesSignatureFinisher)\n";
echo "\n";

echo "Documentação completa:\n";
echo "• README.md\n";
echo "• /examples/PadesSignaturePhase2Example.php (em breve)\n";
echo "• /examples/PadesSignaturePhase3Example.php (em breve)\n";
echo "\n";

echo "============================================\n";


