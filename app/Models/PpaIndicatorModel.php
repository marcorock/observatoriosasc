<?php

namespace App\Models;

use App\Database\DataConect;
use App\Utils\ViewClasses;
use PDO;
use PDOException;

class PpaIndicatorModel
{
    private PDO $pdo;
    private string $table = 'ppa_indicadores';

    public function __construct()
    {
        $this->pdo = DataConect::getInstance();
        $this->ensureTable();
    }

    public function readAll(): array|string
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT id, codigo AS codigo_indicador, numero_programa, nome, objetivo, justificativa,
                        publico_alvo, ods_codigo, ods_descricao, meta_ods, unidade_medida,
                        indice_recente, indice_futuro, memoria_calculo, fonte_dados,
                        criterio_utilizado, forma_calculo, resultado_esperado,
                        tipo_alimentacao AS tipo_apuracao, periodicidade, status AS ativo,
                        created_at, updated_at
                 FROM {$this->table}
                 ORDER BY codigo ASC, id ASC"
            );

            return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar os indicadores do PPA.';
        }
    }

    public function readActiveOptions(): array
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT id, codigo AS codigo_indicador, nome
                 FROM {$this->table}
                 WHERE status = 1
                 ORDER BY codigo ASC, id ASC"
            );

            return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function readPublicCatalog(): array|string
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT pi.id, pi.codigo AS codigo_indicador, pi.numero_programa, pi.nome, pi.objetivo,
                        pi.justificativa, pi.publico_alvo, pi.ods_codigo, pi.ods_descricao,
                        pi.meta_ods, pi.unidade_medida, pi.indice_recente, pi.indice_futuro,
                        pi.memoria_calculo, pi.fonte_dados, pi.criterio_utilizado,
                        pi.forma_calculo, pi.resultado_esperado, pi.tipo_alimentacao AS tipo_apuracao,
                        pi.periodicidade, pi.status AS ativo,
                        COUNT(DISTINCT CASE WHEN pq.ativo = 1 AND eq.id IS NOT NULL THEN pq.id END) AS vinculos_ativos,
                        MAX(CASE WHEN pq.ativo = 1 AND pq.papel = 'principal' AND eq.id IS NOT NULL THEN 1 ELSE 0 END) AS possui_vinculo_principal
                 FROM {$this->table} pi
                 LEFT JOIN ppa_indicador_queries pq ON pq.indicador_id = pi.id
                 LEFT JOIN external_data_queries eq ON eq.id = pq.external_query_id AND eq.ativo = 1
                 WHERE pi.status = 1
                 GROUP BY pi.id
                 ORDER BY COALESCE(pi.numero_programa, 999999) ASC, pi.nome ASC, pi.id ASC"
            );

            $rows = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            return array_map(fn ($row) => $this->decoratePublicIndicator($row), $rows);
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar os indicadores publicos do PPA.';
        }
    }

    public function readPublicBySlug(string $slug): object|string
    {
        $indicators = $this->readPublicCatalog();

        if (is_string($indicators)) {
            return $indicators;
        }

        $slug = trim($slug);

        foreach ($indicators as $indicator) {
            if (($indicator->slug ?? '') === $slug || ($indicator->codigo_slug ?? '') === $slug) {
                return $indicator;
            }
        }

        return 'Indicador do PPA nao encontrado.';
    }

    public function readById(int $id): object|string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, codigo AS codigo_indicador, numero_programa, nome, objetivo, justificativa,
                        publico_alvo, ods_codigo, ods_descricao, meta_ods, unidade_medida,
                        indice_recente, indice_futuro, memoria_calculo, fonte_dados,
                        criterio_utilizado, forma_calculo, resultado_esperado,
                        tipo_alimentacao AS tipo_apuracao, periodicidade, status AS ativo,
                        created_at, updated_at
                 FROM {$this->table}
                 WHERE id = :id
                 LIMIT 1"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ?: 'Indicador do PPA nao encontrado.';
        } catch (PDOException $e) {
            return 'Nao foi possivel carregar o indicador do PPA.';
        }
    }

    public function create(array $data): bool|string
    {
        $payload = $this->preparePayload($data);

        if (is_string($payload)) {
            return $payload;
        }

        if ($this->codeExists($payload['codigo'])) {
            return 'Ja existe um indicador cadastrado com este codigo.';
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                    (codigo, numero_programa, nome, objetivo, justificativa, publico_alvo, ods_codigo,
                     ods_descricao, meta_ods, unidade_medida, indice_recente, indice_futuro,
                     memoria_calculo, fonte_dados, criterio_utilizado, forma_calculo,
                     resultado_esperado, tipo_alimentacao, periodicidade, status)
                 VALUES
                    (:codigo, :numero_programa, :nome, :objetivo, :justificativa, :publico_alvo, :ods_codigo,
                     :ods_descricao, :meta_ods, :unidade_medida, :indice_recente, :indice_futuro,
                     :memoria_calculo, :fonte_dados, :criterio_utilizado, :forma_calculo,
                     :resultado_esperado, :tipo_alimentacao, :periodicidade, :status)"
            );

            $this->bindPayload($stmt, $payload);

            return $stmt->execute();
        } catch (PDOException $e) {
            return 'Nao foi possivel cadastrar o indicador do PPA.';
        }
    }

    public function updateById(int $id, array $data): bool|string
    {
        $payload = $this->preparePayload($data);

        if (is_string($payload)) {
            return $payload;
        }

        if ($this->codeExists($payload['codigo'], $id)) {
            return 'Ja existe um indicador cadastrado com este codigo.';
        }

        try {
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table}
                 SET codigo = :codigo,
                     numero_programa = :numero_programa,
                     nome = :nome,
                     objetivo = :objetivo,
                     justificativa = :justificativa,
                     publico_alvo = :publico_alvo,
                     ods_codigo = :ods_codigo,
                     ods_descricao = :ods_descricao,
                     meta_ods = :meta_ods,
                     unidade_medida = :unidade_medida,
                     indice_recente = :indice_recente,
                     indice_futuro = :indice_futuro,
                     memoria_calculo = :memoria_calculo,
                     fonte_dados = :fonte_dados,
                     criterio_utilizado = :criterio_utilizado,
                     forma_calculo = :forma_calculo,
                     resultado_esperado = :resultado_esperado,
                     tipo_alimentacao = :tipo_alimentacao,
                     periodicidade = :periodicidade,
                     status = :status
                 WHERE id = :id"
            );

            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindPayload($stmt, $payload);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return 'Nao foi possivel atualizar o indicador do PPA.';
        }
    }

    public function deleteById(int $id): bool|string
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return 'Nao foi possivel excluir o indicador do PPA.';
        }
    }

    private function codeExists(string $codigo, ?int $ignoreId = null): bool
    {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE codigo = :codigo";

            if ($ignoreId !== null) {
                $sql .= " AND id <> :ignore_id";
            }

            $sql .= " LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':codigo', $codigo, PDO::PARAM_STR);

            if ($ignoreId !== null) {
                $stmt->bindValue(':ignore_id', $ignoreId, PDO::PARAM_INT);
            }

            $stmt->execute();

            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return true;
        }
    }

    private function decoratePublicIndicator(object $row): object
    {
        $row->slug = ViewClasses::slug((string) $row->nome);
        $row->codigo_slug = ViewClasses::slug((string) $row->codigo_indicador);
        $row->possui_vinculo_principal = (int) ($row->possui_vinculo_principal ?? 0) === 1;
        $row->vinculos_ativos = (int) ($row->vinculos_ativos ?? 0);

        return $row;
    }

    private function preparePayload(array $data): array|string
    {
        $codigo = trim((string) ($data['codigo_indicador'] ?? ''));
        $numeroPrograma = $this->normalizeNullableInt($data['numero_programa'] ?? null);
        $nome = trim((string) ($data['nome'] ?? ''));
        $objetivo = $this->normalizeNullableText($data['objetivo'] ?? null);
        $justificativa = $this->normalizeNullableText($data['justificativa'] ?? null);
        $publicoAlvo = $this->normalizeNullableText($data['publico_alvo'] ?? null);
        $odsCodigo = $this->normalizeNullableText($data['ods_codigo'] ?? null);
        $odsDescricao = $this->normalizeNullableText($data['ods_descricao'] ?? null);
        $metaOds = $this->normalizeNullableText($data['meta_ods'] ?? null);
        $unidadeMedida = $this->normalizeNullableText($data['unidade_medida'] ?? null);
        $indiceRecente = $this->normalizeDecimal($data['indice_recente'] ?? null);
        $indiceFuturo = $this->normalizeDecimal($data['indice_futuro'] ?? null);
        $memoriaCalculo = $this->normalizeNullableText($data['memoria_calculo'] ?? null);
        $fonteDados = $this->normalizeNullableText($data['fonte_dados'] ?? null);
        $criterioUtilizado = $this->normalizeNullableText($data['criterio_utilizado'] ?? null);
        $formaCalculo = $this->normalizeNullableText($data['forma_calculo'] ?? null);
        $resultadoEsperado = $this->normalizeNullableText($data['resultado_esperado'] ?? null);
        $tipoAlimentacao = $this->normalizeTipoApuracao($data['tipo_apuracao'] ?? 'manual');
        $periodicidade = $this->normalizePeriodicidade($data['periodicidade'] ?? 'anual');
        $status = (int) ((string) ($data['ativo'] ?? '1') === '0' ? 0 : 1);

        if ($codigo === '' || mb_strlen($codigo) > 50) {
            return 'Informe um codigo valido para o indicador.';
        }

        if ($nome === '' || mb_strlen($nome) > 255) {
            return 'Informe um nome valido para o indicador.';
        }

        if ($tipoAlimentacao === null) {
            return 'Tipo de apuracao invalido.';
        }

        if ($periodicidade === null) {
            return 'Periodicidade invalida.';
        }

        return [
            'codigo' => $codigo,
            'numero_programa' => $numeroPrograma,
            'nome' => $nome,
            'objetivo' => $objetivo,
            'justificativa' => $justificativa,
            'publico_alvo' => $publicoAlvo,
            'ods_codigo' => $odsCodigo,
            'ods_descricao' => $odsDescricao,
            'meta_ods' => $metaOds,
            'unidade_medida' => $unidadeMedida,
            'indice_recente' => $indiceRecente,
            'indice_futuro' => $indiceFuturo,
            'memoria_calculo' => $memoriaCalculo,
            'fonte_dados' => $fonteDados,
            'criterio_utilizado' => $criterioUtilizado,
            'forma_calculo' => $formaCalculo,
            'resultado_esperado' => $resultadoEsperado,
            'tipo_alimentacao' => $tipoAlimentacao,
            'periodicidade' => $periodicidade,
            'status' => $status,
        ];
    }

    private function normalizeTipoApuracao(mixed $value): ?string
    {
        $value = trim((string) $value);

        $map = [
            'manual' => 'manual',
            'query_externa' => 'automatico',
            'mista' => 'hibrido',
            'automatico' => 'automatico',
            'hibrido' => 'hibrido',
        ];

        return $map[$value] ?? null;
    }

    private function normalizePeriodicidade(mixed $value): ?string
    {
        $value = trim((string) $value);
        $allowed = ['mensal', 'bimestral', 'trimestral', 'quadrimestral', 'semestral', 'anual'];

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return ctype_digit($value) ? (int) $value : null;
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function normalizeDecimal(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? number_format((float) $value, 4, '.', '') : null;
    }

    private function bindPayload(\PDOStatement $stmt, array $payload): void
    {
        $stmt->bindValue(':codigo', $payload['codigo'], PDO::PARAM_STR);
        $stmt->bindValue(':numero_programa', $payload['numero_programa'], $payload['numero_programa'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':nome', $payload['nome'], PDO::PARAM_STR);
        $stmt->bindValue(':objetivo', $payload['objetivo'], $payload['objetivo'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':justificativa', $payload['justificativa'], $payload['justificativa'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':publico_alvo', $payload['publico_alvo'], $payload['publico_alvo'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':ods_codigo', $payload['ods_codigo'], $payload['ods_codigo'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':ods_descricao', $payload['ods_descricao'], $payload['ods_descricao'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':meta_ods', $payload['meta_ods'], $payload['meta_ods'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':unidade_medida', $payload['unidade_medida'], $payload['unidade_medida'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':indice_recente', $payload['indice_recente'], $payload['indice_recente'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':indice_futuro', $payload['indice_futuro'], $payload['indice_futuro'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':memoria_calculo', $payload['memoria_calculo'], $payload['memoria_calculo'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':fonte_dados', $payload['fonte_dados'], $payload['fonte_dados'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':criterio_utilizado', $payload['criterio_utilizado'], $payload['criterio_utilizado'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':forma_calculo', $payload['forma_calculo'], $payload['forma_calculo'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':resultado_esperado', $payload['resultado_esperado'], $payload['resultado_esperado'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':tipo_alimentacao', $payload['tipo_alimentacao'], PDO::PARAM_STR);
        $stmt->bindValue(':periodicidade', $payload['periodicidade'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $payload['status'], PDO::PARAM_INT);
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(50) NOT NULL,
            numero_programa INT NULL,
            nome VARCHAR(255) NOT NULL,
            objetivo TEXT NULL,
            justificativa TEXT NULL,
            publico_alvo VARCHAR(255) NULL,
            ods_codigo VARCHAR(50) NULL,
            ods_descricao VARCHAR(255) NULL,
            meta_ods TEXT NULL,
            unidade_medida VARCHAR(100) NULL,
            indice_recente DECIMAL(15,4) NULL,
            indice_futuro DECIMAL(15,4) NULL,
            memoria_calculo TEXT NULL,
            fonte_dados TEXT NULL,
            criterio_utilizado TEXT NULL,
            forma_calculo TEXT NULL,
            resultado_esperado TEXT NULL,
            tipo_alimentacao ENUM('automatico','manual','hibrido') NOT NULL DEFAULT 'manual',
            periodicidade ENUM('mensal','bimestral','trimestral','quadrimestral','semestral','anual') NOT NULL DEFAULT 'anual',
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_ppa_indicadores_codigo (codigo),
            KEY idx_ppa_indicadores_programa (numero_programa),
            KEY idx_ppa_indicadores_status (status),
            KEY idx_ppa_indicadores_tipo_alimentacao (tipo_alimentacao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            return;
        }
    }
}
