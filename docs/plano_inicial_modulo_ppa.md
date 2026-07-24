# Plano inicial do módulo PPA - Observatório SASC

> Documento histórico de concepção. Ele não representa o estado executável
> atual. Para retomar o desenvolvimento, consultar `docs/continuity.md`; para a
> arquitetura vigente, consultar `docs/architecture/ppa-module.md`.

## 1. Objetivo do módulo

Criar um módulo no sistema **Observatório SASC** para cadastrar, acompanhar, calcular e consolidar os indicadores do **PPA - Plano Plurianual**.

O módulo deve permitir:

* Cadastro dos indicadores do PPA;
* Registro da memória de cálculo;
* Definição da fonte de dados;
* Alimentação automática, manual ou híbrida;
* Armazenamento dos resultados por competência;
* Histórico de sincronizações;
* Exibição em páginas individuais por indicador;
* Comparação entre índice recente, índice futuro e resultado apurado.

---

## 2. Conceito principal

O sistema não deve depender da base original a cada consulta.

A lógica recomendada é:

```text
Fonte original
CECAD / RMA / SIGAS / POPWEB / OSC / Entrada manual
        ↓
Sincronização automática ou lançamento manual
        ↓
Base consolidada no Observatório SASC
        ↓
Página do indicador PPA
        ↓
Gráficos, filtros, metas e histórico
```

O Observatório deve guardar o **retrato consolidado** do indicador por período.

---

## 3. Tipos de alimentação dos indicadores

### 3.1 Automática

Usada quando o indicador puder ser calculado por consulta em outra base.

Exemplos:

* CadÚnico / CECAD;
* RMA CRAS;
* RMA CREAS;
* SIGAS;
* POPWEB;
* Sistema OSC.

### 3.2 Manual

Usada quando o valor do indicador precisar ser informado diretamente no painel administrativo.

Exemplos:

* Índice recente;
* Índice futuro;
* Metas pactuadas;
* Valores que ainda não possuem integração com base externa.

### 3.3 Híbrida

Usada quando parte do indicador vem de base externa e parte é informada manualmente.

Exemplo:

```text
Total de famílias acompanhadas vem do RMA.
Meta percentual ou índice futuro pode ser informado manualmente.
```

---

## 4. Indicadores identificados com memória de cálculo preenchida

### 4.1 Famílias em situação de pobreza no município

**Fonte:** CECAD / CadÚnico
**Tipo:** Automático
**Unidade:** Quantidade de famílias
**Cálculo:** Total de famílias com renda per capita entre R$ 0,00 e R$ 218,00.

---

### 4.2 Acompanhar 10% das famílias beneficiárias do Programa Bolsa Família

**Fonte:** CECAD + RMA CRAS B.2
**Tipo:** Híbrido ou automático
**Unidade:** Percentual de famílias
**Cálculo:**

```text
Total de famílias acompanhadas RMA B.2
÷
Total de famílias com PBF no CECAD
× 100
```

Também deve guardar:

```text
Meta quantitativa = Total de famílias PBF × 0,10
```

---

### 4.3 Acompanhar 10% das famílias com renda per capita até 1/2 salário mínimo pelo PAIF

**Fonte:** CECAD + RMA CRAS A.2
**Tipo:** Híbrido ou automático
**Unidade:** Percentual de famílias
**Cálculo:**

```text
Total de famílias acompanhadas RMA A.2
÷
Total de famílias com renda per capita até 1/2 salário mínimo
× 100
```

Também deve guardar:

```text
Meta quantitativa = Total de famílias até 1/2 salário mínimo × 0,10
```

---

### 4.4 Acompanhar 10% dos membros do BPC pelo PAIF

**Fonte:** CECAD + RMA CRAS B.4
**Tipo:** Híbrido ou automático
**Unidade:** Percentual de famílias / indivíduos
**Cálculo:**

```text
Total de famílias acompanhadas RMA B.4
÷
Total de famílias com membros beneficiários do BPC
× 100
```

Também deve guardar:

```text
Meta quantitativa = Total de famílias com BPC × 0,10
```

