<?php

namespace App\Controllers;

use App\Models\SefazModel;
use App\Models\NotaFiscalModel;

class SefazController
{
    private $config;
    private $sefazModel;
    private $notaFiscalModel;

    public function __construct(array $config)
    {
        $this->config = $config;
        
        // Tenta obter certificado do usuário logado
        $auth = new \App\Core\Auth($config);
        $certificadoUsuario = null;
        
        if ($auth->check()) {
            $usuarioModel = new \App\Models\UsuarioModel($config);
            $certificadoUsuario = $usuarioModel->buscarCertificadoAtivo($auth->id());
        }
        
        $this->sefazModel = new SefazModel($config, $certificadoUsuario);
        $this->notaFiscalModel = new NotaFiscalModel($config['storage']['path'], $config);
        $this->auth = $auth;
    }

    /**
     * Página inicial
     */
    public function home()
    {
        $estatisticas = $this->notaFiscalModel->getEstatisticas();
        require_once __DIR__ . '/../Views/home.php';
    }

    /**
     * Página de busca de notas
     */
    public function buscar()
    {
        $erros = [];
        $sucesso = '';
        $notasEncontradas = [];

        // Verifica configurações
        $errosConfig = $this->sefazModel->validarConfiguracoes();
        if (!empty($errosConfig)) {
            $erros = array_merge($erros, $errosConfig);
        }

        // Processa formulário de busca
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dataInicio = $_POST['data_inicio'] ?? null;
            $dataFim = $_POST['data_fim'] ?? null;

            if (empty($dataInicio) || empty($dataFim)) {
                $erros[] = 'Informe a data início e data fim';
            } else {
                try {
                    $notasEncontradas = $this->sefazModel->buscarNotasPorPeriodo($dataInicio, $dataFim);
                    
                    if (empty($notasEncontradas)) {
                        $sucesso = 'Nenhuma nota encontrada no período informado.';
                    } else {
                        $sucesso = 'Foram encontradas ' . count($notasEncontradas) . ' notas.';
                    }
                    
                } catch (\Exception $e) {
                    $erros[] = 'Erro ao buscar notas: ' . $e->getMessage();
                }
            }
        }

        require_once __DIR__ . '/../Views/buscar.php';
    }

    /**
     * Salva XML de nota fiscal
     */
    public function salvar()
    {
        $sucesso = '';
        $erros = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $notaData = $_POST['nota_data'] ?? null;
            
            if (empty($notaData)) {
                $erros[] = 'Dados da nota não informados';
            } else {
                $nota = json_decode($notaData, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $erros[] = 'Erro ao processar dados da nota';
                } else {
                    try {
                        // Salvar arquivo XML em diretório do usuário
                        $usuarioId = $this->auth->id();
                        $caminhoUsuario = $this->config['storage']['path'] . '/' . $usuarioId;
                        
                        if (!is_dir($caminhoUsuario)) {
                            mkdir($caminhoUsuario, 0755, true);
                        }
                        
                        $caminhoXml = $this->sefazModel->salvarXml($nota, $caminhoUsuario);
                        
                        if ($caminhoXml) {
                            $nota['arquivo_xml'] = $caminhoXml;
                            $nota['usuario_id'] = $usuarioId; // Adiciona ID do usuário
                            
                            // Salvar no banco de dados local
                            if ($this->notaFiscalModel->adicionarNota($nota)) {
                                $sucesso = 'Nota fiscal salva com sucesso!';
                            } else {
                                $sucesso = 'Nota fiscal já existe no sistema.';
                            }
                        } else {
                            $erros[] = 'Erro ao salvar arquivo XML';
                        }
                        
                    } catch (\Exception $e) {
                        $erros[] = 'Erro ao salvar nota: ' . $e->getMessage();
                    }
                }
            }
        }

        // Redirecionar de volta para a página de busca
        $_SESSION['sucesso'] = $sucesso;
        $_SESSION['erros'] = $erros;
        header('Location: index.php?action=buscar');
        exit;
    }

    /**
     * Lista notas fiscais capturadas
     */
    public function listar()
    {
        $sucesso = $_SESSION['sucesso'] ?? '';
        $erros = $_SESSION['erros'] ?? [];
        
        unset($_SESSION['sucesso']);
        unset($_SESSION['erros']);

        // Construir filtros
        $filtros = [];
        
        if (!empty($_GET['filtro_data_inicio'])) {
            $filtros['data_inicio'] = $_GET['filtro_data_inicio'];
        }
        
        if (!empty($_GET['filtro_data_fim'])) {
            $filtros['data_fim'] = $_GET['filtro_data_fim'];
        }
        
        if (!empty($_GET['filtro_cnpj'])) {
            $filtros['cnpj'] = $_GET['filtro_cnpj'];
        }

        $notas = $this->notaFiscalModel->listarNotas($filtros);

        require_once __DIR__ . '/../Views/listar.php';
    }

    /**
     * Exclui uma nota fiscal
     */
    public function excluir()
    {
        $sucesso = '';
        $erros = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $chave = $_POST['chave'] ?? null;
            
            if (empty($chave)) {
                $erros[] = 'Chave da nota não informada';
            } else {
                if ($this->notaFiscalModel->removerNota($chave)) {
                    $sucesso = 'Nota fiscal excluída com sucesso!';
                } else {
                    $erros[] = 'Nota não encontrada';
                }
            }
        }

        $_SESSION['sucesso'] = $sucesso;
        $_SESSION['erros'] = $erros;
        header('Location: index.php?action=listar');
        exit;
    }

    /**
     * Página de configurações
     */
    public function config()
    {
        $sucesso = '';
        $erros = [];

        require_once __DIR__ . '/../Views/config.php';
    }
}
