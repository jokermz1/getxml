<?php

namespace App\Models;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Helpers\XmlSigner;

class SefazModel
{
    private $config;
    private $client;
    private $certificado;
    private $senhaCertificado;
    private $xmlSigner;

    public function __construct(array $config, $certificadoUsuario = null)
    {
        $this->config = $config;

        // PRIORIDADE: 1. Certificado do usuário uploadado, 2. Certificado do .env
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
            'verify' => false,
        ]);

        // Inicializar assinador XML se tiver certificado
        if ($this->certificado && $this->senhaCertificado) {
            try {
                $this->xmlSigner = new XmlSigner($this->certificado, $this->senhaCertificado);
            } catch (\Exception $e) {
                // Se falhar, continuar sem assinador (modo compatibilidade)
                error_log('Aviso: Não foi possível inicializar assinador XML: ' . $e->getMessage());
                $this->xmlSigner = null;
            }
        }
    }

    /**
     * Busca notas fiscais por período (usando NSU)
     * Nota: O serviço SEFAZ usa NSU, não período direto
     */
    public function buscarNotasPorPeriodo($dataInicio, $dataFim)
    {
        $cnpj = $this->config['cnpj']['cnpj'];
        $uf = $this->config['sefaz']['uf'];
        $ambiente = $this->config['sefaz']['ambiente'];

        $notas = [];
        $ultNSU = 0;
        $maxNSU = 0;
        $continuar = true;
        $maxIteracoes = 100; // Limite de segurança
        $iteracao = 0;

        // URLs dos serviços SEFAZ por estado
        $urls = $this->getUrlsSefaz($uf, $ambiente);

        try {
            // Implementação correta com paginação por NSU
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

                    if (!empty($resultado['notas'])) {
                        $notas = array_merge($notas, $resultado['notas']);
                    }

                    // Atualizar NSU para próxima página
                    if (isset($resultado['ultNSU'])) {
                        $ultNSU = $resultado['ultNSU'];
                    }

                    if (isset($resultado['maxNSU'])) {
                        $maxNSU = $resultado['maxNSU'];
                    }

                    // Continuar se houver mais documentos
                    $continuar = ($ultNSU < $maxNSU) && !empty($resultado['notas']);
                } else {
                    $continuar = false;
                }
            }

            // Filtrar por período se necessário
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
        $uf = $this->config['sefaz']['uf'];
        $ambiente = $this->config['sefaz']['ambiente'];

        $urls = $this->getUrlsSefaz($uf, $ambiente);

        try {
            $response = $this->consultarNFeDistribuicao(
                $urls['nfe_distribuicao'],
                $cnpj,
                $chave, // usar chave como parâmetro
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
     * Busca nota fiscal por NSU específico
     */
    public function buscarNotaPorNSU($nsu)
    {
        $cnpj = $this->config['cnpj']['cnpj'];
        $uf = $this->config['sefaz']['uf'];
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
     * Consulta serviço de distribuição de NFe
     */
    private function consultarNFeDistribuicao($url, $cnpj, $param1 = null, $param2 = null, $ultNSU = 0, $tipoConsulta = 'distNSU')
    {
        // Construir o XML de consulta
        $xmlConsulta = $this->buildXmlConsulta($cnpj, $param1, $param2, $ultNSU, $tipoConsulta);

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/xml',
                    'SOAPAction' => 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe/nfeDistDFeInteresse',
                ],
                'body' => $xmlConsulta,
                'cert' => $this->certificado,
                'ssl_key' => [$this->certificado, $this->senhaCertificado],
            ]);

            return $response->getBody()->getContents();

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $e->getResponse()->getBody()->getContents();
            }
            throw $e;
        }
    }

    /**
     * Constrói XML de consulta para serviço de distribuição
     */
    private function buildXmlConsulta($cnpj, $dataInicio = null, $dataFim = null, $ultNSU = 0, $tipoConsulta = 'distNSU')
    {
        $ns = 'http://www.portalfiscal.inf.br/nfe';

        // Determinar se é CNPJ ou CPF
        $documento = $this->limparDocumento($cnpj);
        $tipoDoc = strlen($documento) == 11 ? 'CPF' : 'CNPJ';

        // XML base conforme leiaute SEFAZ
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<distDFeInt xmlns=\"{$ns}\" versao=\"1.01\">
    <tpAmb>{$this->config['sefaz']['ambiente']}</tpAmb>
    <cUFAutor>{$this->getCodigoUF($this->config['sefaz']['uf'])}</cUFAutor>
    <{$tipoDoc}>{$documento}</{$tipoDoc}>";

        // Adicionar tipo de consulta
        switch ($tipoConsulta) {
            case 'distNSU':
                $xml .= "
    <distNSU>
        <ultNSU>{$ultNSU}</ultNSU>
    </distNSU>";
                break;

            case 'consNSU':
                $xml .= "
    <consNSU>
        <NSU>{$ultNSU}</NSU>
    </consNSU>";
                break;

            case 'consChNFe':
                // Para consulta por chave, usar o parâmetro como chave
                $chave = $this->limparDocumento($dataInicio); // reutilizar parâmetro
                $xml .= "
    <consChNFe>
        <chNFe>{$chave}</chNFe>
    </consChNFe>";
                break;
        }

        $xml .= "
</distDFeInt>";

        // Assinar XML se tiver assinador disponível
        if ($this->xmlSigner) {
            try {
                $xml = $this->xmlSigner->assinarXml($xml);
            } catch (\Exception $e) {
                // Se falhar assinatura, continuar sem assinatura (modo compatibilidade)
                error_log('Aviso: Não foi possível assinar XML: ' . $e->getMessage());
            }
        }

        return $xml;
    }

    /**
     * Processa resposta da SEFAZ e extrai XMLs das notas
     */
    private function processarResposta($xmlResposta)
    {
        $resultado = [
            'notas' => [],
            'ultNSU' => 0,
            'maxNSU' => 0,
            'resumos' => [],
            'eventos' => []
        ];

        try {
            $xml = simplexml_load_string($xmlResposta);
            $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

            // Extrair informações de controle NSU
            $ultNSU = $xml->xpath('//nfe:ultNSU');
            if (!empty($ultNSU)) {
                $resultado['ultNSU'] = (int)$ultNSU[0];
            }

            $maxNSU = $xml->xpath('//nfe:maxNSU');
            if (!empty($maxNSU)) {
                $resultado['maxNSU'] = (int)$maxNSU[0];
            }

            // Extrair documentos da resposta (docZip)
            $docs = $xml->xpath('//nfe:docZip');
            foreach ($docs as $doc) {
                $conteudo = base64_decode((string)$doc);
                $notaXml = simplexml_load_string($conteudo);

                if ($notaXml) {
                    $schema = (string)$doc['schema'];
                    $nota = $this->extrairDadosNota($notaXml, $schema, $conteudo);
                    if ($nota) {
                        $resultado['notas'][] = $nota;
                    }
                }
            }

            // Extrair resumos de NF-e (resNFe)
            $resumos = $xml->xpath('//nfe:resNFe');
            foreach ($resumos as $resumo) {
                $resultado['resumos'][] = $this->extrairDadosResumo($resumo);
            }

            // Extrair eventos (resEvento)
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
            // Verificar se é NF-e
            if (isset($notaXml->infNFe)) {
                $chave = (string)$notaXml->infNFe['Id'];
                $chave = str_replace('NFe', '', $chave);

                return [
                    'chave' => $chave,
                    'xml' => $conteudo,
                    'schema' => $schema,
                    'numero' => (string)$notaXml->infNFe->ide->nNF,
                    'serie' => (string)$notaXml->infNFe->ide->serie,
                    'data_emissao' => (string)$notaXml->infNFe->ide->dhEmi,
                    'valor' => (string)$notaXml->infNFe->total->ICMSTot->vNF,
                    'cnpj_emitente' => (string)$notaXml->infNFe->emit->CNPJ,
                    'nome_emitente' => (string)$notaXml->infNFe->emit->xNome,
                    'tipo' => 'NFe'
                ];
            }

            // Verificar se é cancelamento
            if (isset($notaXml->infCanc)) {
                return [
                    'tipo' => 'CancNFe',
                    'chave' => (string)$notaXml->infCanc->chNFe,
                    'xml' => $conteudo,
                    'schema' => $schema
                ];
            }

            // Verificar se é evento
            if (isset($notaXml->infEvento)) {
                return [
                    'tipo' => 'Evento',
                    'chave' => (string)$notaXml->infEvento->chNFe,
                    'tpEvento' => (string)$notaXml->infEvento->tpEvento,
                    'xml' => $conteudo,
                    'schema' => $schema
                ];
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
            'chave' => (string)$resumo->chNFe,
            'nProt' => (string)$resumo->nProt,
            'xNome' => (string)$resumo->xNome,
            'CNPJ' => (string)$resumo->CNPJ,
            'dhEmi' => (string)$resumo->dhEmi,
            'vNF' => (string)$resumo->vNF,
            'tipo' => 'resNFe'
        ];
    }

    /**
     * Extrai dados de evento
     */
    private function extrairDadosEvento($evento)
    {
        return [
            'chave' => (string)$evento->chNFe,
            'tpEvento' => (string)$evento->tpEvento,
            'nSeqEvento' => (string)$evento->nSeqEvento,
            'dhEvento' => (string)$evento->dhEvento,
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
     * Obtém URLs dos serviços SEFAZ por estado
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

        // SVRS (Secretaria Virtual do RS) - usada por vários estados
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
     * Formata data para padrão SEFAZ
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
        // Implementação simplificada - na prática precisa seguir padrão SEFAZ
        return md5($cnpj . date('YmdHis'));
    }

    /**
     * Valida configurações
     */
    public function validarConfiguracoes()
    {
        $erros = [];

        // Verificar CNPJ (obrigatório)
        if (empty($this->config['cnpj']['cnpj'])) {
            $erros[] = 'CNPJ não configurado';
        }

        // Se não tiver certificado do usuário, verificar configuração global
        if (empty($this->certificado)) {
            // Se não tiver certificado do usuário nem do .env, é erro
            if (empty($this->config['sefaz']['certificado'])) {
                $erros[] = 'Nenhum certificado digital configurado. Faça upload do certificado em "Certificados" no menu ou configure no .env';
            } else {
                // Se tiver certificado no .env, verificar senha
                if (empty($this->config['sefaz']['senha_certificado'])) {
                    $erros[] = 'Senha do certificado não configurada no .env';
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
     * Filtra notas por período
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
     * Obtém código numérico da UF (atualizado com todos os estados)
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
            // Códigos especiais
            'AN' => '91', 'SVRS' => '92', 'SVCAN' => '93'
        ];

        return $codigos[$uf] ?? '35'; // SP como padrão
    }
}