---

### 4.5 Novos casos de mulheres no CREAS

**Fonte:** RMA CREAS F.1
**Tipo:** Automático ou manual
**Unidade:** Número de inseridos
**Cálculo:**

```text
Total de mulheres adultas, de 18 a 59 anos, vítimas de violência intrafamiliar inseridas no atendimento.
```

---

### 4.6 Novas pessoas ou famílias pelo PAEFI nos CREAS

**Fonte:** RMA CREAS A.2
**Tipo:** Automático ou manual
**Unidade:** Número de atendidos
**Cálculo:**

```text
Total de novos casos, famílias ou indivíduos, inseridos no acompanhamento do PAEFI.
```

---

### 4.7 Novos adolescentes em medidas socioeducativas nos CREAS

**Fonte:** RMA CREAS J.4
**Tipo:** Automático ou manual
**Unidade:** Número de inseridos
**Cálculo:**

```text
Total de novos adolescentes em cumprimento de LA e/ou PSC inseridos em acompanhamento.
```

---

### 4.8 Novos casos de crianças e adolescentes no PAEFI / CREAS

**Fonte:** RMA CREAS Bloco C
**Tipo:** Automático ou manual
**Unidade:** Número de inseridos
**Cálculo:**

```text
C.1 + C.2 + C.3 + C.4 + C.5
```

Itens somados:

```text
C.1 - Violência intrafamiliar
C.2 - Abuso sexual
C.3 - Exploração sexual
C.4 - Negligência ou abandono
C.5 - Trabalho infantil até 15 anos
```

---

### 4.9 Número de abordagem social de criança e adolescente por equipe específica

**Fonte:** POPWEB
**Tipo:** Automático ou manual
**Unidade:** Número de abordados na rua
**Cálculo:**

```text
Total de abordagens sociais registradas pela equipe específica PETI no período.
```

---

### 4.10 Número de atendimentos particularizados dos CRAS

**Fonte:** RMA CRAS C.1
**Tipo:** Automático ou manual
**Unidade:** Número de atendimentos
**Cálculo:**

```text
Total de atendimentos individualizados realizados pelos CRAS no período.
```

---

### 4.11 Número de famílias atendidas em plantão nos CREAS

**Fonte:** SIGAS
**Tipo:** Automático ou manual
**Unidade:** Número de atendidos
**Cálculo:**

```text
Total de atendimentos registrados no SIGAS com tipo "plantão".
```

---

### 4.12 Número de idosos em serviços de proteção social domiciliar, Centro Dia e ILPI

**Fonte:** Sistema OSC
**Tipo:** Automático ou manual
**Unidade:** Número de vagas
**Cálculo:**

```text
Vagas Proteção Social Domiciliar
+
Vagas Centro Dia Idoso
+
Vagas ILPI
```

---

### 4.13 Número de vagas para pessoas idosas em Centro Dia

**Fonte:** Sistema OSC
**Tipo:** Automático ou manual
**Unidade:** Número de vagas
**Cálculo:**

```text
Total de vagas pactuadas nos serviços Centro Dia Idoso.
```

---

### 4.14 Pessoas em situação de rua abordadas pela equipe do Serviço de Abordagem Social

**Fonte:** POPWEB + RMA CREAS K.1
**Tipo:** Automático ou manual
**Unidade:** Número de pessoas diferentes abordadas
**Cálculo:**

```text
Total de pessoas diferentes abordadas e registradas no POPWEB
ou
Total informado no RMA CREAS K.1.
```

---

### 4.15 Taxa de atualização cadastral das famílias até 1/2 salário mínimo no CadÚnico

**Fonte:** CECAD
**Tipo:** Automático
**Unidade:** Percentual
**Cálculo:**

```text
Total de famílias com cadastro atualizado até 24 meses
÷
Total de famílias com renda per capita até 1/2 salário mínimo
× 100
```

---

### 4.16 Vagas para pessoas idosas em ILPI

**Fonte:** Sistema OSC
**Tipo:** Automático ou manual
**Unidade:** Número de vagas
**Cálculo:**

