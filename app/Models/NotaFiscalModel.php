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
        
        // Temporariamente usa JSON se não tiver banco
        $this->dbFile = $storagePath . '/notas_fiscais.json';
        $this->carregarNotas();
    }

    /**
     * Carrega notas do arquivo JSON (modo sem banco)
     */
    private function carregarNotas()
    {
        if (file_exists($this->dbFile)) {
            $conteudo = file_get_contents($this->dbFile);
            $this->notas = json_decode($conteudo, true) ?? [];
        } else {
            $this->notas = [];
        }
    }

    /**
     * Salva notas no arquivo JSON (modo sem banco)
     */
    private function salvarNotas()
    {
        file_put_contents($this->dbFile, json_encode($this->notas, JSON_PRETTY_PRINT));
    }

    /**
     * Adiciona uma nota fiscal
     */
    public function adicionarNota($nota)
    {
        // Tenta usar MySQL se disponível, senão usa JSON
        if ($this->config && $this->db) {
            return $this->adicionarNotaMySQL($nota);
        } else {
            return $this->adicionarNotaJSON($nota);
        }
    }

    /**
     * Adiciona nota usando MySQL
     */
    private function adicionarNotaMySQL($nota)
    {
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
     * Adiciona nota usando JSON (fallback)
     */
    private function adicionarNotaJSON($nota)
    {
        $chave = $nota['chave'];
        
        // Verifica se nota já existe
        if ($this->buscarNotaPorChave($chave)) {
            return false;
        }
        
        $nota['data_captura'] = date('Y-m-d H:i:s');
        $nota['arquivo_xml'] = $nota['arquivo_xml'] ?? null;
        
        $this->notas[$chave] = $nota;
        $this->salvarNotas();
        
        return true;
    }

    /**
     * Busca nota por chave
     */
    public function buscarNotaPorChave($chave, $usuarioId = null)
    {
        // Tenta usar MySQL se disponível, senão usa JSON
        if ($this->config && $this->db) {
            return $this->buscarNotaPorChaveMySQL($chave, $usuarioId);
        } else {
            return $this->buscarNotaPorChaveJSON($chave);
        }
    }

    /**
     * Busca nota por chave usando MySQL
     */
    private function buscarNotaPorChaveMySQL($chave, $usuarioId = null)
    {
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
     * Busca nota por chave usando JSON (fallback)
     */
    private function buscarNotaPorChaveJSON($chave)
    {
        return $this->notas[$chave] ?? null;
    }

    /**
     * Lista notas do usuário
     */
    public function listarNotas($usuarioId, $filtros = [])
    {
        // Tenta usar MySQL se disponível, senão usa JSON
        if ($this->config && $this->db) {
            return $this->listarNotasMySQL($usuarioId, $filtros);
        } else {
            return $this->listarNotasJSON($filtros);
        }
    }

    /**
     * Lista notas usando MySQL
     */
    private function listarNotasMySQL($usuarioId, $filtros = [])
    {
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
     * Lista notas usando JSON (fallback)
     */
    private function listarNotasJSON($filtros = [])
    {
        $notas = $this->notas;
        
        // Aplicar filtros
        if (!empty($filtros['data_inicio'])) {
            $notas = array_filter($notas, function($nota) use ($filtros) {
                return strtotime($nota['data_emissao']) >= strtotime($filtros['data_inicio']);
            });
        }
        
        if (!empty($filtros['data_fim'])) {
            $notas = array_filter($notas, function($nota) use ($filtros) {
                return strtotime($nota['data_emissao']) <= strtotime($filtros['data_fim']);
            });
        }
        
        if (!empty($filtros['cnpj'])) {
            $notas = array_filter($notas, function($nota) use ($filtros) {
                return strpos($nota['cnpj_emitente'], $filtros['cnpj']) !== false;
            });
        }
        
        // Ordenar por data de emissão (decrescente)
        usort($notas, function($a, $b) {
            return strtotime($b['data_emissao']) - strtotime($a['data_emissao']);
        });
        
        return array_values($notas);
    }

    /**
     * Remove uma nota
     */
    public function removerNota($chave, $usuarioId)
    {
        // Tenta usar MySQL se disponível, senão usa JSON
        if ($this->config && $this->db) {
            return $this->removerNotaMySQL($chave, $usuarioId);
        } else {
            return $this->removerNotaJSON($chave);
        }
    }

    /**
     * Remove nota usando MySQL
     */
    private function removerNotaMySQL($chave, $usuarioId)
    {
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
     * Remove nota usando JSON (fallback)
     */
    private function removerNotaJSON($chave)
    {
        if (isset($this->notas[$chave])) {
            // Remove arquivo XML se existir
            if (!empty($this->notas[$chave]['arquivo_xml']) && file_exists($this->notas[$chave]['arquivo_xml'])) {
                unlink($this->notas[$chave]['arquivo_xml']);
            }
            
            unset($this->notas[$chave]);
            $this->salvarNotas();
            return true;
        }
        return false;
    }

    /**
     * Obtém estatísticas das notas do usuário
     */
    public function getEstatisticas($usuarioId)
    {
        // Tenta usar MySQL se disponível, senão usa JSON
        if ($this->config && $this->db) {
            return $this->getEstatisticasMySQL($usuarioId);
        } else {
            return $this->getEstatisticasJSON();
        }
    }

    /**
     * Obtém estatísticas usando MySQL
     */
    private function getEstatisticasMySQL($usuarioId)
    {
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

    /**
     * Obtém estatísticas usando JSON (fallback)
     */
    private function getEstatisticasJSON()
    {
        $total = count($this->notas);
        $valorTotal = array_sum(array_column($this->notas, 'valor'));
        
        return [
            'total_notas' => $total,
            'valor_total' => $valorTotal,
            'valor_medio' => $total > 0 ? $valorTotal / $total : 0,
        ];
    }
}
