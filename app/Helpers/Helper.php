<?php

namespace App\Helpers;

class Helper
{
    /**
     * Formata CNPJ para exibição
     */
    public static function formatarCNPJ($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) != 14) {
            return $cnpj;
        }
        
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }

    /**
     * Formata CPF para exibição
     */
    public static function formatarCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return $cpf;
        }
        
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    /**
     * Formata valor monetário
     */
    public static function formatarMoeda($valor)
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    /**
     * Formata data para exibição
     */
    public static function formatarData($data, $formato = 'd/m/Y H:i')
    {
        if (empty($data)) {
            return '';
        }
        
        $timestamp = strtotime($data);
        
        if ($timestamp === false) {
            return $data;
        }
        
        return date($formato, $timestamp);
    }

    /**
     * Formata data para banco de dados
     */
    public static function formatarDataBanco($data)
    {
        if (empty($data)) {
            return null;
        }
        
        $data = str_replace('/', '-', $data);
        $timestamp = strtotime($data);
        
        if ($timestamp === false) {
            return null;
        }
        
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Valida CNPJ
     */
    public static function validarCNPJ($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        // Valida dígitos verificadores
        for ($i = 0; $i <= 1; $i++) {
            $soma = 0;
            $multiplicador = $i == 0 ? 5 : 6;
            
            for ($j = 0; $j < 12 + $i; $j++) {
                $soma += $cnpj[$j] * $multiplicador;
                $multiplicador = $multiplicador == 9 ? 2 : $multiplicador + 1;
            }
            
            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;
            
            if ($cnpj[12 + $i] != $digito) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Valida CPF
     */
    public static function validarCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Valida dígitos verificadores
        for ($i = 0; $i <= 1; $i++) {
            $soma = 0;
            $multiplicador = $i == 0 ? 10 : 11;
            
            for ($j = 0; $j < 9 + $i; $j++) {
                $soma += $cpf[$j] * $multiplicador;
                $multiplicador--;
            }
            
            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;
            
            if ($cpf[9 + $i] != $digito) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Remove caracteres especiais
     */
    public static function limparString($string)
    {
        return preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
    }

    /**
     * Gera slug a partir de uma string
     */
    public static function gerarSlug($string)
    {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        $string = trim($string, '-');
        
        return $string;
    }

    /**
     * Trunca texto
     */
    public static function truncarTexto($texto, $tamanho = 100, $sufixo = '...')
    {
        if (strlen($texto) <= $tamanho) {
            return $texto;
        }
        
        return substr($texto, 0, $tamanho) . $sufixo;
    }

    /**
     * Converte bytes para formato legível
     */
    public static function formatarBytes($bytes, $precisao = 2)
    {
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precisao) . ' ' . $unidades[$i];
    }

    /**
     * Gera UUID
     */
    public static function gerarUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Sanitiza entrada de dados
     */
    public static function sanitizar($dados)
    {
        if (is_array($dados)) {
            return array_map([self::class, 'sanitizar'], $dados);
        }
        
        return htmlspecialchars(strip_tags(trim($dados)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Redireciona para uma URL
     */
    public static function redirecionar($url)
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Retorna resposta JSON
     */
    public static function json($dados, $codigo = 200)
    {
        http_response_code($codigo);
        header('Content-Type: application/json');
        echo json_encode($dados);
        exit;
    }

    /**
     * Verifica se é requisição AJAX
     */
    public static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Obtém IP do cliente
     */
    public static function getClienteIP()
    {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }

    /**
     * Obtém user agent do cliente
     */
    public static function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';
    }

    /**
     * Debug variável
     */
    public static function debug($variavel, $morrer = false)
    {
        echo '<pre>';
        var_dump($variavel);
        echo '</pre>';
        
        if ($morrer) {
            die();
        }
    }

    /**
     * Calcula diferença entre datas
     */
    public static function diferencaDatas($data1, $data2, $formato = '%a')
    {
        $data1 = new \DateTime($data1);
        $data2 = new \DateTime($data2);
        $intervalo = $data1->diff($data2);
        
        return $intervalo->format($formato);
    }

    /**
     * Verifica se data está no intervalo
     */
    public static function dataNoIntervalo($data, $inicio, $fim)
    {
        $data = strtotime($data);
        $inicio = strtotime($inicio);
        $fim = strtotime($fim);
        
        return $data >= $inicio && $data <= $fim;
    }

    /**
     * Gera QR Code (URL para API)
     */
    public static function gerarQRCode($texto, $tamanho = 150)
    {
        $texto = urlencode($texto);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$tamanho}x{$tamanho}&data={$texto}";
    }

    /**
     * Verifica se ambiente é desenvolvimento
     */
    public static function isDesenvolvimento()
    {
        return ($_ENV['APP_ENV'] ?? 'production') == 'development';
    }

    /**
     * Obtém URL base do projeto
     */
    public static function urlBase()
    {
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $caminho = dirname($_SERVER['PHP_SELF']);
        
        return "{$protocolo}://{$host}{$caminho}";
    }

    /**
     * Obtém URL atual
     */
    public static function urlAtual()
    {
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        return "{$protocolo}://{$host}{$uri}";
    }

    /**
     * Adiciona mensagem na sessão
     */
    public static function setMensagem($tipo, $mensagem)
    {
        if (!isset($_SESSION['mensagens'])) {
            $_SESSION['mensagens'] = [];
        }
        
        $_SESSION['mensagens'][] = [
            'tipo' => $tipo,
            'mensagem' => $mensagem
        ];
    }

    /**
     * Obtém mensagens da sessão
     */
    public static function getMensagens()
    {
        $mensagens = $_SESSION['mensagens'] ?? [];
        unset($_SESSION['mensagens']);
        
        return $mensagens;
    }

    /**
     * Exibe mensagens da sessão
     */
    public static function exibirMensagens()
    {
        $mensagens = self::getMensagens();
        
        foreach ($mensagens as $msg) {
            $classe = match($msg['tipo']) {
                'sucesso' => 'alert-success',
                'erro' => 'alert-error',
                'info' => 'alert-info',
                'aviso' => 'alert-warning',
                default => 'alert-info'
            };
            
            echo "<div class='alert {$classe}'>";
            echo htmlspecialchars($msg['mensagem']);
            echo "</div>";
        }
    }
}
