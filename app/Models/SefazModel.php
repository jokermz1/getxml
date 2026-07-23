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

    public function __construct(array $config, $certificadoUsuario = null)
    {
        $this->config = $config;
        
        // Usa certificado do usuário se fornecido, senão usa da configuração
        if ($certificadoUsuario) {
            $this->certificado = $certificadoUsuario['caminho_arquivo'];
            $this->senhaCertificado = $certificadoUsuario['senha_certificado'];
            $this->sefazUf = $certificadoUsuario['sefaz_uf'] ?? $config['sefaz']['uf'];
        } else {
            $this->certificado = $config['sefaz']['certificado'];
            $this->senhaCertificado = $config['sefaz']['senha_certificado'];
            $this->sefazUf = $config['sefaz']['uf'];
        }
        
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
        ]);
    }

    /**
     * Busca notas fiscais por período
     */
    public function buscarNotasPorPeriodo($dataInicio, $dataFim)
    {
        $cnpj = $this->config['cnpj']['cnpj'];
        $uf = $this->config['sefaz']['uf'];
        $ambiente = $this->config['sefaz']['ambiente'];

        $notas = [];
        
        // URLs dos serviços SEFAZ por estado
        $urls = $this->getUrlsSefaz($uf, $ambiente);
        
        try {
            // Implementação básica - cada estado tem seu próprio serviço
            // Este é um exemplo genérico que precisa ser adaptado
            $response = $this->consultarNFeDistribuicao($urls['nfe_distribuicao'], $cnpj, $dataInicio, $dataFim);
            
            if ($response) {
                $notas = $this->processarResposta($response);
            }
            
        } catch (RequestException $e) {
            throw new \Exception('Erro ao consultar SEFAZ: ' . $e->getMessage());
        }

        return $notas;
    }

    /**
     * Consulta serviço de distribuição de NFe
     */
    private function consultarNFeDistribuicao($url, $cnpj, $dataInicio, $dataFim)
    {
        // Construir o XML de consulta
        $xmlConsulta = $this->buildXmlConsulta($cnpj, $dataInicio, $dataFim);
        
        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/xml',
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
    private function buildXmlConsulta($cnpj, $dataInicio, $dataFim)
    {
        $ns = 'http://www.portalfiscal.inf.br/nfe';
        
        $dataInicioFormatada = $this->formatarData($dataInicio);
        $dataFimFormatada = $this->formatarData($dataFim);
        
        $chave = $this->gerarChaveConsulta($cnpj);
        
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<distDFeInt xmlns="{$ns}" versao="1.00">
    <tpAmb>{$this->config['sefaz']['ambiente']}</tpAmb>
    <cUFAutor>{$this->getCodigoUF($this->config['sefaz']['uf'])}</cUFAutor>
    <CNPJ>{$cnpj}</CNPJ>
    <distNSU>
        <ultNSU>0</ultNSU>
    </distNSU>
</distDFeInt>
XML;

        return $xml;
    }

    /**
     * Processa resposta da SEFAZ e extrai XMLs das notas
     */
    private function processarResposta($xmlResposta)
    {
        $notas = [];
        
        try {
            $xml = simplexml_load_string($xmlResposta);
            $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
            
            // Extrair documentos da resposta
            $docs = $xml->xpath('//nfe:docZip');
            
            foreach ($docs as $doc) {
                $conteudo = base64_decode((string)$doc);
                $notaXml = simplexml_load_string($conteudo);
                
                if ($notaXml) {
                    $chave = (string)$notaXml->infNFe['Id'];
                    $chave = str_replace('NFe', '', $chave);
                    
                    $notas[] = [
                        'chave' => $chave,
                        'xml' => $conteudo,
                        'numero' => (string)$notaXml->infNFe->ide->nNF,
                        'serie' => (string)$notaXml->infNFe->ide->serie,
                        'data_emissao' => (string)$notaXml->infNFe->ide->dhEmi,
                        'valor' => (string)$notaXml->infNFe->total->ICMSTot->vNF,
                        'cnpj_emitente' => (string)$notaXml->infNFe->emit->CNPJ,
                        'nome_emitente' => (string)$notaXml->infNFe->emit->xNome,
                    ];
                }
            }
            
        } catch (\Exception $e) {
            throw new \Exception('Erro ao processar resposta XML: ' . $e->getMessage());
        }

        return $notas;
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
        $urls = [
            'SP' => [
                'nfe_distribuicao' => $ambiente == '1' 
                    ? 'https://nfe.fazenda.sp.gov.br/nfeWS/services/NFeDistribuicaoDFe'
                    : 'https://homologacao.nfe.fazenda.sp.gov.br/nfeWS/services/NFeDistribuicaoDFe',
            ],
            'MG' => [
                'nfe_distribuicao' => $ambiente == '1'
                    ? 'https://nfe.fazenda.mg.gov.br/nfeWS/services/NFeDistribuicaoDFe'
                    : 'https://hnfe.fazenda.mg.gov.br/nfeWS/services/NFeDistribuicaoDFe',
            ],
            'PR' => [
                'nfe_distribuicao' => $ambiente == '1'
                    ? 'https://nfe.fazenda.pr.gov.br/nfe/NFeDistribuicaoDFe'
                    : 'https://homologacao.nfe.fazenda.pr.gov.br/nfe/NFeDistribuicaoDFe',
            ],
        ];

        return $urls[$uf] ?? $urls['SP'];
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
     * Obtém código numérico da UF
     */
    private function getCodigoUF($uf)
    {
        $codigos = [
            'SP' => '35',
            'MG' => '31',
            'PR' => '41',
            'RJ' => '33',
            'RS' => '43',
            'SC' => '42',
            'BA' => '29',
            'PE' => '26',
        ];
        
        return $codigos[$uf] ?? '35';
    }

    /**
     * Gera chave para consulta
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
        
        if (empty($this->config['cnpj']['cnpj'])) {
            $erros[] = 'CNPJ não configurado';
        }
        
        if (empty($this->certificado)) {
            $erros[] = 'Caminho do certificado digital não configurado';
        }
        
        if (empty($this->senhaCertificado)) {
            $erros[] = 'Senha do certificado não configurada';
        }
        
        return $erros;
    }
}
