<?php

namespace App\Core;

class Auth
{
    private $db;
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        try {
            $this->db = Database::getInstance($config['database']);
        } catch (Exception $e) {
            // Se não conseguir conectar, não inicializa
            $this->db = null;
        }
    }

    /**
     * Tenta fazer login do usuário
     */
    public function login($email, $senha)
    {
        if ($this->db === null) {
            return [
                'success' => false,
                'message' => 'Banco de dados não disponível'
            ];
        }

        try {
            $usuario = $this->db->selectOne(
                "SELECT * FROM usuarios WHERE email = :email AND ativo = 1",
                ['email' => $email]
            );

            if (!$usuario) {
                return [
                    'success' => false,
                    'message' => 'Usuário ou senha incorretos'
                ];
            }

            if (!password_verify($senha, $usuario['senha'])) {
                return [
                    'success' => false,
                    'message' => 'Usuário ou senha incorretos'
                ];
            }

            // Remove senha do array antes de salvar na sessão
            unset($usuario['senha']);

            // Salva usuário na sessão
            $_SESSION['usuario'] = $usuario;
            $_SESSION['usuario_logado'] = true;
            $_SESSION['login_time'] = time();

            // Registra log
            $this->registrarLog($usuario['id'], 'login', 'Usuário fez login');

            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'usuario' => $usuario
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao fazer login: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Faz logout do usuário
     */
    public function logout()
    {
        if ($this->check()) {
            $this->registrarLog($_SESSION['usuario']['id'], 'logout', 'Usuário fez logout');
        }

        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    /**
     * Verifica se usuário está logado
     */
    public function check()
    {
        return isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;
    }

    /**
     * Obtém usuário logado
     */
    public function user()
    {
        return $_SESSION['usuario'] ?? null;
    }

    /**
     * Obtém ID do usuário logado
     */
    public function id()
    {
        return $_SESSION['usuario']['id'] ?? null;
    }

    /**
     * Verifica se usuário é admin
     */
    public function isAdmin()
    {
        $usuario = $this->user();
        return $usuario && $usuario['papel'] === 'admin';
    }

    /**
     * Verifica se usuário é contador
     */
    public function isContador()
    {
        $usuario = $this->user();
        return $usuario && $usuario['papel'] === 'contador';
    }

    /**
     * Verifica se usuário tem permissão
     */
    public function hasPermission($papel)
    {
        $usuario = $this->user();
        return $usuario && $usuario['papel'] === $papel;
    }

    /**
     * Exige que usuário esteja logado
     */
    public function requireLogin()
    {
        if (!$this->check()) {
            header('Location: index.php?action=login');
            exit;
        }
    }

    /**
     * Exige que usuário seja admin
     */
    public function requireAdmin()
    {
        $this->requireLogin();
        
        if (!$this->isAdmin()) {
            header('Location: index.php?action=dashboard');
            exit;
        }
    }

    /**
     * Registra log de ação
     */
    private function registrarLog($usuarioId, $acao, $descricao = '')
    {
        if ($this->db === null) {
            return;
        }

        try {
            $this->db->insert('logs_sistema', [
                'usuario_id' => $usuarioId,
                'acao' => $acao,
                'descricao' => $descricao,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido'
            ]);
        } catch (Exception $e) {
            // Silencioso - não deve quebrar o sistema se log falhar
        }
    }

    /**
     * Atualiza último acesso do usuário
     */
    public function atualizarUltimoAcesso()
    {
        if ($this->db === null || !$this->check()) {
            return;
        }

        try {
            $this->db->update(
                'usuarios',
                ['atualizado_em' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $this->id()]
            );
        } catch (Exception $e) {
            // Silencioso
        }
    }

    /**
     * Verifica se sessão expirou
     */
    public function verificarExpiracao($tempoMaximo = 3600)
    {
        if (!$this->check()) {
            return false;
        }

        $tempo = time() - ($_SESSION['login_time'] ?? 0);
        
        if ($tempo > $tempoMaximo) {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Cria novo usuário
     */
    public function registrar($dados)
    {
        if ($this->db === null) {
            return [
                'success' => false,
                'message' => 'Banco de dados não disponível'
            ];
        }

        try {
            // Verifica se email já existe
            $existente = $this->db->selectOne(
                "SELECT id FROM usuarios WHERE email = :email",
                ['email' => $dados['email']]
            );

            if ($existente) {
                return [
                    'success' => false,
                    'message' => 'Email já cadastrado'
                ];
            }

            // Hash da senha
            $dados['senha'] = password_hash($dados['senha'], PASSWORD_BCRYPT);

            // Insere usuário
            $usuarioId = $this->db->insert('usuarios', $dados);

            return [
                'success' => true,
                'message' => 'Usuário criado com sucesso',
                'usuario_id' => $usuarioId
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar usuário: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtém todos os usuários (admin only)
     */
    public function getAllUsers()
    {
        if ($this->db === null) {
            return [];
        }

        try {
            $usuarios = $this->db->select("SELECT id, nome, email, cnpj, ie, papel, ativo, criado_em FROM usuarios ORDER BY nome");
            
            // Remove senhas do resultado
            foreach ($usuarios as &$usuario) {
                unset($usuario['senha']);
            }
            
            return $usuarios;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Ativa/desativa usuário
     */
    public function toggleUserStatus($usuarioId, $ativo)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            $this->db->update(
                'usuarios',
                ['ativo' => $ativo ? 1 : 0],
                'id = :id',
                ['id' => $usuarioId]
            );

            $this->registrarLog($this->id(), 'toggle_user_status', "Usuário {$usuarioId} " . ($ativo ? 'ativado' : 'desativado'));

            return true;

        } catch (Exception $e) {
            return false;
        }
    }
}
