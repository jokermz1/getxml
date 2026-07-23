<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\UsuarioModel;
use App\Helpers\UploadHelper;

class AuthController
{
    private $config;
    private $auth;
    private $usuarioModel;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->auth = new Auth($config);
        $this->usuarioModel = new UsuarioModel($config);
    }

    /**
     * Página de login
     */
    public function login()
    {
        // Se já estiver logado, redireciona para dashboard
        if ($this->auth->check()) {
            header('Location: index.php?action=dashboard');
            exit;
        }

        $erros = [];
        $sucesso = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';

            $resultado = $this->auth->login($email, $senha);

            if ($resultado['success']) {
                header('Location: index.php?action=dashboard');
                exit;
            } else {
                $erros[] = $resultado['message'];
            }
        }

        require_once __DIR__ . '/../Views/login.php';
    }

    /**
     * Logout
     */
    public function logout()
    {
        $this->auth->logout();
        header('Location: index.php?action=login');
        exit;
    }

    /**
     * Dashboard do usuário
     */
    public function dashboard()
    {
        $this->auth->requireLogin();
        
        $usuario = $this->auth->user();
        $certificados = $this->usuarioModel->buscarCertificados($usuario['id']);
        
        require_once __DIR__ . '/../Views/dashboard.php';
    }

    /**
     * Página de perfil do usuário
     */
    public function perfil()
    {
        $this->auth->requireLogin();
        
        $usuario = $this->auth->user();
        $erros = [];
        $sucesso = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = $_POST['nome'] ?? '';
            $cnpj = $_POST['cnpj'] ?? '';
            $ie = $_POST['ie'] ?? '';

            if ($this->usuarioModel->atualizarDados($usuario['id'], $nome, $cnpj, $ie)) {
                $sucesso = 'Dados atualizados com sucesso!';
                // Atualiza sessão
                $_SESSION['usuario']['nome'] = $nome;
                $_SESSION['usuario']['cnpj'] = $cnpj;
                $_SESSION['usuario']['ie'] = $ie;
            } else {
                $erros[] = 'Erro ao atualizar dados';
            }
        }

        require_once __DIR__ . '/../Views/perfil.php';
    }

    /**
     * Upload de certificado
     */
    public function uploadCertificado()
    {
        $this->auth->requireLogin();
        
        $usuario = $this->auth->user();
        $erros = [];
        $sucesso = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_FILES['certificado']) || $_FILES['certificado']['error'] !== UPLOAD_ERR_OK) {
                $erros[] = 'Nenhum arquivo enviado';
            } else {
                $senha = $_POST['senha_certificado'] ?? '';
                $sefazUf = $_POST['sefaz_uf'] ?? 'SP';

                if (empty($senha)) {
                    $erros[] = 'Senha do certificado é obrigatória';
                } else {
                    // Faz upload
                    $resultado = UploadHelper::uploadCertificado($_FILES['certificado'], $usuario['id']);

                    if ($resultado['success']) {
                        // Salva no banco
                        $dados = [
                            'usuario_id' => $usuario['id'],
                            'nome_arquivo' => $resultado['nome_arquivo'],
                            'caminho_arquivo' => $resultado['caminho_arquivo'],
                            'senha_certificado' => $senha,
                            'sefaz_uf' => $sefazUf
                        ];

                        if ($this->usuarioModel->adicionarCertificado($dados)) {
                            $sucesso = 'Certificado enviado com sucesso!';
                        } else {
                            $erros[] = 'Erro ao salvar certificado no banco';
                            // Remove arquivo se falhou no banco
                            UploadHelper::removerCertificado($resultado['caminho_arquivo']);
                        }
                    } else {
                        $erros = array_merge($erros, $resultado['errors']);
                    }
                }
            }
        }

        $certificados = $this->usuarioModel->buscarCertificados($usuario['id']);
        require_once __DIR__ . '/../Views/upload_certificado.php';
    }

    /**
     * Remove certificado
     */
    public function removerCertificado()
    {
        $this->auth->requireLogin();
        
        $usuario = $this->auth->user();
        $certificadoId = $_POST['certificado_id'] ?? 0;

        if ($this->usuarioModel->removerCertificado($certificadoId, $usuario['id'])) {
            $_SESSION['sucesso'] = 'Certificado removido com sucesso!';
        } else {
            $_SESSION['erros'] = ['Erro ao remover certificado'];
        }

        header('Location: index.php?action=upload_certificado');
        exit;
    }

    /**
     * Ativa/desativa certificado
     */
    public function toggleCertificado()
    {
        $this->auth->requireLogin();
        
        $usuario = $this->auth->user();
        $certificadoId = $_POST['certificado_id'] ?? 0;
        $ativo = $_POST['ativo'] ?? 0;

        if ($this->usuarioModel->toggleCertificado($certificadoId, $usuario['id'], $ativo)) {
            $_SESSION['sucesso'] = 'Certificado atualizado com sucesso!';
        } else {
            $_SESSION['erros'] = ['Erro ao atualizar certificado'];
        }

        header('Location: index.php?action=upload_certificado');
        exit;
    }

    /**
     * Alterar senha
     */
    public function alterarSenha()
    {
        $this->auth->requireLogin();
        
        $usuario = $this->auth->user();
        $erros = [];
        $sucesso = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $senhaAtual = $_POST['senha_atual'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirmarSenha = $_POST['confirmar_senha'] ?? '';

            if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
                $erros[] = 'Todos os campos são obrigatórios';
            } elseif ($novaSenha !== $confirmarSenha) {
                $erros[] = 'Nova senha e confirmação não conferem';
            } elseif (strlen($novaSenha) < 6) {
                $erros[] = 'Nova senha deve ter no mínimo 6 caracteres';
            } else {
                if ($this->usuarioModel->alterarSenha($usuario['id'], $senhaAtual, $novaSenha)) {
                    $sucesso = 'Senha alterada com sucesso!';
                } else {
                    $erros[] = 'Senha atual incorreta';
                }
            }
        }

        require_once __DIR__ . '/../Views/alterar_senha.php';
    }

    /**
     * Painel admin
     */
    public function admin()
    {
        $this->auth->requireAdmin();
        
        $usuarioLogado = $this->auth->user();
        $usuarios = $this->usuarioModel->listarTodos();
        
        require_once __DIR__ . '/../Views/admin.php';
    }

    /**
     * Criar novo usuário (admin)
     */
    public function adminCriarUsuario()
    {
        $this->auth->requireAdmin();
        
        $erros = [];
        $sucesso = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $cnpj = $_POST['cnpj'] ?? '';
            $ie = $_POST['ie'] ?? '';
            $papel = $_POST['papel'] ?? 'contador';

            if (empty($nome) || empty($email) || empty($senha)) {
                $erros[] = 'Nome, email e senha são obrigatórios';
            } elseif (strlen($senha) < 6) {
                $erros[] = 'Senha deve ter no mínimo 6 caracteres';
            } else {
                $dados = [
                    'nome' => $nome,
                    'email' => $email,
                    'senha' => $senha,
                    'cnpj' => $cnpj,
                    'ie' => $ie,
                    'papel' => $papel,
                    'ativo' => 1
                ];

                $usuarioId = $this->usuarioModel->criar($dados);
                
                if ($usuarioId) {
                    $sucesso = 'Usuário criado com sucesso!';
                } else {
                    $erros[] = 'Erro ao criar usuário (email já pode estar cadastrado)';
                }
            }
        }

        require_once __DIR__ . '/../Views/admin_criar_usuario.php';
    }

    /**
     * Ativa/desativa usuário (admin)
     */
    public function adminToggleUsuario()
    {
        $this->auth->requireAdmin();
        
        $usuarioId = $_POST['usuario_id'] ?? 0;
        $ativo = $_POST['ativo'] ?? 0;

        if ($this->auth->toggleUserStatus($usuarioId, $ativo)) {
            $_SESSION['sucesso'] = 'Status do usuário atualizado com sucesso!';
        } else {
            $_SESSION['erros'] = ['Erro ao atualizar status do usuário'];
        }

        header('Location: index.php?action=admin');
        exit;
    }
}
