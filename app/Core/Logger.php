<?php

namespace App\Core;

class Logger
{
    private $diretorioLogs;
    private $contexto;
    private $nivelLog = 'DEBUG';

    const NIVEIS = [
        'DEBUG' => 1,
        'INFO' => 2,
        'AVISO' => 3,
        'ERRO' => 4,
        'CRITICO' => 5
    ];

    public function __construct($diretorioLogs = 'storage/logs', $contexto = 'app')
    {
        $this->diretorioLogs = $diretorioLogs;
        $this->contexto = $contexto;
        
        // Cria diretório de logs se não existir
        if (!is_dir($diretorioLogs)) {
            mkdir($diretorioLogs, 0755, true);
        }
    }

    /**
     * Define nível mínimo de log
     */
    public function setNivelLog($nivel)
    {
        if (isset(self::NIVEIS[$nivel])) {
            $this->nivelLog = $nivel;
        }
    }

    /**
     * Registra mensagem de debug
     */
    public function debug($mensagem, $contexto = [])
    {
        $this->log('DEBUG', $mensagem, $contexto);
    }

    /**
     * Registra mensagem de info
     */
    public function info($mensagem, $contexto = [])
    {
        $this->log('INFO', $mensagem, $contexto);
    }

    /**
     * Registra mensagem de aviso
     */
    public function aviso($mensagem, $contexto = [])
    {
        $this->log('AVISO', $mensagem, $contexto);
    }

    /**
     * Registra mensagem de erro
     */
    public function erro($mensagem, $contexto = [])
    {
        $this->log('ERRO', $mensagem, $contexto);
    }

    /**
     * Registra mensagem crítica
     */
    public function critico($mensagem, $contexto = [])
    {
        $this->log('CRITICO', $mensagem, $contexto);
    }

    /**
     * Registra mensagem de log
     */
    private function log($nivel, $mensagem, $contexto = [])
    {
        // Verifica se nível deve ser registrado
        if (self::NIVEIS[$nivel] < self::NIVEIS[$this->nivelLog]) {
            return;
        }

        $dataHora = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $usuario = $_SESSION['usuario_id'] ?? 'anonimo';
        
        // Formata mensagem
        $linha = sprintf(
            "[%s] [%s] [%s] [%s] %s %s\n",
            $dataHora,
            $nivel,
            $ip,
            $usuario,
            $mensagem,
            !empty($contexto) ? json_encode($contexto, JSON_UNESCAPED_UNICODE) : ''
        );

        // Escreve no arquivo de log
        $arquivoLog = $this->obterArquivoLog();
        file_put_contents($arquivoLog, $linha, FILE_APPEND | LOCK_EX);

        // Em desenvolvimento, também exibe na tela
        if ($this->isDesenvolvimento()) {
            error_log($linha);
        }
    }

    /**
     * Obtém caminho do arquivo de log
     */
    private function obterArquivoLog()
    {
        $data = date('Y-m-d');
        return $this->diretorioLogs . '/' . $this->contexto . '_' . $data . '.log';
    }

    /**
     * Verifica se é ambiente de desenvolvimento
     */
    private function isDesenvolvimento()
    {
        return ($_ENV['APP_ENV'] ?? 'production') === 'development';
    }

