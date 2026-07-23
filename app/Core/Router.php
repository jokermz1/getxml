<?php

namespace App\Core;

class Router
{
    private $rotas = [];
    private $parametros = [];

    /**
     * Adiciona uma rota GET
     */
    public function get($caminho, $acao)
    {
        $this->adicionarRota('GET', $caminho, $acao);
    }

    /**
     * Adiciona uma rota POST
     */
    public function post($caminho, $acao)
    {
        $this->adicionarRota('POST', $caminho, $acao);
    }

    /**
     * Adiciona uma rota qualquer método
     */
    public function any($caminho, $acao)
    {
        $this->adicionarRota('GET|POST', $caminho, $acao);
    }

    /**
     * Adiciona uma rota
     */
    private function adicionarRota($metodo, $caminho, $acao)
    {
        $this->rotas[] = [
            'metodo' => $metodo,
            'caminho' => $caminho,
            'acao' => $acao
        ];
    }

    /**
     * Dispatch da rota
     */
    public function dispatch()
    {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $caminho = $this->getCaminho();
        
        foreach ($this->rotas as $rota) {
            if ($this->correspondeRota($rota, $metodo, $caminho)) {
                return $this->executarAcao($rota['acao']);
            }
        }
        
        // Rota padrão (sistema legado)
        return $this->dispatchLegado();
    }

    /**
     * Obtém caminho da URL
     */
    private function getCaminho()
    {
        $caminho = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($caminho, '?')) !== false) {
            $caminho = substr($caminho, 0, $pos);
        }
        
        // Remove diretório base se estiver subdiretório
        $diretorioBase = dirname($_SERVER['SCRIPT_NAME']);
        if ($diretorioBase != '/' && strpos($caminho, $diretorioBase) === 0) {
            $caminho = substr($caminho, strlen($diretorioBase));
        }
        
        return $caminho ?: '/';
    }

    /**
     * Verifica se rota corresponde
     */
    private function correspondeRota($rota, $metodo, $caminho)
    {
        // Verifica método
        if (!preg_match('/^(' . $rota['metodo'] . ')$/', $metodo)) {
            return false;
        }
        
        // Converte rota para regex
        $padrao = $this->converterParaRegex($rota['caminho']);
        
        // Verifica caminho
        if (!preg_match($padrao, $caminho, $correspondencias)) {
            return false;
        }
        
        // Extrai parâmetros
        array_shift($correspondencias);
        $this->parametros = $correspondencias;
        
        return true;
    }

    /**
     * Converte caminho da rota para regex
     */
    private function converterParaRegex($caminho)
    {
        // Escapa barras
        $caminho = str_replace('/', '\/', $caminho);
        
        // Converte parâmetros {param} para regex
        $caminho = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^\/]+)', $caminho);
        
        return '/^' . $caminho . '$/';
    }

    /**
     * Executa ação da rota
     */
    private function executarAcao($acao)
    {
        // Se for callable
        if (is_callable($acao)) {
            return call_user_func_array($acao, $this->parametros);
        }
        
        // Se for string no formato "Controller@metodo"
        if (is_string($acao) && strpos($acao, '@') !== false) {
            list($controlador, $metodo) = explode('@', $acao);
            
            $controlador = "App\\Controllers\\{$controlador}";
            
            if (class_exists($controlador)) {
                $instancia = new $controlador();
                
                if (method_exists($instancia, $metodo)) {
                    return call_user_func_array([$instancia, $metodo], $this->parametros);
                }
            }
        }
        
        throw new \Exception("Ação não encontrada: " . (is_string($acao) ? $acao : 'callable'));
    }

    /**
     * Dispatch do sistema legado (compatibilidade)
     */
    private function dispatchLegado()
    {
        $action = $_GET['action'] ?? 'home';
        
        return $action;
    }

    /**
     * Obtém parâmetros da rota
     */
    public function getParametros()
    {
        return $this->parametros;
    }

    /**
     * Obtém parâmetro específico
     */
    public function getParametro($nome, $padrao = null)
    {
        return $this->parametros[$nome] ?? $padrao;
    }

    /**
     * Gera URL para uma rota
     */
    public function url($nome, $parametros = [])
    {
        // Implementação básica - pode ser expandida
        $url = '/index.php';
        
        if (!empty($parametros)) {
            $url .= '?' . http_build_query($parametros);
        }
        
        return $url;
    }

    /**
     * Redireciona para uma rota
     */
    public function redirecionar($nome, $parametros = [])
    {
        $url = $this->url($nome, $parametros);
        header("Location: {$url}");
        exit;
    }
}
