# Observatorio SASC

## Configuração Inicial

### Pré-requisitos
- PHP 8.0+
- MySQL 5.7+
- Composer

### Instalação

1. Clone ou acesse o projeto:
   ```bash
   cd observatoriosasc
   ```

2. Instale as dependências:
   ```bash
   composer install
   ```

## Configuração do Banco de Dados

### 1. Criar arquivo .env

Copie o arquivo `.env.example` para `.env`:
```bash
cp .env.example .env
```

### 2. Configurar variáveis de ambiente

Edite o arquivo `.env` com suas credenciais do banco de dados:
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=observatoriosasc
DB_USER=seu_usuario
DB_PASS=sua_senha
DB_CHARSET=utf8mb4
```

### 3. Criar o banco de dados

Crie o banco de dados MySQL:
```sql
CREATE DATABASE observatoriosasc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Como Usar a Conexão com Banco de Dados

### Classe DataConect

A classe `App\Core\DataConect` implementa o padrão **Singleton** para gerenciar a conexão com o banco de dados. A conexão é aberta apenas uma vez e reutilizada em toda a aplicação.

### Exemplo de uso em um controlador:

```php
use App\Core\DataConect;

// Obter a instância da conexão
$connection = DataConect::getInstance();

// Preparar e executar uma consulta SELECT
$stmt = $connection->prepare("SELECT * FROM users WHERE active = ?");
$stmt->execute([1]);
$users = $stmt->fetchAll();

// Inserir dados
$stmt = $connection->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
$stmt->execute(["João", "joao@example.com"]);

// Atualizar dados
$stmt = $connection->prepare("UPDATE users SET name = ? WHERE id = ?");
$stmt->execute(["João Silva", 1]);

// Deletar dados
$stmt = $connection->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([1]);
```

## Query Builder

O `QueryBuilder` facilita a construção de queries SQL de forma fluente, permitindo encadeamento de métodos sem a necessidade de escrever SQL manualmente. Ele inclui um método `reset()` para limpar o estado e evitar interferências entre chamadas.

### Exemplo de uso básico:

```php
use App\Core\QueryBuilder;

// Selecionar todos os registros de uma tabela
$qb = new QueryBuilder();
$results = $qb->table('users')->get();

// Selecionar colunas específicas
$results = $qb->table('users')->select('id', 'name', 'email')->get();

// Adicionar condições WHERE
$results = $qb->table('users')->where('active', 1)->get();

// Múltiplas condições
$results = $qb->table('users')
    ->where('active', 1)
    ->where('role', 'admin')
    ->get();

// Condições OR
$results = $qb->table('users')
    ->where('active', 1)
    ->orWhere('role', 'admin')
    ->get();

// JOINs
$results = $qb->table('users')
    ->select('users.name', 'profiles.bio')
    ->join('profiles', 'users.id', 'profiles.user_id')
    ->get();

// LEFT JOIN
$results = $qb->table('users')
    ->leftJoin('profiles', 'users.id', 'profiles.user_id')
    ->get();

// ORDER BY
$results = $qb->table('users')
    ->orderBy('name', 'ASC')
    ->orderBy('created_at', 'DESC')
    ->get();

// LIMIT e OFFSET
$results = $qb->table('users')
    ->limit(10)
    ->offset(20)
    ->get();

// Obter apenas o primeiro resultado com fetch()
$user = $qb->table('users')->where('id', 1)->fetch();

// Obter apenas o primeiro resultado usando first()
$user = $qb->table('users')->where('id', 1)->first();

// Obter resultados como objetos anônimos
$results = $qb->table('users')->asObject()->get();

// Obter resultados como instâncias de classe
$results = $qb->table('users')->asClass(\App\Models\User::class)->get();

// Resetar o QueryBuilder para reutilização
$qb->reset()->table('posts')->where('published', 1)->get();
```

### Vantagens do Query Builder:
- **Segurança**: Usa prepared statements automaticamente.
- **Legibilidade**: Código mais limpo e expressivo.
- **Flexibilidade**: Suporte a SELECT, WHERE, JOIN, ORDER BY, LIMIT/OFFSET.
- **Reutilização**: Método `reset()` permite limpar o estado sem criar novas instâncias.

## Estrutura de Diretórios

```
App/
├── Core/
│   ├── DataConect.php    # Configuração e conexão com banco
│   ├── Router.php        # Gerenciador de rotas
│   └── Template.php      # Gerenciador de templates Twig
├── Controllers/          # Controladores da aplicação
└── Views/                # Templates Twig

Routes/
└── web.php              # Definição de rotas

Public/
└── index.php            # Ponto de entrada da aplicação
```

## Biblioteca útil. Estudar!
https://gridstackjs.com/demo/index.html