```text
Total de vagas pactuadas nos serviços de ILPI.
```

---

## 5. Estrutura sugerida do banco de dados

### 5.1 Tabela: ppa_indicadores

Armazena o cadastro principal do indicador.

```sql
CREATE TABLE ppa_indicadores (
    id INT AUTO_INCREMENT PRIMARY KEY,

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

    tipo_alimentacao ENUM('automatico', 'manual', 'hibrido') DEFAULT 'manual',
    periodicidade ENUM('mensal', 'bimestral', 'trimestral', 'quadrimestral', 'semestral', 'anual') DEFAULT 'anual',

    status TINYINT DEFAULT 1,

    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL
);
```

---

### 5.2 Tabela: ppa_fontes_dados

Armazena as fontes de dados usadas pelos indicadores.

```sql
CREATE TABLE ppa_fontes_dados (
    id INT AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(100) NOT NULL,
    descricao TEXT NULL,

    tipo_fonte ENUM('banco_externo', 'sistema_externo', 'manual', 'planilha', 'api') DEFAULT 'manual',

    nome_banco VARCHAR(100) NULL,
    nome_tabela VARCHAR(100) NULL,
    observacao TEXT NULL,

    status TINYINT DEFAULT 1,

    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL
);
```

Exemplos de fontes:

```text
CECAD
CadÚnico
RMA CRAS
RMA CREAS
SIGAS
POPWEB
Sistema OSC
Entrada Manual
```

---

### 5.3 Tabela: ppa_indicador_fontes

Relaciona indicadores com uma ou mais fontes de dados.

```sql
CREATE TABLE ppa_indicador_fontes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    indicador_id INT NOT NULL,
    fonte_id INT NOT NULL,

    papel_fonte ENUM('principal', 'numerador', 'denominador', 'apoio', 'manual') DEFAULT 'principal',

    observacao TEXT NULL,

    FOREIGN KEY (indicador_id) REFERENCES ppa_indicadores(id),
    FOREIGN KEY (fonte_id) REFERENCES ppa_fontes_dados(id)
);
```

Exemplo:

```text
Indicador: Acompanhar 10% das famílias PBF

Fonte 1: RMA CRAS B.2
Papel: numerador

Fonte 2: CECAD
Papel: denominador
```

---

### 5.4 Tabela: ppa_indicador_variaveis

Armazena as variáveis usadas no cálculo.

```sql
CREATE TABLE ppa_indicador_variaveis (
    id INT AUTO_INCREMENT PRIMARY KEY,

    indicador_id INT NOT NULL,

    chave VARCHAR(100) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT NULL,

    tipo_valor ENUM('inteiro', 'decimal', 'percentual', 'texto') DEFAULT 'decimal',

    origem ENUM('automatico', 'manual', 'calculado') DEFAULT 'manual',

    obrigatorio TINYINT DEFAULT 1,

    FOREIGN KEY (indicador_id) REFERENCES ppa_indicadores(id)
);
```

Exemplo de variáveis:

```text
total_familias_pbf
familias_acompanhadas_b2
meta_10_porcento
resultado_percentual
```

---

### 5.5 Tabela: ppa_resultados

Armazena o resultado consolidado de cada indicador por período.

```sql
CREATE TABLE ppa_resultados (
    id INT AUTO_INCREMENT PRIMARY KEY,

    indicador_id INT NOT NULL,

    ano_referencia INT NOT NULL,
    competencia CHAR(7) NULL,

    valor_numerador DECIMAL(15,4) NULL,
    valor_denominador DECIMAL(15,4) NULL,
    valor_meta_quantitativa DECIMAL(15,4) NULL,
    valor_resultado DECIMAL(15,4) NOT NULL,

    indice_recente DECIMAL(15,4) NULL,
    indice_futuro DECIMAL(15,4) NULL,

    unidade_medida VARCHAR(100) NULL,

    cras VARCHAR(150) NULL,
    creas VARCHAR(150) NULL,
    bairro VARCHAR(150) NULL,
    regiao VARCHAR(100) NULL,
    unidade VARCHAR(150) NULL,
    servico VARCHAR(150) NULL,

    tipo_lancamento ENUM('automatico', 'manual', 'hibrido') DEFAULT 'manual',

    fonte_resumo VARCHAR(255) NULL,
    observacao TEXT NULL,

    status ENUM('rascunho', 'validado', 'publicado', 'cancelado') DEFAULT 'rascunho',

    criado_por INT NULL,
    validado_por INT NULL,

    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    validado_em DATETIME NULL,

    FOREIGN KEY (indicador_id) REFERENCES ppa_indicadores(id),

    INDEX idx_indicador_ano (indicador_id, ano_referencia),
    INDEX idx_competencia (competencia),
    INDEX idx_cras (cras),
    INDEX idx_creas (creas),
    INDEX idx_bairro (bairro),
    INDEX idx_regiao (regiao)
);
```

