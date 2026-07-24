<?php

namespace App\Models;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
class SefazModel
{
    private $config;
    private $client;
    private $certificado;
    private $senhaCertificado;
    private $sefazUf;
    private $certificadoOrigem;
    private $arquivosTemporarios = [];

    public function __construct(array $config, $certificadoUsuario = null)
    {
        $this->config = $config;

        // PRIORIDADE: 1. Certificado do usuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio uploadado, 2. Certificado do .env
        if ($certificadoUsuario && !empty($certificadoUsuario['caminho_arquivo'])) {
            $this->certificado = $certificadoUsuario['caminho_arquivo'];
            $this->senhaCertificado = $certificadoUsuario['senha_certificado'];
            $this->sefazUf = $certificadoUsuario['sefaz_uf'] ?? $config['sefaz']['uf'];
            $this->certificadoOrigem = 'usuario'; // Para debug
        } elseif (!empty($config['sefaz']['certificado'])) {
            $this->certificado = $config['sefaz']['certificado'];
            $this->senhaCertificado = $config['sefaz']['senha_certificado'];
            $this->sefazUf = $config['sefaz']['uf'];
            $this->certificadoOrigem = 'env'; // Para debug
        } else {
            $this->certificado = null;
            $this->senhaCertificado = null;
            $this->sefazUf = $config['sefaz']['uf'];
            $this->certificadoOrigem = 'nenhum'; // Para debug
        }

        $this->client = new Client([
            'timeout' => 30,
            'verify' => !empty($config['sefaz']['ca_bundle']) ? $config['sefaz']['ca_bundle'] : true,
        ]);

    }

    /**
     * Busca notas fiscais por perÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­odo (usando NSU)
     * Nota: O serviÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§o SEFAZ usa NSU, nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o perÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­odo direto
     */
    public function buscarNotasPorPeriodo($dataInicio, $dataFim)
    {
        $cnpj = $this->config['cnpj']['cnpj'];
        $uf = $this->sefazUf;
        $ambiente = $this->config['sefaz']['ambiente'];

        $notas = [];
        $ultNSU = 0;
        $maxNSU = 0;
        $continuar = true;
        $maxIteracoes = 100; // Limite de seguranÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§a
        $iteracao = 0;

        // URLs dos serviÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§os SEFAZ por estado
        $urls = $this->getUrlsSefaz($uf, $ambiente);

        try {
            // ImplementaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o correta com paginaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o por NSU
            while ($continuar && $iteracao < $maxIteracoes) {
                $iteracao++;

                $response = $this->consultarNFeDistribuicao(
                    $urls['nfe_distribuicao'],
                    $cnpj,
                    null,
                    null,
                    $ultNSU,
                    'distNSU'
                );

                if ($response) {
                    $resultado = $this->processarResposta($response);
                    $ultNSUAnterior = $ultNSU;

                    if (!empty($resultado['notas'])) {
                        $notas = array_merge($notas, $resultado['notas']);
                    }

                    // Atualizar NSU para prÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³xima pÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gina
                    if (isset($resultado['ultNSU'])) {
                        $ultNSU = $resultado['ultNSU'];
                    }

                    if (isset($resultado['maxNSU'])) {
                        $maxNSU = $resultado['maxNSU'];
                    }

                    // Continuar enquanto houver NSU para processar e houver progresso
                    $continuar = ($ultNSU < $maxNSU) && ($ultNSU !== $ultNSUAnterior);
                } else {
                    $continuar = false;
                }
            }

            // Filtrar por perÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­odo se necessÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio
            if ($dataInicio && $dataFim) {
                $notas = $this->filtrarPorPeriodo($notas, $dataInicio, $dataFim);
            }

        } catch (RequestException $e) {
            throw new \Exception('Erro ao consultar SEFAZ: ' . $e->getMessage());
        }

        return $notas;
    }

