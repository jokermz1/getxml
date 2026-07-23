<?php

namespace App\Core;

class Database
{
    private static $instance = null;
    private $connection;
    private $config;

    private function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Obtém instância única do banco de dados
     */
    public static function getInstance(array $config = null)
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new \Exception('Configuração do banco de dados não fornecida na primeira chamada');
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Conecta ao banco de dados
     */
    private function connect()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $this->config['host'] ?? 'localhost',
            $this->config['database'] ?? 'getxml'
        );

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->connection = new \PDO(
                $dsn,
                $this->config['username'] ?? 'root',
                $this->config['password'] ?? '',
                $options
            );
        } catch (\PDOException $e) {
            throw new \Exception('Erro de conexão com o banco de dados: ' . $e->getMessage());
        }
    }

    /**
     * Obtém a conexão PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Executa uma query SELECT
     */
    public function select($query, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception('Erro na query SELECT: ' . $e->getMessage());
        }
    }

    /**
     * Executa uma query e retorna uma única linha
     */
    public function selectOne($query, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            throw new \Exception('Erro na query SELECT: ' . $e->getMessage());
        }
    }

    /**
     * Executa uma query INSERT
     */
    public function insert($table, $data)
    {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->connection->prepare($query);
            $stmt->execute($data);
            
            return $this->connection->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception('Erro na query INSERT: ' . $e->getMessage());
        }
    }

    /**
     * Executa uma query UPDATE
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        try {
            $set = [];
            foreach (array_keys($data) as $column) {
                $set[] = "{$column} = :{$column}";
            }
            $setClause = implode(', ', $set);
            
            $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array_merge($data, $whereParams));
            
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception('Erro na query UPDATE: ' . $e->getMessage());
        }
    }

    /**
     * Executa uma query DELETE
     */
    public function delete($table, $where, $params = [])
    {
        try {
            $query = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception('Erro na query DELETE: ' . $e->getMessage());
        }
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * Desfaz uma transação
     */
    public function rollback()
    {
        return $this->connection->rollBack();
    }

    /**
     * Testa a conexão
     */
    public function testConnection()
    {
        try {
            $this->connection->query("SELECT 1");
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Fecha a conexão
     */
    public function close()
    {
        $this->connection = null;
        self::$instance = null;
    }

    /**
     * Impede clonagem
     */
    private function __clone()
    {
    }

    /**
     * Impede unserialize
     */
    public function __wakeup()
    {
        throw new \Exception("Não pode unserialize singleton");
    }
}
