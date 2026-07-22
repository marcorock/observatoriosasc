INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 149 - Base familias PBF por CRAS',
    'Base fixa de familias beneficiarias do Programa Bolsa Familia por CRAS, usando a referencia mais recente de ref_cad da tabela familia.',
    'SELECT
    COALESCE(NULLIF(TRIM(f.cras), ''''), ''NAO INFORMADO'') AS cras,
    COALESCE(NULLIF(TRIM(f.regiao), ''''), ''NAO INFORMADA'') AS regiao,
    COUNT(DISTINCT f.cod_familiar_fam) AS total_familias_pbf,
    MAX(f.ref_cad) AS ref_cad_referencia
FROM familia f
WHERE f.marc_pbf = ''1''
  AND f.ref_cad = (
        SELECT MAX(f2.ref_cad)
        FROM familia f2
        WHERE COALESCE(TRIM(f2.ref_cad), '''') <> ''''
    )
GROUP BY
    COALESCE(NULLIF(TRIM(f.cras), ''''), ''NAO INFORMADO''),
    COALESCE(NULLIF(TRIM(f.regiao), ''''), ''NAO INFORMADA'')
ORDER BY cras ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 149 - Base familias PBF por CRAS'
    );

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 149 - RMA CRAS B2 mensal por unidade',
    'Serie mensal do RMA CRAS para o campo B.2, consolidando novas familias beneficiarias do PBF inseridas em acompanhamento no ano mais recente disponivel.',
    'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    ) AS cras,
    SUM(COALESCE(r.b2, 0)) AS total_familias_acompanhadas
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, cras ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 149 - RMA CRAS B2 mensal por unidade'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Base fixa de familias beneficiarias do Programa Bolsa Familia por CRAS, usando a referencia mais recente de ref_cad da tabela familia.',
    q.sql_query = 'SELECT
    COALESCE(NULLIF(TRIM(f.cras), ''''), ''NAO INFORMADO'') AS cras,
    COALESCE(NULLIF(TRIM(f.regiao), ''''), ''NAO INFORMADA'') AS regiao,
    COUNT(DISTINCT f.cod_familiar_fam) AS total_familias_pbf,
    MAX(f.ref_cad) AS ref_cad_referencia
FROM familia f
WHERE f.marc_pbf = ''1''
  AND f.ref_cad = (
        SELECT MAX(f2.ref_cad)
        FROM familia f2
        WHERE COALESCE(TRIM(f2.ref_cad), '''') <> ''''
    )
GROUP BY
    COALESCE(NULLIF(TRIM(f.cras), ''''), ''NAO INFORMADO''),
    COALESCE(NULLIF(TRIM(f.regiao), ''''), ''NAO INFORMADA'')
ORDER BY cras ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 149 - Base familias PBF por CRAS';

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Serie mensal do RMA CRAS para o campo B.2, consolidando novas familias beneficiarias do PBF inseridas em acompanhamento no ano mais recente disponivel.',
    q.sql_query = 'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    ) AS cras,
    SUM(COALESCE(r.b2, 0)) AS total_familias_acompanhadas
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, cras ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 149 - RMA CRAS B2 mensal por unidade';

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 148 - Base familias ate 1/2 salario minimo por CRAS',
    'Base fixa de familias com renda per capita de ate meio salario minimo por CRAS, usando a classificacao de renda da tabela pessoa na referencia mais recente.',
    'SELECT
    COALESCE(NULLIF(TRIM(p.cras), ''''), ''NAO INFORMADO'') AS cras,
    COALESCE(NULLIF(TRIM(p.regiao), ''''), ''NAO INFORMADA'') AS regiao,
    COUNT(DISTINCT p.cod_familiar_fam) AS total_familias_pbf,
    MAX(p.ref_cad) AS ref_cad_referencia
FROM pessoa p
WHERE p.ref_cad = (
        SELECT MAX(p2.ref_cad)
        FROM pessoa p2
        WHERE COALESCE(TRIM(p2.ref_cad), '''') <> ''''
    )
  AND (
        COALESCE(p.renda_0, ''0'') = ''1''
        OR COALESCE(p.`>_0_a_1_4`, ''0'') = ''1''
        OR COALESCE(p.`>1_4_a_1_2`, ''0'') = ''1''
    )
GROUP BY
    COALESCE(NULLIF(TRIM(p.cras), ''''), ''NAO INFORMADO''),
    COALESCE(NULLIF(TRIM(p.regiao), ''''), ''NAO INFORMADA'')
ORDER BY cras ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 148 - Base familias ate 1/2 salario minimo por CRAS'
    );

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 148 - RMA CRAS A2 mensal por unidade',
    'Serie mensal do RMA CRAS para o campo A.2, consolidando novas familias inseridas em acompanhamento no ano mais recente disponivel.',
    'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    ) AS cras,
    SUM(COALESCE(r.a2, 0)) AS total_familias_acompanhadas
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, cras ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 148 - RMA CRAS A2 mensal por unidade'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Base fixa de familias com renda per capita de ate meio salario minimo por CRAS, usando a classificacao de renda da tabela pessoa na referencia mais recente.',
    q.sql_query = 'SELECT
    COALESCE(NULLIF(TRIM(p.cras), ''''), ''NAO INFORMADO'') AS cras,
    COALESCE(NULLIF(TRIM(p.regiao), ''''), ''NAO INFORMADA'') AS regiao,
    COUNT(DISTINCT p.cod_familiar_fam) AS total_familias_pbf,
    MAX(p.ref_cad) AS ref_cad_referencia
FROM pessoa p
WHERE p.ref_cad = (
        SELECT MAX(p2.ref_cad)
        FROM pessoa p2
        WHERE COALESCE(TRIM(p2.ref_cad), '''') <> ''''
    )
  AND (
        COALESCE(p.renda_0, ''0'') = ''1''
        OR COALESCE(p.`>_0_a_1_4`, ''0'') = ''1''
        OR COALESCE(p.`>1_4_a_1_2`, ''0'') = ''1''
    )
GROUP BY
    COALESCE(NULLIF(TRIM(p.cras), ''''), ''NAO INFORMADO''),
    COALESCE(NULLIF(TRIM(p.regiao), ''''), ''NAO INFORMADA'')
ORDER BY cras ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 148 - Base familias ate 1/2 salario minimo por CRAS';

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Serie mensal do RMA CRAS para o campo A.2, consolidando novas familias inseridas em acompanhamento no ano mais recente disponivel.',
    q.sql_query = 'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    ) AS cras,
    SUM(COALESCE(r.a2, 0)) AS total_familias_acompanhadas
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, cras ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 148 - RMA CRAS A2 mensal por unidade';

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 147 - Base familias BPC por CRAS',
    'Base fixa de familias com membros beneficiarios do BPC por CRAS, usando a classificacao da tabela pessoa na referencia mais recente.',
    'SELECT
    COALESCE(NULLIF(TRIM(p.cras), ''''), ''NAO INFORMADO'') AS cras,
    COALESCE(NULLIF(TRIM(p.regiao), ''''), ''NAO INFORMADA'') AS regiao,
    COUNT(DISTINCT p.cod_familiar_fam) AS total_familias_pbf,
    MAX(p.ref_cad) AS ref_cad_referencia
FROM pessoa p
WHERE p.ref_cad = (
        SELECT MAX(p2.ref_cad)
        FROM pessoa p2
        WHERE COALESCE(TRIM(p2.ref_cad), '''') <> ''''
    )
  AND COALESCE(TRIM(p.bpc), '''') <> ''''
GROUP BY
    COALESCE(NULLIF(TRIM(p.cras), ''''), ''NAO INFORMADO''),
    COALESCE(NULLIF(TRIM(p.regiao), ''''), ''NAO INFORMADA'')
ORDER BY cras ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 147 - Base familias BPC por CRAS'
    );

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 147 - RMA CRAS B4 mensal por unidade',
    'Serie mensal do RMA CRAS para o campo B.4, consolidando novas familias inseridas em acompanhamento com membros beneficiarios do BPC no ano mais recente disponivel.',
    'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    ) AS cras,
    SUM(COALESCE(r.b4, 0)) AS total_familias_acompanhadas
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, cras ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 147 - RMA CRAS B4 mensal por unidade'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Base fixa de familias com membros beneficiarios do BPC por CRAS, usando a classificacao da tabela pessoa na referencia mais recente.',
    q.sql_query = 'SELECT
    COALESCE(NULLIF(TRIM(p.cras), ''''), ''NAO INFORMADO'') AS cras,
    COALESCE(NULLIF(TRIM(p.regiao), ''''), ''NAO INFORMADA'') AS regiao,
    COUNT(DISTINCT p.cod_familiar_fam) AS total_familias_pbf,
    MAX(p.ref_cad) AS ref_cad_referencia
FROM pessoa p
WHERE p.ref_cad = (
        SELECT MAX(p2.ref_cad)
        FROM pessoa p2
        WHERE COALESCE(TRIM(p2.ref_cad), '''') <> ''''
    )
  AND COALESCE(TRIM(p.bpc), '''') <> ''''
GROUP BY
    COALESCE(NULLIF(TRIM(p.cras), ''''), ''NAO INFORMADO''),
    COALESCE(NULLIF(TRIM(p.regiao), ''''), ''NAO INFORMADA'')
ORDER BY cras ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 147 - Base familias BPC por CRAS';

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Serie mensal do RMA CRAS para o campo B.4, consolidando novas familias inseridas em acompanhamento com membros beneficiarios do BPC no ano mais recente disponivel.',
    q.sql_query = 'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    ) AS cras,
    SUM(COALESCE(r.b4, 0)) AS total_familias_acompanhadas
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(REPLACE(TRIM(r.nome_unidade), CHAR(39), ''''), ''''),
        COALESCE(NULLIF(REPLACE(TRIM(r.id_cras), CHAR(39), ''''), ''''), ''CRAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, cras ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 147 - RMA CRAS B4 mensal por unidade';

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 151 - RMA CREAS F1 mensal por unidade',
    'Serie mensal do RMA CREAS para o campo F.1, consolidando mulheres adultas vitimas de violencia intrafamiliar inseridas em acompanhamento no ano mais recente disponivel.',
    'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    ) AS unidade,
    SUM(COALESCE(r.f1, 0)) AS total_inseridos
FROM rma_creas r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_creas r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 151 - RMA CREAS F1 mensal por unidade'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Serie mensal do RMA CREAS para o campo F.1, consolidando mulheres adultas vitimas de violencia intrafamiliar inseridas em acompanhamento no ano mais recente disponivel.',
    q.sql_query = 'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    ) AS unidade,
    SUM(COALESCE(r.f1, 0)) AS total_inseridos
FROM rma_creas r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_creas r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 151 - RMA CREAS F1 mensal por unidade';

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 150 - RMA CREAS A2 mensal por unidade',
    'Serie mensal do RMA CREAS para o campo A.2, consolidando novos casos inseridos em acompanhamento pelo PAEFI no ano mais recente disponivel.',
    'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    ) AS unidade,
    SUM(COALESCE(r.a2, 0)) AS total_inseridos
FROM rma_creas r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_creas r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 150 - RMA CREAS A2 mensal por unidade'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Serie mensal do RMA CREAS para o campo A.2, consolidando novos casos inseridos em acompanhamento pelo PAEFI no ano mais recente disponivel.',
    q.sql_query = 'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    ) AS unidade,
    SUM(COALESCE(r.a2, 0)) AS total_inseridos
FROM rma_creas r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_creas r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 150 - RMA CREAS A2 mensal por unidade';

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 153 - RMA CREAS J4 mensal por unidade',
    'Serie mensal do RMA CREAS para o campo J.4, consolidando novos adolescentes em cumprimento de medidas socioeducativas inseridos em acompanhamento no ano mais recente disponivel.',
    'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    ) AS unidade,
    SUM(COALESCE(r.j4, 0)) AS total_inseridos
FROM rma_creas r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_creas r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 153 - RMA CREAS J4 mensal por unidade'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Serie mensal do RMA CREAS para o campo J.4, consolidando novos adolescentes em cumprimento de medidas socioeducativas inseridos em acompanhamento no ano mais recente disponivel.',
    q.sql_query = 'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    ) AS unidade,
    SUM(COALESCE(r.j4, 0)) AS total_inseridos
FROM rma_creas r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_creas r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 153 - RMA CREAS J4 mensal por unidade';

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 152 - RMA CREAS bloco C mensal por unidade',
    'Serie mensal do RMA CREAS para a somatoria dos itens C.1 a C.5, consolidando novos casos de criancas e adolescentes inseridos no PAEFI no ano mais recente disponivel.',
    'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    ) AS unidade,
    SUM(
        COALESCE(r.c1, 0)
        + COALESCE(r.c2, 0)
        + COALESCE(r.c3, 0)
        + COALESCE(r.c4, 0)
        + COALESCE(r.c5, 0)
    ) AS total_inseridos
FROM rma_creas r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_creas r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 152 - RMA CREAS bloco C mensal por unidade'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Serie mensal do RMA CREAS para a somatoria dos itens C.1 a C.5, consolidando novos casos de criancas e adolescentes inseridos no PAEFI no ano mais recente disponivel.',
    q.sql_query = 'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    ) AS unidade,
    SUM(
        COALESCE(r.c1, 0)
        + COALESCE(r.c2, 0)
        + COALESCE(r.c3, 0)
        + COALESCE(r.c4, 0)
        + COALESCE(r.c5, 0)
    ) AS total_inseridos
FROM rma_creas r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_creas r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_creas), ''''), ''CREAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 152 - RMA CREAS bloco C mensal por unidade';

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 158 - RMA CRAS C1 mensal por unidade',
    'Serie mensal do RMA CRAS para o campo C.1, consolidando atendimentos individualizados realizados no ano mais recente disponivel.',
    'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_cras), ''''), ''CRAS NAO INFORMADO'')
    ) AS unidade,
    SUM(COALESCE(r.c1, 0)) AS total_inseridos
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_cras), ''''), ''CRAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 158 - RMA CRAS C1 mensal por unidade'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Serie mensal do RMA CRAS para o campo C.1, consolidando atendimentos individualizados realizados no ano mais recente disponivel.',
    q.sql_query = 'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_cras), ''''), ''CRAS NAO INFORMADO'')
    ) AS unidade,
    SUM(COALESCE(r.c1, 0)) AS total_inseridos
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_cras), ''''), ''CRAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 158 - RMA CRAS C1 mensal por unidade';

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 121 - RMA CRAS C3 mensal por unidade',
    'Serie mensal do RMA CRAS para o campo C.3, consolidando familias encaminhadas para atualizacao cadastral no ano mais recente disponivel.',
    'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_cras), ''''), ''CRAS NAO INFORMADO'')
    ) AS unidade,
    SUM(COALESCE(r.c3, 0)) AS total_inseridos
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_cras), ''''), ''CRAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 121 - RMA CRAS C3 mensal por unidade'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Serie mensal do RMA CRAS para o campo C.3, consolidando familias encaminhadas para atualizacao cadastral no ano mais recente disponivel.',
    q.sql_query = 'SELECT
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01'') AS mes_referencia,
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_cras), ''''), ''CRAS NAO INFORMADO'')
    ) AS unidade,
    SUM(COALESCE(r.c3, 0)) AS total_inseridos
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY
    DATE_FORMAT(r.mes_referencia, ''%Y-%m-01''),
    COALESCE(
        NULLIF(TRIM(r.nome_unidade), ''''),
        COALESCE(NULLIF(TRIM(r.id_cras), ''''), ''CRAS NAO INFORMADO'')
    )
ORDER BY mes_referencia ASC, unidade ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 121 - RMA CRAS C3 mensal por unidade';

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 121 - Base familias ate 1/2 salario minimo por CRAS',
    'Base fixa de familias com renda per capita de ate meio salario minimo por CRAS, usando a classificacao de renda da tabela pessoa na referencia mais recente.',
    'SELECT
    COALESCE(NULLIF(TRIM(COALESCE(f.cras, p.cras)), ''''), ''NAO INFORMADO'') AS cras,
    COALESCE(NULLIF(TRIM(COALESCE(f.regiao, p.regiao)), ''''), ''NAO INFORMADA'') AS regiao,
    COUNT(DISTINCT p.cod_familiar_fam) AS total_familias_pbf,
    MAX(p.ref_cad) AS ref_cad_referencia
FROM pessoa p
LEFT JOIN familia f
    ON f.cod_familiar_fam = p.cod_familiar_fam
   AND f.ref_cad = p.ref_cad
WHERE p.ref_cad = (
        SELECT MAX(p2.ref_cad)
        FROM pessoa p2
        WHERE COALESCE(TRIM(p2.ref_cad), '''') <> ''''
    )
  AND (
        COALESCE(p.renda_0, ''0'') = ''1''
        OR COALESCE(p.`>_0_a_1_4`, ''0'') = ''1''
        OR COALESCE(p.`>1_4_a_1_2`, ''0'') = ''1''
    )
GROUP BY
    COALESCE(NULLIF(TRIM(COALESCE(f.cras, p.cras)), ''''), ''NAO INFORMADO''),
    COALESCE(NULLIF(TRIM(COALESCE(f.regiao, p.regiao)), ''''), ''NAO INFORMADA'')
ORDER BY cras ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 121 - Base familias ate 1/2 salario minimo por CRAS'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Base fixa de familias com renda per capita de ate meio salario minimo por CRAS, usando a classificacao de renda da tabela pessoa na referencia mais recente.',
    q.sql_query = 'SELECT
    COALESCE(NULLIF(TRIM(COALESCE(f.cras, p.cras)), ''''), ''NAO INFORMADO'') AS cras,
    COALESCE(NULLIF(TRIM(COALESCE(f.regiao, p.regiao)), ''''), ''NAO INFORMADA'') AS regiao,
    COUNT(DISTINCT p.cod_familiar_fam) AS total_familias_pbf,
    MAX(p.ref_cad) AS ref_cad_referencia
FROM pessoa p
LEFT JOIN familia f
    ON f.cod_familiar_fam = p.cod_familiar_fam
   AND f.ref_cad = p.ref_cad
WHERE p.ref_cad = (
        SELECT MAX(p2.ref_cad)
        FROM pessoa p2
        WHERE COALESCE(TRIM(p2.ref_cad), '''') <> ''''
    )
  AND (
        COALESCE(p.renda_0, ''0'') = ''1''
        OR COALESCE(p.`>_0_a_1_4`, ''0'') = ''1''
        OR COALESCE(p.`>1_4_a_1_2`, ''0'') = ''1''
    )
GROUP BY
    COALESCE(NULLIF(TRIM(COALESCE(f.cras, p.cras)), ''''), ''NAO INFORMADO''),
    COALESCE(NULLIF(TRIM(COALESCE(f.regiao, p.regiao)), ''''), ''NAO INFORMADA'')
ORDER BY cras ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 121 - Base familias ate 1/2 salario minimo por CRAS';

INSERT INTO external_data_queries (
    source_id,
    nome,
    descricao,
    sql_query,
    ativo
)
SELECT
    s.id,
    'PPA 121 - Familias atualizadas ate 24 meses por CRAS',
    'Base de familias com renda per capita de ate meio salario minimo e cadastro atualizado em ate 24 meses, consolidada por CRAS na referencia mais recente.',
    'SELECT
    MAX(base.ref_cad) AS mes_referencia,
    COALESCE(NULLIF(TRIM(f.cras), ''''), ''CRAS NAO INFORMADO'') AS cras,
    SUM(familias_atualizadas.total_familias_atualizadas) AS total_familias_acompanhadas
FROM (
    SELECT
        p.cod_familiar_fam,
        p.ref_cad,
        COUNT(*) AS total_registros
    FROM pessoa p
    WHERE p.ref_cad = (
            SELECT MAX(p2.ref_cad)
            FROM pessoa p2
            WHERE COALESCE(TRIM(p2.ref_cad), '''') <> ''''
        )
      AND (
            COALESCE(p.renda_0, ''0'') = ''1''
            OR COALESCE(p.`>_0_a_1_4`, ''0'') = ''1''
            OR COALESCE(p.`>1_4_a_1_2`, ''0'') = ''1''
        )
    GROUP BY p.cod_familiar_fam, p.ref_cad
) base
INNER JOIN familia f
    ON f.cod_familiar_fam = base.cod_familiar_fam
   AND f.ref_cad = base.ref_cad
INNER JOIN (
    SELECT
        p3.cod_familiar_fam,
        p3.ref_cad,
        1 AS total_familias_atualizadas
    FROM pessoa p3
    INNER JOIN familia f3
        ON f3.cod_familiar_fam = p3.cod_familiar_fam
       AND f3.ref_cad = p3.ref_cad
    WHERE p3.ref_cad = (
            SELECT MAX(p4.ref_cad)
            FROM pessoa p4
            WHERE COALESCE(TRIM(p4.ref_cad), '''') <> ''''
        )
      AND (
            COALESCE(p3.renda_0, ''0'') = ''1''
            OR COALESCE(p3.`>_0_a_1_4`, ''0'') = ''1''
            OR COALESCE(p3.`>1_4_a_1_2`, ''0'') = ''1''
        )
      AND COALESCE(TRIM(f3.dat_atual_fam), '''') <> ''''
      AND TIMESTAMPDIFF(
            MONTH,
            COALESCE(
                STR_TO_DATE(NULLIF(TRIM(f3.dat_atual_fam), ''''), ''%Y-%m-%d''),
                STR_TO_DATE(NULLIF(TRIM(f3.dat_atual_fam), ''''), ''%d/%m/%Y'')
            ),
            COALESCE(
                STR_TO_DATE(NULLIF(TRIM(p3.ref_cad), ''''), ''%Y-%m-%d''),
                STR_TO_DATE(NULLIF(TRIM(p3.ref_cad), ''''), ''%d/%m/%Y'')
            )
        ) BETWEEN 0 AND 24
    GROUP BY p3.cod_familiar_fam, p3.ref_cad
) familias_atualizadas
    ON familias_atualizadas.cod_familiar_fam = base.cod_familiar_fam
   AND familias_atualizadas.ref_cad = base.ref_cad
GROUP BY
    COALESCE(NULLIF(TRIM(f.cras), ''''), ''CRAS NAO INFORMADO'')
ORDER BY cras ASC',
    1
FROM external_data_sources s
WHERE s.nome = 'Cadastro único'
  AND NOT EXISTS (
        SELECT 1
        FROM external_data_queries q
        WHERE q.source_id = s.id
          AND q.nome = 'PPA 121 - Familias atualizadas ate 24 meses por CRAS'
    );

UPDATE external_data_queries q
INNER JOIN external_data_sources s ON s.id = q.source_id
SET q.descricao = 'Base de familias com renda per capita de ate meio salario minimo e cadastro atualizado em ate 24 meses, consolidada por CRAS na referencia mais recente.',
    q.sql_query = 'SELECT
    MAX(base.ref_cad) AS mes_referencia,
    COALESCE(NULLIF(TRIM(f.cras), ''''), ''CRAS NAO INFORMADO'') AS cras,
    SUM(familias_atualizadas.total_familias_atualizadas) AS total_familias_acompanhadas
FROM (
    SELECT
        p.cod_familiar_fam,
        p.ref_cad,
        COUNT(*) AS total_registros
    FROM pessoa p
    WHERE p.ref_cad = (
            SELECT MAX(p2.ref_cad)
            FROM pessoa p2
            WHERE COALESCE(TRIM(p2.ref_cad), '''') <> ''''
        )
      AND (
            COALESCE(p.renda_0, ''0'') = ''1''
            OR COALESCE(p.`>_0_a_1_4`, ''0'') = ''1''
            OR COALESCE(p.`>1_4_a_1_2`, ''0'') = ''1''
        )
    GROUP BY p.cod_familiar_fam, p.ref_cad
) base
INNER JOIN familia f
    ON f.cod_familiar_fam = base.cod_familiar_fam
   AND f.ref_cad = base.ref_cad
INNER JOIN (
    SELECT
        p3.cod_familiar_fam,
        p3.ref_cad,
        1 AS total_familias_atualizadas
    FROM pessoa p3
    INNER JOIN familia f3
        ON f3.cod_familiar_fam = p3.cod_familiar_fam
       AND f3.ref_cad = p3.ref_cad
    WHERE p3.ref_cad = (
            SELECT MAX(p4.ref_cad)
            FROM pessoa p4
            WHERE COALESCE(TRIM(p4.ref_cad), '''') <> ''''
        )
      AND (
            COALESCE(p3.renda_0, ''0'') = ''1''
            OR COALESCE(p3.`>_0_a_1_4`, ''0'') = ''1''
            OR COALESCE(p3.`>1_4_a_1_2`, ''0'') = ''1''
        )
      AND COALESCE(TRIM(f3.dat_atual_fam), '''') <> ''''
      AND TIMESTAMPDIFF(
            MONTH,
            COALESCE(
                STR_TO_DATE(NULLIF(TRIM(f3.dat_atual_fam), ''''), ''%Y-%m-%d''),
                STR_TO_DATE(NULLIF(TRIM(f3.dat_atual_fam), ''''), ''%d/%m/%Y'')
            ),
            COALESCE(
                STR_TO_DATE(NULLIF(TRIM(p3.ref_cad), ''''), ''%Y-%m-%d''),
                STR_TO_DATE(NULLIF(TRIM(p3.ref_cad), ''''), ''%d/%m/%Y'')
            )
        ) BETWEEN 0 AND 24
    GROUP BY p3.cod_familiar_fam, p3.ref_cad
) familias_atualizadas
    ON familias_atualizadas.cod_familiar_fam = base.cod_familiar_fam
   AND familias_atualizadas.ref_cad = base.ref_cad
GROUP BY
    COALESCE(NULLIF(TRIM(f.cras), ''''), ''CRAS NAO INFORMADO'')
ORDER BY cras ASC',
    q.ativo = 1
WHERE s.nome = 'Cadastro único'
  AND q.nome = 'PPA 121 - Familias atualizadas ate 24 meses por CRAS';