    /**
     * Registra exceção
     */
    public function excecao(\Exception $e, $contexto = [])
    {
        $mensagem = sprintf(
            "Exceção: %s em %s:%d",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        $contexto['trace'] = $e->getTraceAsString();
        $this->erro($mensagem, $contexto);
    }

    /**
     * Registra acesso a rota
     */
    public function acesso($rota, $metodo = 'GET')
    {
        $url = $_SERVER['REQUEST_URI'] ?? 'CLI';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';
        
        $contexto = [
            'rota' => $rota,
            'metodo' => $metodo,
            'url' => $url,
            'user_agent' => $userAgent
        ];

        $this->info("Acesso à rota: {$rota}", $contexto);
    }

    /**
     * Registra operação de banco de dados
     */
    public function banco($operacao, $tabela, $contexto = [])
    {
        $mensagem = "Operação de banco: {$operacao} na tabela {$tabela}";
        $this->debug($mensagem, $contexto);
    }

    /**
     * Registra operação de API externa
     */
    public function api($servico, $operacao, $contexto = [])
    {
        $mensagem = "Chamada API: {$servico} - {$operacao}";
        $this->info($mensagem, $contexto);
    }

    /**
     * Registra erro de API SEFAZ
     */
    public function erroSEFAZ($operacao, $erro, $contexto = [])
    {
        $mensagem = "Erro SEFAZ: {$operacao} - {$erro}";
        $contexto['servico'] = 'SEFAZ';
        $this->erro($mensagem, $contexto);
    }

    /**
     * Registra operação com arquivo
     */
    public function arquivo($operacao, $arquivo, $contexto = [])
    {
        $mensagem = "Operação arquivo: {$operacao} - {$arquivo}";
        $this->debug($mensagem, $contexto);
    }

    /**
     * Limpa logs antigos
     */
    public function limparLogsAntigos($dias = 30)
    {
        $arquivos = glob($this->diretorioLogs . '/*.log');
        $dataLimite = strtotime("-{$dias} days");
        
        foreach ($arquivos as $arquivo) {
            if (filemtime($arquivo) < $dataLimite) {
                unlink($arquivo);
                $this->info("Log antigo removido: {$arquivo}");
            }
        }
    }

    /**
     * Obtém estatísticas dos logs
     */
    public function getEstatisticas()
    {
        $arquivos = glob($this->diretorioLogs . '/*.log');
        $estatisticas = [
            'total_arquivos' => count($arquivos),
            'tamanho_total' => 0,
            'por_nivel' => [
                'DEBUG' => 0,
                'INFO' => 0,
                'AVISO' => 0,
                'ERRO' => 0,
                'CRITICO' => 0
            ]
        ];

        foreach ($arquivos as $arquivo) {
            $estatisticas['tamanho_total'] += filesize($arquivo);
            
            $conteudo = file_get_contents($arquivo);
            $linhas = explode("\n", $conteudo);
            
            foreach ($linhas as $linha) {
                foreach (array_keys($estatisticas['por_nivel']) as $nivel) {
                    if (strpos($linha, "[{$nivel}]") !== false) {
                        $estatisticas['por_nivel'][$nivel]++;
                    }
                }
            }
        }

        return $estatisticas;
    }

    /**
     * Obtém logs recentes
     */
    public function getLogsRecentes($quantidade = 100)
    {
        $arquivoLog = $this->obterArquivoLog();
        
        if (!file_exists($arquivoLog)) {
            return [];
        }

        $linhas = array_reverse(file($arquivoLog));
        $logs = [];

        foreach (array_slice($linhas, 0, $quantidade) as $linha) {
            if (!empty(trim($linha))) {
                $logs[] = $this->parseLinhaLog($linha);
            }
        }

        return $logs;
    }

    /**
     * Parse de linha de log
     */
    private function parseLinhaLog($linha)
    {
        // Padrão: [2024-01-01 12:00:00] [NIVEL] [IP] [USUARIO] mensagem contexto
        $padrao = '/\[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] (.*)/';
        
        if (preg_match($padrao, $linha, $correspondencias)) {
            return [
                'data_hora' => $correspondencias[1],
                'nivel' => $correspondencias[2],
                'ip' => $correspondencias[3],
                'usuario' => $correspondencias[4],
                'mensagem' => $correspondencias[5]
            ];
        }

        return [
            'raw' => $linha
        ];
    }

    /**
     * Exporta logs para arquivo
     */
    public function exportarLogs($dataInicio, $dataFim, $arquivoSaida)
    {
        $arquivos = glob($this->diretorioLogs . '/*.log');
        $conteudoExportado = '';
        
        $inicio = strtotime($dataInicio);
        $fim = strtotime($dataFim);

        foreach ($arquivos as $arquivo) {
            $dataArquivo = strtotime(str_replace([$this->diretorioLogs . '/', $this->contexto . '_', '.log'], '', $arquivo));
            
            if ($dataArquivo >= $inicio && $dataArquivo <= $fim) {
                $conteudoExportado .= file_get_contents($arquivo) . "\n";
            }
        }

        file_put_contents($arquivoSaida, $conteudoExportado);
        return $arquivoSaida;
    }

    /**
     * Cria logger específico
     */
    public static function criar($contexto = 'app')
    {
        return new self('storage/logs', $contexto);
    }
}
