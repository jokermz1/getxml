<?php

namespace App\Middleware;

class SecurityMiddleware
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Executa middleware de segurança
     */
    public function handle()
    {
        $this->protegerCSRF();
        $this->protegerXSS();
        $this->protegerSQLInjection();
        $this->protegerClickjacking();
        $this->protegerMIME();
        $this->limitarTaxaRequisicoes();
        $this->validarOrigem();
    }

    /**
     * Proteção contra CSRF
     */
    private function protegerCSRF()
    {
        // Gera token CSRF se não existir
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Obtém token CSRF
     */
    public static function getCSRFToken()
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Valida token CSRF
     */
    public static function validarCSRF($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Gera campo CSRF para formulário
     */
    public static function campoCSRF()
    {
        $token = self::getCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Proteção contra XSS
     */
    private function protegerXSS()
    {
        // Headers de segurança
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
    }

    /**
     * Sanitiza dados de entrada
     */
    public static function sanitizarEntrada($dados)
    {
        if (is_array($dados)) {
            return array_map([self::class, 'sanitizarEntrada'], $dados);
        }
        
        return htmlspecialchars(strip_tags(trim($dados)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Proteção básica contra SQL Injection
     */
    private function protegerSQLInjection()
    {
        // Sanitiza dados GET e POST
        $_GET = self::sanitizarEntrada($_GET);
        $_POST = self::sanitizarEntrada($_POST);
        $_REQUEST = self::sanitizarEntrada($_REQUEST);
    }

    /**
     * Proteção contra Clickjacking
     */
    private function protegerClickjacking()
    {
        header('X-Frame-Options: SAMEORIGIN');
        
        // Alternative: Content-Security-Policy
        // header("Content-Security-Policy: frame-ancestors 'self'");
    }

    /**
     * Proteção contra MIME sniffing
     */
    private function protegerMIME()
    {
        header('X-Content-Type-Options: nosniff');
    }

    /**
     * Limitação básica de taxa de requisições
     */
    private function limitarTaxaRequisicoes()
    {
        $ip = $this->obterIP();
        $chave = 'rate_limit_' . $ip;
        
        if (!isset($_SESSION[$chave])) {
            $_SESSION[$chave] = [
                'count' => 0,
                'time' => time()
            ];
        }
        
        $limite = 100; // requisições por minuto
        $intervalo = 60; // segundos
        
        // Reset se passou o intervalo
        if (time() - $_SESSION[$chave]['time'] > $intervalo) {
            $_SESSION[$chave] = [
                'count' => 0,
                'time' => time()
            ];
        }
        
        // Incrementa contador
        $_SESSION[$chave]['count']++;
        
        // Verifica limite
        if ($_SESSION[$chave]['count'] > $limite) {
            http_response_code(429);
            die('Muitas requisições. Tente novamente mais tarde.');
        }
    }

    /**
     * Valida origem da requisição
     */
    private function validarOrigem()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $origem = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
            
            if (!empty($origem)) {
                $hostsPermitidos = [
                    'localhost',
                    '127.0.0.1',
                    $_SERVER['HTTP_HOST'] ?? ''
                ];
                
                $origemPermitida = false;
                
                foreach ($hostsPermitidos as $host) {
                    if (strpos($origem, $host) !== false) {
                        $origemPermitida = true;
                        break;
                    }
                }
                
                if (!$origemPermitida && $this->config['app']['env'] === 'production') {
                    http_response_code(403);
                    die('Origem não permitida');
                }
            }
        }
    }

    /**
     * Obtém IP do cliente
     */
    private function obterIP()
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
     * Valida senha forte
     */
    public static function validarSenha($senha)
    {
        // Mínimo 8 caracteres
        if (strlen($senha) < 8) {
            return false;
        }
        
        // Deve conter letras maiúsculas e minúsculas
        if (!preg_match('/[A-Z]/', $senha) || !preg_match('/[a-z]/', $senha)) {
            return false;
        }
        
        // Deve conter números
        if (!preg_match('/[0-9]/', $senha)) {
            return false;
        }
        
        // Deve conter caracteres especiais
        if (!preg_match('/[^A-Za-z0-9]/', $senha)) {
            return false;
        }
        
        return true;
    }

    /**
     * Gera hash de senha
     */
    public static function hashSenha($senha)
    {
        return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verifica senha
     */
    public static function verificarSenha($senha, $hash)
    {
        return password_verify($senha, $hash);
    }

    /**
     * Gera token seguro
     */
    public static function gerarToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Criptografa dados sensíveis
     */
    public static function criptografar($dados, $chave)
    {
        $iv = random_bytes(16);
        $dadosCriptografados = openssl_encrypt(
            $dados,
            'AES-256-CBC',
            $chave,
            0,
            $iv
        );
        
        return base64_encode($iv . $dadosCriptografados);
    }

    /**
     * Descriptografa dados sensíveis
     */
    public static function descriptografar($dadosCriptografados, $chave)
    {
        $dados = base64_decode($dadosCriptografados);
        $iv = substr($dados, 0, 16);
        $dadosCriptografados = substr($dados, 16);
        
        return openssl_decrypt(
            $dadosCriptografados,
            'AES-256-CBC',
            $chave,
            0,
            $iv
        );
    }

    /**
     * Valida se ambiente é seguro
     */
    public static function ambienteSeguro()
    {
        // Verifica se está em produção
        if (($this->config['app']['env'] ?? 'production') === 'production') {
            // Verifica se está usando HTTPS
            if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Força HTTPS em produção
     */
    public static function forcarHTTPS()
    {
        if (($this->config['app']['env'] ?? 'production') === 'production') {
            if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
                $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                header("Location: {$url}");
                exit;
            }
        }
    }

    /**
     * Remove dados sensíveis de array
     */
    public static function removerDadosSensiveis($dados, $camposSensiveis = ['senha', 'password', 'token', 'cpf', 'cnpj'])
    {
        foreach ($camposSensiveis as $campo) {
            if (isset($dados[$campo])) {
                $dados[$campo] = '***REMOVIDO***';
            }
        }
        
        return $dados;
    }
}
