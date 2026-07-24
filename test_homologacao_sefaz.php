<?php

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/config.php';

echo "=== Teste de Homologacao SEFAZ ===\n\n";

function ok($message)
{
    echo "   OK  $message\n";
}

function fail($message)
{
    echo "   ERRO $message\n";
}

function step($number, $title)
{
    echo "\n{$number}. {$title}\n";
}

try {
    $sefazModel = new \App\Models\SefazModel($config);
    $reflection = new \ReflectionClass($sefazModel);

    step(1, 'Validando configuracao base');
    $erros = $sefazModel->validarConfiguracoes();
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            fail($erro);
        }
    } else {
        ok('Configuracao basica valida');
    }

    step(2, 'Validando XML do pedido');
    $buildXml = $reflection->getMethod('buildXmlConsulta');
    $buildXml->setAccessible(true);

    $xmlDistNsu = $buildXml->invoke($sefazModel, '12345678000190', null, null, 0, 'distNSU');
    $domDistNsu = new DOMDocument('1.0', 'UTF-8');
    $domDistNsu->preserveWhiteSpace = false;
    $loaded = $domDistNsu->loadXML($xmlDistNsu);

    if ($loaded && $domDistNsu->getElementsByTagName('distDFeInt')->length > 0) {
        ok('distDFeInt gerado corretamente');
    } else {
        fail('Falha ao gerar distDFeInt');
    }

    if (strpos($xmlDistNsu, '<distNSU>') !== false && strpos($xmlDistNsu, '<ultNSU>000000000000000</ultNSU>') !== false) {
        ok('distNSU com NSU zero formatado com 15 digitos');
    } else {
        fail('distNSU nao contem o NSU esperado');
    }

    $xmlConsNsu = $buildXml->invoke($sefazModel, '12345678000190', null, null, 123, 'consNSU');
    if (strpos($xmlConsNsu, '<consNSU>') !== false && strpos($xmlConsNsu, '<NSU>000000000000123</NSU>') !== false) {
        ok('consNSU com NSU formatado corretamente');
    } else {
        fail('consNSU nao foi montado como esperado');
    }

    $xmlConsCh = $buildXml->invoke($sefazModel, '12345678000190', '35140100000000000000550010000000011000000010', null, 0, 'consChNFe');
    if (strpos($xmlConsCh, '<consChNFe>') !== false && strpos($xmlConsCh, '<chNFe>35140100000000000000550010000000011000000010</chNFe>') !== false) {
        ok('consChNFe com chave de acesso correta');
    } else {
        fail('consChNFe nao foi montado como esperado');
    }

    $envelopeMethod = $reflection->getMethod('montarEnvelopeSoap');
    $envelopeMethod->setAccessible(true);
    $soapEnvelope = $envelopeMethod->invoke($sefazModel, $xmlDistNsu);

    if (strpos($soapEnvelope, 'nfeCabecMsg') !== false && strpos($soapEnvelope, 'nfeDadosMsg') !== false) {
        ok('Envelope SOAP contem cabecalho e corpo esperados');
    } else {
        fail('Envelope SOAP incompleto');
    }

    step(3, 'Validando parser de resposta');
    $procNFe = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<procNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
        . '<NFe>'
        . '<infNFe Id="NFe35140100000000000000550010000000011000000010">'
        . '<ide>'
        . '<nNF>1</nNF>'
        . '<serie>1</serie>'
        . '<dhEmi>2026-07-24T10:00:00-03:00</dhEmi>'
        . '</ide>'
        . '<emit>'
        . '<CNPJ>12345678000190</CNPJ>'
        . '<xNome>EMPRESA TESTE LTDA</xNome>'
        . '</emit>'
        . '<total>'
        . '<ICMSTot>'
        . '<vNF>100.00</vNF>'
        . '</ICMSTot>'
        . '</total>'
        . '</infNFe>'
        . '</NFe>'
        . '</procNFe>';

    $payload = function_exists('gzencode') ? gzencode($procNFe) : $procNFe;
    $docZip = base64_encode($payload);
    $retornoSimulado = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<retDistDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">'
        . '<tpAmb>2</tpAmb>'
        . '<verAplic>TESTE</verAplic>'
        . '<cStat>138</cStat>'
        . '<xMotivo>Documento localizado</xMotivo>'
        . '<dhResp>2026-07-24T10:00:00-03:00</dhResp>'
        . '<ultNSU>000000000000123</ultNSU>'
        . '<maxNSU>000000000000456</maxNSU>'
        . '<loteDistDFeInt>'
        . '<docZip NSU="000000000000123" schema="procNFe_v4.00.xsd">'
        . $docZip
        . '</docZip>'
        . '</loteDistDFeInt>'
        . '</retDistDFeInt>';

    $processarResposta = $reflection->getMethod('processarResposta');
    $processarResposta->setAccessible(true);
    $resultado = $processarResposta->invoke($sefazModel, $retornoSimulado);

    if (!empty($resultado['notas']) && ($resultado['notas'][0]['chave'] ?? '') === '35140100000000000000550010000000011000000010') {
        ok('Parser extraiu a nota do docZip simulado');
    } else {
        fail('Parser nao extraiu a nota esperada');
    }

    if (($resultado['ultNSU'] ?? '') === '000000000000123' && ($resultado['maxNSU'] ?? '') === '000000000000456') {
        ok('NSU de retorno interpretado corretamente');
    } else {
        fail('NSU de retorno nao bate com o esperado');
    }

    $resNFe = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<resNFe xmlns="http://www.portalfiscal.inf.br/nfe">'
        . '<chNFe>35140100000000000000550010000000011000000010</chNFe>'
        . '<CNPJ>12345678000190</CNPJ>'
        . '<xNome>EMPRESA TESTE LTDA</xNome>'
        . '<dhEmi>2026-07-24T10:00:00-03:00</dhEmi>'
        . '<vNF>100.00</vNF>'
        . '<nProt>135000000000000</nProt>'
        . '</resNFe>';
    $resPayload = function_exists('gzencode') ? gzencode($resNFe) : $resNFe;
    $resDocZip = base64_encode($resPayload);
    $retornoResumoSimulado = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<retDistDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">'
        . '<tpAmb>2</tpAmb>'
        . '<verAplic>TESTE</verAplic>'
        . '<cStat>138</cStat>'
        . '<xMotivo>Documento localizado</xMotivo>'
        . '<dhResp>2026-07-24T10:00:00-03:00</dhResp>'
        . '<ultNSU>000000000000124</ultNSU>'
        . '<maxNSU>000000000000456</maxNSU>'
        . '<loteDistDFeInt>'
        . '<docZip NSU="000000000000124" schema="resNFe_v1.01.xsd">'
        . $resDocZip
        . '</docZip>'
        . '</loteDistDFeInt>'
        . '</retDistDFeInt>';

    $resultadoResumo = $processarResposta->invoke($sefazModel, $retornoResumoSimulado);
    if (!empty($resultadoResumo['notas']) && ($resultadoResumo['notas'][0]['tipo'] ?? '') === 'resNFe') {
        ok('Parser extraiu o resumo resNFe simulado');
    } else {
        fail('Parser nao extraiu o resumo resNFe esperado');
    }

    step(4, 'Teste real em homologacao');
    $liveTest = getenv('SEFAZ_LIVE_TEST') === '1';
    $hasCert = !empty($config['sefaz']['certificado']);
    $hasCnpj = !empty($config['cnpj']['cnpj']);

    if (!$liveTest) {
        echo "   Pulado. Defina SEFAZ_LIVE_TEST=1 para tentar a chamada real.\n";
    } elseif (!$hasCert || !$hasCnpj) {
        echo "   Pulado. Configure CNPJ e certificado no .env antes do teste real.\n";
    } else {
        try {
            $testChave = getenv('SEFAZ_TEST_CHAVE');
            if (!empty($testChave)) {
                $nota = $sefazModel->buscarNotaPorChave($testChave);
                ok('Consulta real por chave executada sem erro fatal');
                echo $nota ? "   Nota retornada com sucesso\n" : "   Nenhuma nota retornada para a chave informada\n";
            } else {
                $inicio = date('Y-m-d', strtotime('-7 days'));
                $fim = date('Y-m-d');
                $notas = $sefazModel->buscarNotasPorPeriodo($inicio, $fim);
                ok('Chamada real executada sem erro fatal');
                echo '   Notas retornadas: ' . count($notas) . "\n";
            }
        } catch (Exception $e) {
            fail('Chamada real falhou: ' . $e->getMessage());
        }
    }

    echo "\n=== Fim do teste ===\n";
} catch (Exception $e) {
    fail('Erro geral no teste: ' . $e->getMessage());
    exit(1);
}