---

### 5.6 Tabela: ppa_resultado_variaveis

Guarda os valores detalhados usados no cálculo do resultado.

```sql
CREATE TABLE ppa_resultado_variaveis (
    id INT AUTO_INCREMENT PRIMARY KEY,

    resultado_id INT NOT NULL,
    variavel_id INT NOT NULL,

    valor_decimal DECIMAL(15,4) NULL,
    valor_texto TEXT NULL,

    origem ENUM('automatico', 'manual', 'calculado') DEFAULT 'manual',

    FOREIGN KEY (resultado_id) REFERENCES ppa_resultados(id),
    FOREIGN KEY (variavel_id) REFERENCES ppa_indicador_variaveis(id)
);
```

Essa tabela evita criar uma coluna nova para cada tipo de indicador.

---

### 5.7 Tabela: ppa_sincronizacoes

Controla cada importação ou cálculo executado.

```sql
CREATE TABLE ppa_sincronizacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    indicador_id INT NULL,
    fonte_id INT NULL,

    ano_referencia INT NOT NULL,
    competencia CHAR(7) NULL,

    tipo_execucao ENUM('manual', 'agendada', 'reprocessamento') DEFAULT 'manual',

    status ENUM('pendente', 'processando', 'concluido', 'erro') DEFAULT 'pendente',

    total_lidos INT DEFAULT 0,
    total_processados INT DEFAULT 0,
    total_inseridos INT DEFAULT 0,
    total_atualizados INT DEFAULT 0,

    mensagem TEXT NULL,
    erro TEXT NULL,

    executado_por INT NULL,

    iniciado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    finalizado_em DATETIME NULL,

    FOREIGN KEY (indicador_id) REFERENCES ppa_indicadores(id),
    FOREIGN KEY (fonte_id) REFERENCES ppa_fontes_dados(id)
);
```

---

## 6. Exemplo de cadastro de indicador

```sql
INSERT INTO ppa_indicadores (
    codigo,
    numero_programa,
    nome,
    unidade_medida,
    indice_recente,
    indice_futuro,
    memoria_calculo,
    fonte_dados,
    tipo_alimentacao,
    periodicidade
) VALUES (
    'PPA-149',
    149,
    'Acompanhar 10% das famílias beneficiárias do Programa Bolsa Família inscritas no CadÚnico',
    'Percentual de famílias',
    7,
    10,
    'RMA CRAS B.2 / Total de famílias com PBF CECAD Dezembro do ano anterior',
    'CECAD e RMA CRAS',
    'hibrido',
    'anual'
);
```

---

## 7. Exemplo de variáveis para indicador percentual

```sql
INSERT INTO ppa_indicador_variaveis (
    indicador_id,
    chave,
    nome,
    tipo_valor,
    origem
) VALUES
(1, 'total_familias_pbf', 'Total de famílias com PBF no CECAD', 'inteiro', 'automatico'),
(1, 'familias_acompanhadas_b2', 'Famílias acompanhadas no RMA CRAS B.2', 'inteiro', 'automatico'),
(1, 'meta_10_porcento', 'Meta quantitativa de 10%', 'decimal', 'calculado'),
(1, 'resultado_percentual', 'Resultado percentual alcançado', 'percentual', 'calculado');
```

