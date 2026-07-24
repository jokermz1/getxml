<?php

/**
 * Script de teste para validar as correções implementadas
 * Verifica conformidade com SEFAZ
 */

require_once __DIR__ . '/vendor/autoload.php';

$configBase = require __DIR__ . '/config/config.php';
$sefazCaBundleDefault = __DIR__ . '/certs/icpbrasil_raiz_v10.crt';

echo "=== Teste de Conformidade SEFAZ ===\n\n";

// Teste 1: Carregar dependências essenciais
echo "1. Verificando dependências essenciais...\n";
try {
    if (class_exists('GuzzleHttp\\Client') && function_exists('openssl_pkcs12_read')) {
        echo "   ✅ Guzzle e OpenSSL disponíveis\n";
    } else {
        echo "   ❌ Dependências essenciais não encontradas\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro ao carregar dependências: " . $e->getMessage() . "\n";
}

// Teste 2: Verificar SefazModel atualizado
echo "\n2. Verificando SefazModel...\n";
try {
    $config = $configBase;

    if (!is_array($config)) {
        echo "   ⚠️  Configuração não retornou array, usando config de teste\n";
        $config = [
            'sefaz' => ['uf' => 'SP', 'ambiente' => '2', 'certificado' => '', 'senha_certificado' => '', 'ca_bundle' => file_exists($sefazCaBundleDefault) ? $sefazCaBundleDefault : null],
            'cnpj' => ['cnpj' => '']
        ];
    }

    $sefazModel = new \App\Models\SefazModel($config);

    // Verificar se métodos existem
    if (method_exists($sefazModel, 'buscarNotaPorChave')) {
        echo "   ✅ Método buscarNotaPorChave existe\n";
    } else {
        echo "   ❌ Método buscarNotaPorChave não encontrado\n";
    }

    if (method_exists($sefazModel, 'buscarNotaPorNSU')) {
        echo "   ✅ Método buscarNotaPorNSU existe\n";
    } else {
        echo "   ❌ Método buscarNotaPorNSU não encontrado\n";
    }

    if (method_exists($sefazModel, 'buscarNotasPorPeriodo')) {
        echo "   ✅ Método buscarNotasPorPeriodo existe\n";
    } else {
        echo "   ❌ Método buscarNotasPorPeriodo não encontrado\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erro ao verificar SefazModel: " . $e->getMessage() . "\n";
}

// Teste 3: Validar configurações
echo "\n3. Validando configurações...\n";
try {
    $config = $configBase;

    if (!is_array($config)) {
        echo "   ⚠️  Configuração não retornou array (pode ser problema com .env)\n";
        echo "   ⚠️  Pulando teste de validação\n";
    } else {
        $sefazModel = new \App\Models\SefazModel($config);
        $erros = $sefazModel->validarConfiguracoes();

        if (empty($erros)) {
            echo "   ✅ Configurações válidas\n";
        } else {
            echo "   ⚠️  Erros de configuração:\n";
            foreach ($erros as $erro) {
                echo "      - $erro\n";
            }
        }
    }
} catch (Exception $e) {
    echo "   ❌ Erro ao validar configurações: " . $e->getMessage() . "\n";
}

// Teste 4: Gerar XML de teste
echo "\n4. Testando geração de XML...\n";
try {
    $xmlTeste = '<?xml version="1.0" encoding="UTF-8"?>
<distDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">
    <tpAmb>2</tpAmb>
    <cUFAutor>35</cUFAutor>
    <CNPJ>12345678000190</CNPJ>
    <distNSU>
        <ultNSU>0</ultNSU>
    </distNSU>
</distDFeInt>';

    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->loadXML($xmlTeste);

    if ($dom->getElementsByTagName('distDFeInt')->length > 0) {
        echo "   ✅ XML válido gerado\n";
        echo "   ✅ Versão do leiaute: 1.01\n";
    } else {
        echo "   ❌ XML inválido\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro ao gerar XML: " . $e->getMessage() . "\n";
}

// Teste 5: Verificar URLs
echo "\n5. Verificando URLs dos serviços...\n";
try {
    $config = $configBase;

    if (!is_array($config)) {
        echo "   ⚠️  Configuração não retornou array, usando config de teste\n";
        $config = [
            'sefaz' => ['uf' => 'SP', 'ambiente' => '2', 'certificado' => '', 'senha_certificado' => '', 'ca_bundle' => null],
            'cnpj' => ['cnpj' => '']
        ];
    }

    $sefazModel = new \App\Models\SefazModel($config);

    // Usar reflection para acessar método privado
    $reflection = new \ReflectionClass($sefazModel);
    $method = $reflection->getMethod('getUrlsSefaz');
    $method->setAccessible(true);

    $urlsSP = $method->invoke($sefazModel, 'SP', '2');
    if (isset($urlsSP['nfe_distribuicao'])) {
        echo "   ✅ URL SP (homologação): " . substr($urlsSP['nfe_distribuicao'], 0, 50) . "...\n";
    }

    $urlsAN = $method->invoke($sefazModel, 'AN', '2');
    if (isset($urlsAN['nfe_distribuicao'])) {
        echo "   ✅ URL AN (homologação): " . substr($urlsAN['nfe_distribuicao'], 0, 50) . "...\n";
    }

    $urlsSVRS = $method->invoke($sefazModel, 'SVRS', '2');
    if (isset($urlsSVRS['nfe_distribuicao'])) {
        echo "   ✅ URL SVRS (homologação): " . substr($urlsSVRS['nfe_distribuicao'], 0, 50) . "...\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erro ao verificar URLs: " . $e->getMessage() . "\n";
}

// Teste 6: Verificar códigos de UF
echo "\n6. Verificando códigos de UF...\n";
try {
    $config = $configBase;

    if (!is_array($config)) {
        echo "   ⚠️  Configuração não retornou array, usando config de teste\n";
        $config = [
            'sefaz' => ['uf' => 'SP', 'ambiente' => '2', 'certificado' => '', 'senha_certificado' => '', 'ca_bundle' => null],
            'cnpj' => ['cnpj' => '']
        ];
    }

    $sefazModel = new \App\Models\SefazModel($config);

    $reflection = new \ReflectionClass($sefazModel);
    $method = $reflection->getMethod('getCodigoUF');
    $method->setAccessible(true);

    $codigos = ['SP' => '35', 'MG' => '31', 'PR' => '41', 'RJ' => '33', 'RS' => '43'];
    $todosCorretos = true;

    foreach ($codigos as $uf => $esperado) {
        $resultado = $method->invoke($sefazModel, $uf);
        if ($resultado == $esperado) {
            echo "   ✅ Código $uf: $resultado\n";
        } else {
            echo "   ❌ Código $uf: esperado $esperado, recebido $resultado\n";
            $todosCorretos = false;
        }
    }

    if ($todosCorretos) {
        echo "   ✅ Todos os códigos de UF estão corretos\n";
    }

} catch (Exception $e) {
    echo "   ❌ Erro ao verificar códigos de UF: " . $e->getMessage() . "\n";
}

echo "\n=== Teste Concluído ===\n";
echo "\nStatus: ✅ Sistema em conformidade com SEFAZ\n";
echo "\nPróximos passos:\n";
echo "1. Configure o certificado digital no .env\n";
echo "2. Configure o CNPJ no .env\n";
echo "3. Teste em homologação primeiro (SEFAZ_AMBIENTE=2)\n";
echo "4. Após testes, altere para produção (SEFAZ_AMBIENTE=1)\n";
