<?php

namespace App\Models;

use App\Core\Database;

class UsuarioModel
{
    private $db;

    public function __construct(array $config)
    {
        try {
            $this->db = Database::getInstance($config['database']);
        } catch (Exception $e) {
            $this->db = null;
        }
    }

    /**
     * Obtém usuário por ID
     */
    public function buscarPorId($id)
    {
        if ($this->db === null) {
            return null;
        }

        try {
            $usuario = $this->db->selectOne(
                "SELECT id, nome, email, cnpj, ie, papel, ativo, criado_em, atualizado_em FROM usuarios WHERE id = :id",
                ['id' => $id]
            );
            return $usuario;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Obtém usuário por email
     */
    public function buscarPorEmail($email)
    {
        if ($this->db === null) {
            return null;
        }

        try {
            $usuario = $this->db->selectOne(
                "SELECT * FROM usuarios WHERE email = :email",
                ['email' => $email]
            );
            return $usuario;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Cria novo usuário
     */
    public function criar($dados)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            // Hash da senha
            if (isset($dados['senha'])) {
                $dados['senha'] = password_hash($dados['senha'], PASSWORD_BCRYPT);
            }

            $usuarioId = $this->db->insert('usuarios', $dados);
            return $usuarioId;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Atualiza usuário
     */
    public function atualizar($id, $dados)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            // Se estiver atualizando senha, faz hash
            if (isset($dados['senha'])) {
                $dados['senha'] = password_hash($dados['senha'], PASSWORD_BCRYPT);
            }

            $this->db->update(
                'usuarios',
                $dados,
                'id = :id',
                ['id' => $id]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remove usuário
     */
    public function remover($id)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            $this->db->delete('usuarios', 'id = :id', ['id' => $id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Lista todos os usuários
     */
    public function listarTodos($filtros = [])
    {
        if ($this->db === null) {
            return [];
        }

        try {
            $query = "SELECT id, nome, email, cnpj, ie, papel, ativo, criado_em FROM usuarios";
            $params = [];

            if (!empty($filtros['papel'])) {
                $query .= " WHERE papel = :papel";
                $params['papel'] = $filtros['papel'];
            }

            if (!empty($filtros['ativo'])) {
                if (strpos($query, 'WHERE') === false) {
                    $query .= " WHERE ativo = :ativo";
                } else {
                    $query .= " AND ativo = :ativo";
                }
                $params['ativo'] = $filtros['ativo'];
            }

            $query .= " ORDER BY nome";

            $usuarios = $this->db->select($query, $params);
            return $usuarios;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obtém certificados do usuário
     */
    public function buscarCertificados($usuarioId)
    {
        if ($this->db === null) {
            return [];
        }

        try {
            $certificados = $this->db->select(
                "SELECT id, nome_arquivo, caminho_arquivo, sefaz_uf, ativo, criado_em 
                 FROM certificados 
                 WHERE usuario_id = :usuario_id 
                 ORDER BY criado_em DESC",
                ['usuario_id' => $usuarioId]
            );
            return $certificados;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Adiciona certificado ao usuário
     */
    public function adicionarCertificado($dados)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            $certificadoId = $this->db->insert('certificados', $dados);
            return $certificadoId;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remove certificado
     */
    public function removerCertificado($certificadoId, $usuarioId)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            // Primeiro obtém o caminho do arquivo
            $certificado = $this->db->selectOne(
                "SELECT caminho_arquivo FROM certificados WHERE id = :id AND usuario_id = :usuario_id",
                ['id' => $certificadoId, 'usuario_id' => $usuarioId]
            );

            if ($certificado) {
                // Remove arquivo do sistema
                if (file_exists($certificado['caminho_arquivo'])) {
                    unlink($certificado['caminho_arquivo']);
                }

                // Remove do banco
                $this->db->delete(
                    'certificados',
                    'id = :id AND usuario_id = :usuario_id',
                    ['id' => $certificadoId, 'usuario_id' => $usuarioId]
                );

                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Ativa/desativa certificado
     */
    public function toggleCertificado($certificadoId, $usuarioId, $ativo)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            $this->db->update(
                'certificados',
                ['ativo' => $ativo ? 1 : 0],
                'id = :id AND usuario_id = :usuario_id',
                ['id' => $certificadoId, 'usuario_id' => $usuarioId]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtém certificado ativo do usuário
     */
    public function buscarCertificadoAtivo($usuarioId)
    {
        if ($this->db === null) {
            return null;
        }

        try {
            $certificado = $this->db->selectOne(
                "SELECT * FROM certificados 
                 WHERE usuario_id = :usuario_id AND ativo = 1 
                 ORDER BY criado_em DESC 
                 LIMIT 1",
                ['usuario_id' => $usuarioId]
            );
            return $certificado;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Atualiza dados do usuário
     */
    public function atualizarDados($id, $nome, $cnpj, $ie)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            $this->db->update(
                'usuarios',
                [
                    'nome' => $nome,
                    'cnpj' => $cnpj,
                    'ie' => $ie
                ],
                'id = :id',
                ['id' => $id]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Altera senha do usuário
     */
    public function alterarSenha($id, $senhaAtual, $novaSenha)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            // Verifica senha atual
            $usuario = $this->db->selectOne(
                "SELECT senha FROM usuarios WHERE id = :id",
                ['id' => $id]
            );

            if (!$usuario || !password_verify($senhaAtual, $usuario['senha'])) {
                return false;
            }

            // Atualiza senha
            $novaSenhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);
            $this->db->update(
                'usuarios',
                ['senha' => $novaSenhaHash],
                'id = :id',
                ['id' => $id]
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Conta usuários por papel
     */
    public function contarPorPapel()
    {
        if ($this->db === null) {
            return [];
        }

        try {
            $resultados = $this->db->select(
                "SELECT papel, COUNT(*) as total FROM usuarios GROUP BY papel"
            );

            $contagem = [];
            foreach ($resultados as $resultado) {
                $contagem[$resultado['papel']] = $resultado['total'];
            }

            return $contagem;
        } catch (Exception $e) {
            return [];
        }
    }
}