---

## 8. Exemplo de resultado consolidado

```sql
INSERT INTO ppa_resultados (
    indicador_id,
    ano_referencia,
    competencia,
    valor_numerador,
    valor_denominador,
    valor_meta_quantitativa,
    valor_resultado,
    indice_recente,
    indice_futuro,
    unidade_medida,
    tipo_lancamento,
    fonte_resumo,
    status
) VALUES (
    1,
    2026,
    '2025-12',
    700,
    10000,
    1000,
    7,
    7,
    10,
    'Percentual de famílias',
    'hibrido',
    'CECAD Dezembro/2025 + RMA CRAS B.2',
    'publicado'
);
```

---

## 9. Estrutura visual da página do indicador

```text
[PPA-149] Acompanhar 10% das famílias beneficiárias do PBF

Unidade de medida: Percentual de famílias
Fonte: CECAD + RMA CRAS
Periodicidade: Anual
Tipo de alimentação: Híbrida

┌─────────────────────┐ ┌─────────────────────┐ ┌─────────────────────┐
│ Índice Recente      │ │ Índice Futuro       │ │ Resultado Apurado   │
│ 7%                  │ │ 10%                 │ │ 7%                  │
└─────────────────────┘ └─────────────────────┘ └─────────────────────┘

Memória de cálculo:
RMA CRAS B.2 / Total de famílias com PBF no CECAD × 100

Dados do cálculo:
- Numerador: 700 famílias acompanhadas
- Denominador: 10.000 famílias PBF
- Meta quantitativa: 1.000 famílias
- Resultado: 7%

Filtros:
[Ano] [Competência] [CRAS] [Região] [Status]

Gráfico:
Evolução anual do indicador

Tabela:
Ano | Numerador | Denominador | Meta | Resultado | Status
```

---

## 10. Sugestão para o painel administrativo

### Cadastro do indicador

Campos recomendados:

```text
Código
Número do programa
Nome do indicador
Objetivo
Justificativa
Público-alvo
ODS
Meta ODS
Unidade de medida
Índice recente
Índice futuro
Memória de cálculo
Fonte dos dados
Critério utilizado
Forma de cálculo
Resultado esperado
Tipo de alimentação
Periodicidade
Status
```

---

### Lançamento manual de resultado

Campos recomendados:

```text
Indicador
Ano de referência
Competência
Valor do numerador
Valor do denominador
Valor da meta quantitativa
Valor do resultado
Índice recente
Índice futuro
Fonte resumo
Observação
Status
```

---

### Sincronização

A tela de sincronização deve permitir:

```text
Selecionar indicador
Selecionar fonte
Selecionar ano de referência
Selecionar competência
Executar sincronização
Ver histórico
Ver erros
Reprocessar
Publicar resultado
```

---

## 11. Recomendação técnica inicial

Para este módulo, a estrutura mais flexível é:

```text
ppa_indicadores
    Cadastro principal

ppa_fontes_dados
    Fontes como CECAD, RMA, SIGAS, POPWEB, OSC

ppa_indicador_fontes
    Relação entre indicador e fontes

ppa_indicador_variaveis
    Variáveis usadas no cálculo

ppa_resultados
    Resultado consolidado por ano/competência

ppa_resultado_variaveis
    Detalhamento dos valores usados no cálculo

ppa_sincronizacoes
    Histórico de execuções
```

Essa estrutura permite atender indicadores simples, percentuais, somatórios, manuais, automáticos e híbridos sem precisar alterar o banco a cada novo indicador.

---

## 12. Observação importante

Neste primeiro momento, o ideal é não tentar calcular tudo diretamente na tela.

O fluxo mais seguro é:

```text
1. Cadastrar indicador
2. Cadastrar fontes
3. Cadastrar variáveis do cálculo
4. Executar sincronização ou lançar manualmente
5. Salvar resultado consolidado
6. Publicar resultado
7. Exibir na página pública/administrativa do indicador
```

Assim o módulo nasce preparado para crescer sem virar uma teia de aranha no banco de dados.
