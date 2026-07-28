<?php

namespace App\Services;

use App\Models\ExternalDatabaseRuntime;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;

final class PpaQueryFileCache
{
    private const SCHEMA_VERSION = 1;

    public function __construct(
        private ?string $directory = null
    ) {
        $this->directory = rtrim(
            $directory ?? dirname(__DIR__, 2) . '/storage/cache/ppa/queries',
            DIRECTORY_SEPARATOR
        );

        if ($this->directory === '') {
            throw new RuntimeException('Informe um diretorio valido para o cache do PPA.');
        }
    }

    public function read(object $source, object $query, int $limit): ?array
    {
        $identity = $this->identity($source, $query, $limit);
        $path = $this->path($identity);

        if (!is_file($path)) {
            return null;
        }

        $lock = @fopen($path . '.lock', 'c');

        if ($lock === false) {
            return null;
        }

        try {
            if (!flock($lock, LOCK_SH)) {
                return null;
            }

            $contents = @file_get_contents($path);
            flock($lock, LOCK_UN);
        } finally {
            fclose($lock);
        }

        if (!is_string($contents) || $contents === '') {
            return null;
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return $this->isValidPayload($payload, $identity) ? $payload : null;
    }

    public function write(
        object $source,
        object $query,
        int $limit,
        array $rows,
        ?DateTimeInterface $generatedAt = null
    ): array {
        $this->assertRows($rows);
        $identity = $this->identity($source, $query, $limit);
        $payload = array_merge($identity, [
            'generated_at' => ($generatedAt ?? new DateTimeImmutable())->format(DATE_ATOM),
            'row_count' => count($rows),
            'rows' => $rows,
        ]);
        $this->ensureDirectory();
        $path = $this->path($identity);
        $lock = @fopen($path . '.lock', 'c');

        if ($lock === false) {
            throw new RuntimeException('Nao foi possivel criar a trava do cache do PPA.');
        }

        $temporaryPath = null;

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('Nao foi possivel bloquear o cache do PPA para escrita.');
            }

            $temporaryPath = tempnam($this->directory, '.ppa-cache-');

            if ($temporaryPath === false) {
                throw new RuntimeException('Nao foi possivel criar o arquivo temporario do cache do PPA.');
            }

            $json = json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
            );

            if (file_put_contents($temporaryPath, $json) === false) {
                throw new RuntimeException('Nao foi possivel gravar o cache temporario do PPA.');
            }

            @chmod($temporaryPath, 0660);

            if (!rename($temporaryPath, $path)) {
                throw new RuntimeException('Nao foi possivel substituir o cache do PPA.');
            }

            $temporaryPath = null;
            flock($lock, LOCK_UN);
        } catch (\Throwable $e) {
            if (is_string($temporaryPath) && is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }

            throw $e instanceof RuntimeException
                ? $e
                : new RuntimeException('Nao foi possivel gravar o cache do PPA.', 0, $e);
        } finally {
            fclose($lock);
        }

        return $payload;
    }

    private function identity(object $source, object $query, int $limit): array
    {
        $sourceId = (int) ($source->id ?? 0);
        $queryId = (int) ($query->id ?? 0);
        $safeLimit = max(1, $limit);
        $queryHash = hash(
            'sha256',
            ExternalDatabaseRuntime::normalizeSql((string) ($query->sql_query ?? ''))
        );

        if ($sourceId <= 0 || $queryId <= 0) {
            throw new RuntimeException('Fonte e consulta validas sao obrigatorias para o cache do PPA.');
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'source_id' => $sourceId,
            'query_id' => $queryId,
            'query_hash' => $queryHash,
            'limit' => $safeLimit,
        ];
    }

    private function path(array $identity): string
    {
        $key = hash('sha256', implode(':', [
            $identity['schema_version'],
            $identity['source_id'],
            $identity['query_id'],
            $identity['query_hash'],
            $identity['limit'],
        ]));

        return $this->directory . DIRECTORY_SEPARATOR . $key . '.json';
    }

    private function ensureDirectory(): void
    {
        if (is_dir($this->directory)) {
            if (!is_writable($this->directory)) {
                throw new RuntimeException('O diretorio de cache do PPA nao possui permissao de escrita.');
            }

            return;
        }

        if (!@mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Nao foi possivel criar o diretorio de cache do PPA.');
        }
    }

    private function assertRows(array $rows): void
    {
        if (!array_is_list($rows)) {
            throw new RuntimeException('As linhas do cache do PPA devem formar uma lista.');
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new RuntimeException('Cada linha do cache do PPA deve ser um array.');
            }
        }
    }

    private function isValidPayload($payload, array $identity): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        foreach ($identity as $field => $expected) {
            if (($payload[$field] ?? null) !== $expected) {
                return false;
            }
        }

        if (!is_string($payload['generated_at'] ?? null)
            || DateTimeImmutable::createFromFormat(DATE_ATOM, $payload['generated_at']) === false
            || !is_array($payload['rows'] ?? null)
            || !array_is_list($payload['rows'])
            || !is_int($payload['row_count'] ?? null)
            || $payload['row_count'] !== count($payload['rows'])) {
            return false;
        }

        foreach ($payload['rows'] as $row) {
            if (!is_array($row)) {
                return false;
            }
        }

        return true;
    }
}