    /**
     * Busca nota fiscal por chave de acesso
     */
    public function buscarNotaPorChave($chave)
    {
        $cnpj = $this->config['cnpj']['cnpj'];
        $uf = $this->sefazUf;
        $ambiente = $this->config['sefaz']['ambiente'];

        $urls = $this->getUrlsSefaz($uf, $ambiente);

        try {
            $response = $this->consultarNFeDistribuicao(
                $urls['nfe_distribuicao'],
                $cnpj,
                $chave, // usar chave como parÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢metro
                null,
                null,
                'consChNFe'
            );

            if ($response) {
                $resultado = $this->processarResposta($response);
                return $resultado['notas'][0] ?? null;
            }

        } catch (RequestException $e) {
            throw new \Exception('Erro ao consultar SEFAZ: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Busca nota fiscal por NSU especÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­fico
     */
    public function buscarNotaPorNSU($nsu)
    {
        $cnpj = $this->config['cnpj']['cnpj'];
        $uf = $this->sefazUf;
        $ambiente = $this->config['sefaz']['ambiente'];

        $urls = $this->getUrlsSefaz($uf, $ambiente);

        try {
            $response = $this->consultarNFeDistribuicao(
                $urls['nfe_distribuicao'],
                $cnpj,
                null,
                null,
                $nsu,
                'consNSU'
            );

            if ($response) {
                $resultado = $this->processarResposta($response);
                return $resultado['notas'][0] ?? null;
            }

        } catch (RequestException $e) {
            throw new \Exception('Erro ao consultar SEFAZ: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Consulta servico de distribuicao de NFe
     */
    private function consultarNFeDistribuicao($url, $cnpj, $param1 = null, $param2 = null, $ultNSU = 0, $tipoConsulta = 'distNSU')
    {
        $xmlConsulta = $this->buildXmlConsulta($cnpj, $param1, $param2, $ultNSU, $tipoConsulta);
        $soapEnvelope = $this->montarEnvelopeSoap($xmlConsulta);

        try {
            $options = [
                'headers' => [
                    'Content-Type' => 'application/soap+xml; charset=utf-8',
                    'Accept' => 'application/soap+xml, text/xml',
                    'SOAPAction' => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe/nfeDistDFeInteresse',
                ],
                'body' => $soapEnvelope,
            ];

            $opcoesCertificado = $this->obterOpcoesCertificado();
            if (!empty($opcoesCertificado)) {
                $options = array_merge($options, $opcoesCertificado);
            }

            $response = $this->client->post($url, $options);

            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $e->getResponse()->getBody()->getContents();
            }

            throw $e;
        } finally {
            $this->limparArquivosTemporarios();
        }
    }

    /**
     * Construi XML de consulta para servico de distribuicao
     */
    private function buildXmlConsulta($cnpj, $dataInicio = null, $dataFim = null, $ultNSU = 0, $tipoConsulta = 'distNSU')
    {
        $ns = 'http://www.portalfiscal.inf.br/nfe';
        $documento = $this->limparDocumento($cnpj);
        $tipoDoc = strlen($documento) == 11 ? 'CPF' : 'CNPJ';
        $codigoUf = $this->getCodigoUF($this->sefazUf);
        $ambiente = (string) $this->config['sefaz']['ambiente'];
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<distDFeInt xmlns=\"{$ns}\" versao=\"1.01\">\n    <tpAmb>{$ambiente}</tpAmb>\n    <cUFAutor>{$codigoUf}</cUFAutor>\n    <{$tipoDoc}>{$documento}</{$tipoDoc}>";

        switch ($tipoConsulta) {
            case 'distNSU':
                $xml .= "\n    <distNSU>\n        <ultNSU>{$this->normalizarNsu($ultNSU)}</ultNSU>\n    </distNSU>";
                break;

            case 'consNSU':
                $xml .= "\n    <consNSU>\n        <NSU>{$this->normalizarNsu($ultNSU)}</NSU>\n    </consNSU>";
                break;

            case 'consChNFe':
                $chave = $this->limparDocumento($param1 ?? $dataInicio);
                $xml .= "\n    <consChNFe>\n        <chNFe>{$chave}</chNFe>\n    </consChNFe>";
                break;
        }

        $xml .= "\n</distDFeInt>";

        return $xml;
    }

    /**
     * Monta envelope SOAP 1.2 para o pedido
     */
    private function montarEnvelopeSoap($xmlConsulta)
    {
        $codigoUf = $this->getCodigoUF($this->sefazUf);

        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'
            . '<soap12:Header>'
            . '<nfeCabecMsg xmlns="http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe">'
            . '<cUF>' . $codigoUf . '</cUF>'
            . '<versaoDados>1.01</versaoDados>'
            . '</nfeCabecMsg>'
            . '</soap12:Header>'
            . '<soap12:Body>'
            . '<nfeDistDFeInteresse xmlns="http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe">'
            . '<nfeDadosMsg xmlns="http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe">'
            . $xmlConsulta
            . '</nfeDadosMsg>'
            . '</nfeDistDFeInteresse>'
            . '</soap12:Body>'
            . '</soap12:Envelope>';
    }

    /**
     * Prepara certificado PFX/P12 para uso no TLS do cliente HTTP
     */
    private function obterOpcoesCertificado()
    {
        if (empty($this->certificado) || empty($this->senhaCertificado)) {
            return [];
        }

        if (!file_exists($this->certificado)) {
            throw new \Exception('Arquivo de certificado nÃƒÂ£o encontrado: ' . $this->certificado);
        }

        $conteudo = file_get_contents($this->certificado);
        if ($conteudo === false) {
            throw new \Exception('NÃƒÂ£o foi possÃƒÂ­vel ler o arquivo de certificado.');
        }

        if (!openssl_pkcs12_read($conteudo, $certs, $this->senhaCertificado)) {
            throw new \Exception('NÃƒÂ£o foi possÃƒÂ­vel abrir o certificado. Verifique a senha.');
        }

        $diretorioTemp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'getxml_sefaz';
        if (!is_dir($diretorioTemp) && !mkdir($diretorioTemp, 0700, true) && !is_dir($diretorioTemp)) {
            throw new \Exception('NÃƒÂ£o foi possÃƒÂ­vel criar diretÃƒÂ³rio temporÃƒÂ¡rio para o certificado.');
        }

        $identificador = uniqid('tls_', true);
        $caminhoCert = $diretorioTemp . DIRECTORY_SEPARATOR . $identificador . '.crt.pem';
        $caminhoKey = $diretorioTemp . DIRECTORY_SEPARATOR . $identificador . '.key.pem';

        if (file_put_contents($caminhoCert, $certs['cert']) === false || file_put_contents($caminhoKey, $certs['pkey']) === false) {
            throw new \Exception('NÃƒÂ£o foi possÃƒÂ­vel preparar os arquivos PEM do certificado.');
        }

        $this->arquivosTemporarios[] = $caminhoCert;
        $this->arquivosTemporarios[] = $caminhoKey;

        return [
            'cert' => $caminhoCert,
            'ssl_key' => $caminhoKey,
        ];
    }

    /**
     * Remove arquivos temporarios do certificado
     */
    private function limparArquivosTemporarios()
    {
        foreach ($this->arquivosTemporarios as $arquivo) {
            if (is_file($arquivo)) {
                @unlink($arquivo);
            }
        }

        $this->arquivosTemporarios = [];
    }

    /**
     * Descompacta o conteÃƒÂºdo base64 do docZip
     */
    private function descompactarDocZip($conteudo)
    {
        $binario = base64_decode(trim((string) $conteudo), true);
        if ($binario === false) {
            return null;
        }

        $primeiroCaracter = ltrim($binario);
        if ($primeiroCaracter !== '' && $primeiroCaracter[0] === '<') {
            return null;
        }

        $candidatos = [];
        if (function_exists('gzdecode')) {
            $candidatos[] = @gzdecode($binario);
        }
        if (function_exists('gzuncompress')) {
            $candidatos[] = @gzuncompress($binario);
        }
        if (function_exists('gzinflate')) {
            $candidatos[] = @gzinflate($binario);
        }

        foreach ($candidatos as $xml) {
            if ($xml !== false && $xml !== null) {
                return $xml;
            }
        }

        return null;
    }

    /**
     * Normaliza NSU para 15 digitos
     */
    private function normalizarNsu($nsu)
    {
        return str_pad((string) $nsu, 15, '0', STR_PAD_LEFT);
    }

    /**
     * Processa resposta da SEFAZ e extrai XMLs das notas
     */
    private function processarResposta($xmlResposta)
    {
        $resultado = [
            'notas' => [],
            'ultNSU' => '000000000000000',
            'maxNSU' => '000000000000000',
            'resumos' => [],
            'eventos' => []
        ];

        try {
            $xml = simplexml_load_string($xmlResposta);
            if (!$xml) {
                throw new \Exception('Resposta XML invÃƒÂ¡lida.');
            }

            $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

            $ultNSU = $xml->xpath('//nfe:ultNSU');
            if (!empty($ultNSU)) {
                $resultado['ultNSU'] = $this->normalizarNsu((string) $ultNSU[0]);
            }

            $maxNSU = $xml->xpath('//nfe:maxNSU');
            if (!empty($maxNSU)) {
                $resultado['maxNSU'] = $this->normalizarNsu((string) $maxNSU[0]);
            }

            $docs = $xml->xpath('//nfe:docZip');
            foreach ($docs as $doc) {
                $conteudo = $this->descompactarDocZip((string) $doc);
                if ($conteudo === null) {
                    continue;
                }

                $notaXml = simplexml_load_string($conteudo);
                if ($notaXml) {
                    $schema = (string) $doc['schema'];
                    $nota = $this->extrairDadosNota($notaXml, $schema, $conteudo);
                    if ($nota) {
                        $resultado['notas'][] = $nota;
                    }
                }
            }

            $resumos = $xml->xpath('//nfe:resNFe');
            foreach ($resumos as $resumo) {
                $resultado['resumos'][] = $this->extrairDadosResumo($resumo);
            }

            $eventos = $xml->xpath('//nfe:resEvento');
            foreach ($eventos as $evento) {
                $resultado['eventos'][] = $this->extrairDadosEvento($evento);
            }
        } catch (\Exception $e) {
            throw new \Exception('Erro ao processar resposta XML: ' . $e->getMessage());
        }

        return $resultado;
    }

    /**
     * Extrai dados de uma nota fiscal
     */
    private function extrairDadosNota($notaXml, $schema, $conteudo)
    {
        try {
            $raiz = $notaXml->getName();

            $resumoNodes = $notaXml->xpath('//*[local-name()="resNFe"]');
            if (!empty($resumoNodes)) {
                return $this->extrairDadosResumo($resumoNodes[0]);
            }

            $eventoResumoNodes = $notaXml->xpath('//*[local-name()="resEvento"]');
            if (!empty($eventoResumoNodes)) {
                return $this->extrairDadosEvento($eventoResumoNodes[0]);
            }

            $infNFeNodes = $notaXml->xpath('//*[local-name()="infNFe"]');
            if (!empty($infNFeNodes)) {
                $infNFe = $infNFeNodes[0];
                $chave = str_replace('NFe', '', (string) $infNFe['Id']);

                return [
                    'chave' => $chave,
                    'xml' => $conteudo,
                    'schema' => $schema,
                    'numero' => (string) $infNFe->ide->nNF,
                    'serie' => (string) $infNFe->ide->serie,
                    'data_emissao' => (string) $infNFe->ide->dhEmi,
                    'valor' => (string) $infNFe->total->ICMSTot->vNF,
                    'cnpj_emitente' => (string) $infNFe->emit->CNPJ,
                    'nome_emitente' => (string) $infNFe->emit->xNome,
                    'tipo' => 'NFe'
                ];
            }

            if ($raiz === 'procEventoNFe') {
                $infEventoNodes = $notaXml->xpath('//*[local-name()="infEvento"]');
                if (!empty($infEventoNodes)) {
                    $infEvento = $infEventoNodes[0];
                    return [
                        'tipo' => 'Evento',
                        'chave' => (string) $infEvento->chNFe,
                        'tpEvento' => (string) $infEvento->tpEvento,
                        'data_emissao' => (string) $infEvento->dhEvento,
                        'xml' => $conteudo,
                        'schema' => $schema
                    ];
                }
            }

            if ($raiz === 'NFe') {
                $infNFeNodes = $notaXml->xpath('//*[local-name()="infNFe"]');
                if (!empty($infNFeNodes)) {
                    $infNFe = $infNFeNodes[0];
                    $chave = str_replace('NFe', '', (string) $infNFe['Id']);

                    return [
                        'chave' => $chave,
                        'xml' => $conteudo,
                        'schema' => $schema,
                        'numero' => (string) $infNFe->ide->nNF,
                        'serie' => (string) $infNFe->ide->serie,
                        'data_emissao' => (string) $infNFe->ide->dhEmi,
                        'valor' => (string) $infNFe->total->ICMSTot->vNF,
                        'cnpj_emitente' => (string) $infNFe->emit->CNPJ,
                        'nome_emitente' => (string) $infNFe->emit->xNome,
                        'tipo' => 'NFe'
                    ];
                }
            }

            if ($raiz === 'procEventoNFe' || $raiz === 'evento' || $raiz === 'procEvento') {
                $infEventoNodes = $notaXml->xpath('//*[local-name()="infEvento"]');
                if (!empty($infEventoNodes)) {
                    $infEvento = $infEventoNodes[0];
                    return [
                        'tipo' => 'Evento',
                        'chave' => (string) $infEvento->chNFe,
                        'tpEvento' => (string) $infEvento->tpEvento,
                        'data_emissao' => (string) $infEvento->dhEvento,
                        'xml' => $conteudo,
                        'schema' => $schema
                    ];
                }
            }

            if ($raiz === 'procNFe') {
                $infNFeNodes = $notaXml->xpath('//*[local-name()="infNFe"]');
                if (!empty($infNFeNodes)) {
                    $infNFe = $infNFeNodes[0];
                    $chave = str_replace('NFe', '', (string) $infNFe['Id']);

                    return [
                        'chave' => $chave,
                        'xml' => $conteudo,
                        'schema' => $schema,
                        'numero' => (string) $infNFe->ide->nNF,
                        'serie' => (string) $infNFe->ide->serie,
                        'data_emissao' => (string) $infNFe->ide->dhEmi,
                        'valor' => (string) $infNFe->total->ICMSTot->vNF,
                        'cnpj_emitente' => (string) $infNFe->emit->CNPJ,
                        'nome_emitente' => (string) $infNFe->emit->xNome,
                        'tipo' => 'NFe'
                    ];
                }
            }

            if ($raiz === 'NFe' || $raiz === 'procNFe') {
                $resumoNodes = $notaXml->xpath('//*[local-name()="resNFe"]');
                if (!empty($resumoNodes)) {
                    return $this->extrairDadosResumo($resumoNodes[0]);
                }
            }

        } catch (\Exception $e) {
            error_log('Erro ao extrair dados da nota: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Extrai dados de resumo de NF-e
     */
    private function extrairDadosResumo($resumo)
    {
        return [
            'chave' => (string) $resumo->chNFe,
            'nProt' => (string) $resumo->nProt,
            'xNome' => (string) $resumo->xNome,
            'CNPJ' => (string) $resumo->CNPJ,
            'dhEmi' => (string) $resumo->dhEmi,
            'data_emissao' => (string) $resumo->dhEmi,
            'vNF' => (string) $resumo->vNF,
            'tipo' => 'resNFe'
        ];
    }

    /**
     * Extrai dados de evento
     */
    private function extrairDadosEvento($evento)
    {
        return [
            'chave' => (string) $evento->chNFe,
            'tpEvento' => (string) $evento->tpEvento,
            'nSeqEvento' => (string) $evento->nSeqEvento,
            'dhEvento' => (string) $evento->dhEvento,
            'data_emissao' => (string) $evento->dhEvento,
            'tipo' => 'resEvento'
        ];
    }

    /**
     * Salva XML de nota fiscal em arquivo
     */
    public function salvarXml($nota, $caminho)
    {
        $nomeArquivo = sprintf('%s-%s.xml', 
            $nota['chave'], 
            date('YmdHis')
        );
        
        $caminhoCompleto = $caminho . '/' . $nomeArquivo;
        
        if (file_put_contents($caminhoCompleto, $nota['xml'])) {
            return $caminhoCompleto;
        }
        
        return false;
    }

    /**
     * ObtÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©m URLs dos serviÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§os SEFAZ por estado
     */
    private function getUrlsSefaz($uf, $ambiente)
    {
        $producao = $ambiente == '1';

        $urls = [
            // Ambiente Nacional (AN)
            'AN' => [
                'nfe_distribuicao' => $producao
                    ? 'https://www1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://hom.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],

            // Estados
            'SP' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.fazenda.sp.gov.br/nfeWS/services/NFeDistribuicaoDFe'
                    : 'https://homologacao.nfe.fazenda.sp.gov.br/nfeWS/services/NFeDistribuicaoDFe',
            ],
            'MG' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.fazenda.mg.gov.br/nfeWS/services/NFeDistribuicaoDFe'
                    : 'https://hnfe.fazenda.mg.gov.br/nfeWS/services/NFeDistribuicaoDFe',
            ],
            'PR' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.fazenda.pr.gov.br/nfe/NFeDistribuicaoDFe'
                    : 'https://homologacao.nfe.fazenda.pr.gov.br/nfe/NFeDistribuicaoDFe',
            ],
            'RJ' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.fazenda.rj.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homologacao.nfe.fazenda.rj.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'RS' => [
                'nfe_distribuicao' => $producao
                    ? 'https://sef.rs.gov.br/NFe/NFeDistribuicaoDFe'
                    : 'https://sef.rs.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'SC' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.svrs.rs.gov.br/ws/NfeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'BA' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.ba.gov.br/webservices/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://hnfe.sefaz.ba.gov.br/webservices/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'PE' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.pe.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://nfehomolog.sefaz.pe.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'GO' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.go.gov.br/nfe/services/NFeDistribuicaoDFe'
                    : 'https://homolog.sefaz.go.gov.br/nfe/services/NFeDistribuicaoDFe',
            ],
            'AM' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.am.gov.br/services/NFeDistribuicaoDFe'
                    : 'https://homnfe.sefaz.am.gov.br/services/NFeDistribuicaoDFe',
            ],
            'CE' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.ce.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://nfeh.sefaz.ce.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'DF' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.df.gov.br/WS/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.df.gov.br/WS/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'ES' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.es.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homologacao.nfe.sefaz.es.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'MA' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.ma.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.ma.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'MS' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.ms.gov.br/ws/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homologacao.nfe.sefaz.ms.gov.br/ws/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'MT' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.mt.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homologacao.sefaz.mt.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'PA' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.pa.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.pa.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'PB' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.pb.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homologacao.nfe.sefaz.pb.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'PI' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.pi.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.pi.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'RN' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.rn.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.rn.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'RO' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.ro.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.ro.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'RR' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.rr.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.rr.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'SE' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.se.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.se.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'TO' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.to.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.to.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'AP' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.ap.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.ap.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'AC' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.ac.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.ac.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
            'AL' => [
                'nfe_distribuicao' => $producao
                    ? 'https://nfe.sefaz.al.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    : 'https://homolog.nfe.sefaz.al.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            ],
        ];

        // SVRS (Secretaria Virtual do RS) - usada por vÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios estados
        $urls['SVRS'] = [
            'nfe_distribuicao' => $producao
                ? 'https://nfe.svrs.rs.gov.br/ws/NfeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                : 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
        ];

        // SVCAN (Secretaria Virtual do Ambiente Nacional)
        $urls['SVCAN'] = [
            'nfe_distribuicao' => $producao
                ? 'https://nfe.svcan.rs.gov.br/ws/NfeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                : 'https://nfe-homologacao.svcan.rs.gov.br/ws/NfeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
        ];

        return $urls[$uf] ?? $urls['AN'];
    }

    /**
     * Formata data para padrÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o SEFAZ
     */
    private function formatarData($data)
    {
        if (strlen($data) == 10) {
            return $data . 'T00:00:00-03:00';
        }
        return $data;
    }

    /**
     * Gera chave para consulta (DEPRECIADO - usar NSU)
     */
    private function gerarChaveConsulta($cnpj)
    {
        // ImplementaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o simplificada - na prÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡tica precisa seguir padrÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o SEFAZ
        return md5($cnpj . date('YmdHis'));
    }

    /**
     * Valida configuraÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes
     */
    public function validarConfiguracoes()
    {
        $erros = [];

        // Verificar CNPJ (obrigatÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³rio)
        if (empty($this->config['cnpj']['cnpj'])) {
            $erros[] = 'CNPJ nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o configurado';
        }

        // Se nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o tiver certificado do usuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio, verificar configuraÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o global
        if (empty($this->certificado)) {
            // Se nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o tiver certificado do usuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio nem do .env, ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â© erro
            if (empty($this->config['sefaz']['certificado'])) {
                $erros[] = 'Nenhum certificado digital configurado. FaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§a upload do certificado em "Certificados" no menu ou configure no .env';
            } else {
                // Se tiver certificado no .env, verificar senha
                if (empty($this->config['sefaz']['senha_certificado'])) {
                    $erros[] = 'Senha do certificado nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o configurada no .env';
                }
            }
        }

        return $erros;
    }

    /**
     * Limpa documento (CNPJ/CPF) removendo caracteres especiais
     */
    private function limparDocumento($documento)
    {
        return preg_replace('/[^0-9]/', '', $documento);
    }

    /**
     * Filtra notas por perÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­odo
     */
    private function filtrarPorPeriodo($notas, $dataInicio, $dataFim)
    {
        $inicio = strtotime($dataInicio);
        $fim = strtotime($dataFim);

        return array_filter($notas, function($nota) use ($inicio, $fim) {
            if (isset($nota['data_emissao'])) {
                $dataNota = strtotime($nota['data_emissao']);
                return $dataNota >= $inicio && $dataNota <= $fim;
            }
            return false;
        });
    }

    /**
     * ObtÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©m cÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³digo numÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©rico da UF (atualizado com todos os estados)
     */
    private function getCodigoUF($uf)
    {
        $codigos = [
            'AC' => '12', 'AL' => '27', 'AP' => '16', 'AM' => '13',
            'BA' => '29', 'CE' => '23', 'DF' => '53', 'ES' => '32',
            'GO' => '52', 'MA' => '21', 'MT' => '51', 'MS' => '50',
            'MG' => '31', 'PA' => '15', 'PB' => '25', 'PR' => '41',
            'PE' => '26', 'PI' => '22', 'RJ' => '33', 'RN' => '24',
            'RS' => '43', 'RO' => '11', 'RR' => '14', 'SC' => '42',
            'SP' => '35', 'SE' => '28', 'TO' => '17',
            // CÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³digos especiais
            'AN' => '91', 'SVRS' => '92', 'SVCAN' => '93'
        ];

        return $codigos[$uf] ?? '35'; // SP como padrÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o
    }
}
