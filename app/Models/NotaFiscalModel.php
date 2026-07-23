<?php

namespace App\Models;

use App\Core\Database;

class NotaFiscalModel
{
    private $db;
    private $config;

    public function __construct($storagePath, array $config = null)
    {
        $this->config = $config;
        
        try {
            if ($config) {
                $this->db = Database::getInstance($config['database']);
            }
        } catch (Exception $e) {
            $this->db = null;
        }
    }

    /**
     * Adiciona uma nota fiscal
     */
    public function adicionarNota($nota)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            // Verifica se nota já existe
            $existente = $this->db->selectOne(
                "SELECT id FROM notas_fiscais WHERE chave = :chave",
                ['chave' => $nota['chave']]
            );

            if ($existente) {
                return false;
            }

            // Prepara dados para inserção
            $dados = [
                'usuario_id' => $nota['usuario_id'] ?? null,
                'chave' => $nota['chave'],
                'numero' => $nota['numero'] ?? null,
                'serie' => $nota['serie'] ?? null,
                'data_emissao' => $nota['data_emissao'] ?? null,
                'valor' => $nota['valor'] ?? 0,
                'cnpj_emitente' => $nota['cnpj_emitente'] ?? null,
                'nome_emitente' => $nota['nome_emitente'] ?? null,
                'caminho_xml' => $nota['arquivo_xml'] ?? null,
                'data_captura' => date('Y-m-d H:i:s')
            ];

            $notaId = $this->db->insert('notas_fiscais', $dados);
            return $notaId > 0;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Busca nota por chave
     */
    public function buscarNotaPorChave($chave, $usuarioId = null)
    {
        if ($this->db === null) {
            return null;
        }

        try {
            $query = "SELECT * FROM notas_fiscais WHERE chave = :chave";
            $params = ['chave' => $chave];

            if ($usuarioId) {
                $query .= " AND usuario_id = :usuario_id";
                $params['usuario_id'] = $usuarioId;
            }

            $nota = $this->db->selectOne($query, $params);
            return $nota;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Lista notas do usuário
     */
    public function listarNotas($usuarioId, $filtros = [])
    {
        if ($this->db === null) {
            return [];
        }

        try {
            $query = "SELECT * FROM notas_fiscais WHERE usuario_id = :usuario_id";
            $params = ['usuario_id' => $usuarioId];

            // Aplicar filtros
            if (!empty($filtros['data_inicio'])) {
                $query .= " AND data_emissao >= :data_inicio";
                $params['data_inicio'] = $filtros['data_inicio'];
            }

            if (!empty($filtros['data_fim'])) {
                $query .= " AND data_emissao <= :data_fim";
                $params['data_fim'] = $filtros['data_fim'];
            }

            if (!empty($filtros['cnpj'])) {
                $query .= " AND cnpj_emitente LIKE :cnpj";
                $params['cnpj'] = '%' . $filtros['cnpj'] . '%';
            }

            $query .= " ORDER BY data_emissao DESC";

            $notas = $this->db->select($query, $params);
            return $notas;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Remove uma nota
     */
    public function removerNota($chave, $usuarioId)
    {
        if ($this->db === null) {
            return false;
        }

        try {
            // Primeiro obtém a nota para remover o arquivo
            $nota = $this->buscarNotaPorChave($chave, $usuarioId);

            if ($nota) {
                // Remove arquivo XML se existir
                if (!empty($nota['caminho_xml']) && file_exists($nota['caminho_xml'])) {
                    unlink($nota['caminho_xml']);
                }

                // Remove do banco
                $this->db->delete(
                    'notas_fiscais',
                    'chave = :chave AND usuario_id = :usuario_id',
                    ['chave' => $chave, 'usuario_id' => $usuarioId]
                );

                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtém estatísticas das notas do usuário
     */
    public function getEstatisticas($usuarioId)
    {
        if ($this->db === null) {
            return [
                'total_notas' => 0,
                'valor_total' => 0,
                'valor_medio' => 0,
            ];
        }

        try {
            $resultado = $this->db->selectOne(
                "SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(valor), 0) as valor_total,
                    COALESCE(AVG(valor), 0) as valor_medio
                 FROM notas_fiscais 
                 WHERE usuario_id = :usuario_id",
                ['usuario_id' => $usuarioId]
            );

            return [
                'total_notas' => $resultado['total'],
                'valor_total' => $resultado['valor_total'],
                'valor_medio' => $resultado['valor_medio'],
            ];

        } catch (Exception $e) {
            return [
                'total_notas' => 0,
                'valor_total' => 0,
                'valor_medio' => 0,
            ];
        }
    }
}
