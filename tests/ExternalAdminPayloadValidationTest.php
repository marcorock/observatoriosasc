<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/ExternalDbCrypto.php';

use App\Models\ExternalDataSourceModel;
use App\Models\ExternalQueryModel;

$failures = [];
$assertSame = static function ($expected, $actual, string $label) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = sprintf(
            "%s\nExpected: %s\nActual:   %s",
            $label,
            var_export($expected, true),
            var_export($actual, true)
        );
    }
};
$assertTrue = static function (bool $actual, string $label) use (&$failures): void {
    if (!$actual) {
        $failures[] = $label . "\nExpected true, got false.";
    }
};

$sourceModel = (new ReflectionClass(ExternalDataSourceModel::class))->newInstanceWithoutConstructor();
$sourcePayload = new ReflectionMethod(ExternalDataSourceModel::class, 'preparePayload');
$sourcePayload->setAccessible(true);
$validSource = [
    'nome' => 'Fonte de Teste',
    'host' => 'database.internal',
    'porta' => 3306,
    'database_name' => 'dados_sociais',
    'username' => 'leitura',
    'password' => 'senha-de-teste',
    'charset' => 'utf8mb4',
    'descricao' => 'Fonte usada somente no teste.',
    'ativo' => '1',
];

$invalidSourceCases = [
    ['nome', '', 'Informe um nome valido para a conexao externa.'],
    ['host', '', 'Informe um host valido para a conexao externa.'],
    ['database_name', '', 'Informe o nome do banco externo.'],
    ['username', '', 'Informe o usuario de acesso ao banco externo.'],
    ['porta', 0, 'Informe uma porta valida para a conexao externa.'],
    ['charset', '', 'Informe um charset valido para a conexao externa.'],
    ['password', '', 'Informe a senha da conexao externa.'],
];

foreach ($invalidSourceCases as [$field, $value, $expected]) {
    $data = $validSource;
    $data[$field] = $value;
    $assertSame(
        $expected,
        $sourcePayload->invoke($sourceModel, $data, true, ''),
        'rejects an invalid external source field: ' . $field
    );
}

$_ENV['EXTERNAL_DB_CRYPT_KEY'] = 'test-key-that-is-never-used-outside-tests';
$preparedSource = $sourcePayload->invoke($sourceModel, $validSource, true, '');

$assertTrue(is_array($preparedSource), 'accepts a complete external source');
$assertTrue(
    ($preparedSource['password_encrypted'] ?? '') !== $validSource['password'],
    'does not keep the external password in plain text'
);
$assertSame(
    $validSource['password'],
    externalDbDecrypt((string) ($preparedSource['password_encrypted'] ?? '')),
    'encrypts the password with the configured key'
);
$assertSame(1, $preparedSource['ativo'] ?? null, 'normalizes the active source flag');

$existingPassword = (string) $preparedSource['password_encrypted'];
$editSource = $validSource;
$editSource['password'] = '';
$preparedEdit = $sourcePayload->invoke($sourceModel, $editSource, false, $existingPassword);
$assertSame(
    $existingPassword,
    $preparedEdit['password_encrypted'] ?? null,
    'keeps the existing password when edit leaves it empty'
);

$queryModel = (new ReflectionClass(ExternalQueryModel::class))->newInstanceWithoutConstructor();
$queryPayload = new ReflectionMethod(ExternalQueryModel::class, 'preparePayload');
$queryPayload->setAccessible(true);
$validQuery = [
    'source_id' => 3,
    'nome' => 'Resumo de famílias',
    'descricao' => 'Consulta agregada para teste.',
    'sql_query' => "SELECT cras, COUNT(*) AS total\nFROM familias\nGROUP BY cras;",
    'ativo' => '1',
];

$invalidQueryCases = [
    ['source_id', 0, 'Selecione uma conexao externa valida.'],
    ['nome', '', 'Informe um nome valido para a consulta.'],
    ['sql_query', 'DELETE FROM familias', 'Somente consultas iniciadas com SELECT sao permitidas neste modulo.'],
];

foreach ($invalidQueryCases as [$field, $value, $expected]) {
    $data = $validQuery;
    $data[$field] = $value;
    $assertSame(
        $expected,
        $queryPayload->invoke($queryModel, $data),
        'rejects an invalid external query field: ' . $field
    );
}

$preparedQuery = $queryPayload->invoke($queryModel, $validQuery);
$assertTrue(is_array($preparedQuery), 'accepts a valid SELECT query');
$assertSame(
    "SELECT cras, COUNT(*) AS total\nFROM familias\nGROUP BY cras",
    $preparedQuery['sql_query'] ?? null,
    'normalizes the valid SQL before persistence'
);
$assertSame(1, $preparedQuery['ativo'] ?? null, 'normalizes the active query flag');

unset($_ENV['EXTERNAL_DB_CRYPT_KEY']);

if ($failures !== []) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "OK (18 assertions)\n");
