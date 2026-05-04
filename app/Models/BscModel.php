<?php

namespace App\Models;
use App\Database\QueryBuilder;
use PDO;
use PDOException;

class BscModel extends QueryBuilder
{   
    protected string $table = 'bsc';
    private array $allowedSituacoes = ['Não iniciado', 'Em andamento', 'Concluído', 'cancelado'];


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retorna todos os registros da tabela BSC ordenados por ID.
     * Returns all records from the BSC table ordered by ID.
     */
    public function readAll(?string $dataInicio = null, ?string $dataFim = null): array|string
    {
        $this->reset();
        $query = $this->select("*")
                    ->from($this->table);

        $this->aplicarFiltroPeriodo($query, $dataInicio, $dataFim);

        $sql = $this->orderBy("id")
                    ->getSelect();

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Busca um único registro pelo identificador.
     * Fetches a single record by its identifier.
     */
    public function readById(int $id): object|string
    {
        $sql = $this->select("*")
                    ->from($this->table)
                    ->where("id = :id")
                    ->limit(1)
                    ->getSelect();

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result ?: 'Registro não encontrado.';
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Valida os dados e monta o INSERT usando o padrão do QueryBuilder.
     * Validates the data and builds the INSERT using the QueryBuilder pattern.
     */
    public function create(array $data): bool|string
    {
        $payload = $this->preparePayload($data);
        if (is_string($payload)) {
            return $payload;
        }

        $this->reset();
        $sql = $this->insert($this->table, $payload)
                    ->getInsert();

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Atualiza um registro existente pelo ID após validar o payload.
     * Updates an existing record by ID after validating the payload.
     */
    public function updateById(int $id, array $data): bool|string
    {
        $payload = $this->preparePayload($data);
        if (is_string($payload)) {
            return $payload;
        }

        $this->reset();
        $sql = $this->update($this->table, $payload)
                    ->where("id = {$id}")
                    ->getUpdate();

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Remove um registro da tabela com base no ID informado.
     * Removes a record from the table based on the provided ID.
     */
    public function deleteById(int $id): bool|string
    {
        $this->reset();
        $sql = $this->delete($this->table)
                    ->where("id = {$id}")
                    ->getDelete();

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function countColumn($colunm=null, ?string $dataInicio = null, ?string $dataFim = null)
    {
        // select :variável , count(id) as Total from bsc b group by :variável order by :variável
        $this->reset();
        $query = $this->select("{$colunm}, count(id) as total")
                    ->from($this->table);

        $this->aplicarFiltroPeriodo($query, $dataInicio, $dataFim);

        $sql = $this->groupBy($colunm)
                    ->orderBy($colunm)
                    ->getSelect();
        /**  */
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Normaliza e valida os campos antes de persistir no banco.
     * Normalizes and validates the fields before persisting them to the database.
     */
    private function preparePayload(array $data): array|string
    {
        $estrategia = trim((string) ($data['estrategia'] ?? ''));
        $eixo = trim((string) ($data['eixo'] ?? ''));
        $situacao = trim((string) ($data['situacao'] ?? ''));
        $statusDetalhado = $data['status_detalhado'] ?? null;
        $dataReferencia = $data['data_referencia'] ?? null;

        if ($estrategia === '' || $eixo === '' || $situacao === '') {
            return 'Os campos estrategia, eixo e situacao são obrigatórios.';
        }

        if (!in_array($situacao, $this->allowedSituacoes, true)) {
            return 'Situação inválida. Use: Não iniciado, Em andamento ou Concluído.';
        }

        if ($statusDetalhado !== null) {
            $statusDetalhado = trim((string) $statusDetalhado);
            if ($statusDetalhado === '') {
                $statusDetalhado = null;
            }
        }

        if ($dataReferencia !== null) {
            $dataReferencia = trim((string) $dataReferencia);
            if ($dataReferencia === '') {
                $dataReferencia = null;
            }
        }

        return [
            'estrategia' => $estrategia,
            'eixo' => $eixo,
            'situacao' => $situacao,
            'status_detalhado' => $statusDetalhado,
            'data_referencia' => $dataReferencia,
        ];
    }
}
