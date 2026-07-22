INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'principal',
    'total_familias',
    'Vinculo inicial do primeiro indicador do PPA com a primeira consulta externa ativa.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-ERRADICAR-POBREZA'
  AND eq.id = (
        SELECT MIN(eq2.id)
        FROM external_data_queries eq2
        WHERE eq2.ativo = 1
    )
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.papel = 'principal'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'principal',
    'base_familias_pbf',
    'Base fixa de familias beneficiarias do PBF para o indicador 149.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-ACOMPANHAR-FAMILIAS-PBF'
  AND eq.nome = 'PPA 149 - Base familias PBF por CRAS'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'base_familias_pbf'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'apoio',
    'familias_acompanhadas_b2_mensal',
    'Serie mensal do RMA CRAS B.2 para o indicador 149.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-ACOMPANHAR-FAMILIAS-PBF'
  AND eq.nome = 'PPA 149 - RMA CRAS B2 mensal por unidade'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'familias_acompanhadas_b2_mensal'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'principal',
    'base_familias_meio_sm',
    'Base fixa de familias com renda per capita de ate 1/2 salario minimo para o indicador 148.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-ACOMPANHAR-FAMILIAS-MEIO-SM-PAIF'
  AND eq.nome = 'PPA 148 - Base familias ate 1/2 salario minimo por CRAS'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'base_familias_meio_sm'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'apoio',
    'familias_acompanhadas_a2_mensal',
    'Serie mensal do RMA CRAS A.2 para o indicador 148.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-ACOMPANHAR-FAMILIAS-MEIO-SM-PAIF'
  AND eq.nome = 'PPA 148 - RMA CRAS A2 mensal por unidade'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'familias_acompanhadas_a2_mensal'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'principal',
    'base_familias_bpc',
    'Base fixa de familias com membros beneficiarios do BPC para o indicador 147.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-ACOMPANHAR-BPC-PAIF'
  AND eq.nome = 'PPA 147 - Base familias BPC por CRAS'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'base_familias_bpc'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'apoio',
    'familias_acompanhadas_b4_mensal',
    'Serie mensal do RMA CRAS B.4 para o indicador 147.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-ACOMPANHAR-BPC-PAIF'
  AND eq.nome = 'PPA 147 - RMA CRAS B4 mensal por unidade'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'familias_acompanhadas_b4_mensal'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'apoio',
    'serie_mensal_unidade_creas_f1',
    'Serie mensal do RMA CREAS F.1 para o indicador 151.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-CREAS-MULHERES-F1'
  AND eq.nome = 'PPA 151 - RMA CREAS F1 mensal por unidade'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'serie_mensal_unidade_creas_f1'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'apoio',
    'serie_mensal_unidade_creas_a2',
    'Serie mensal do RMA CREAS A.2 para o indicador 150.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-CREAS-PAEFI-A2'
  AND eq.nome = 'PPA 150 - RMA CREAS A2 mensal por unidade'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'serie_mensal_unidade_creas_a2'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'apoio',
    'serie_mensal_unidade_creas_j4',
    'Serie mensal do RMA CREAS J.4 para o indicador 153.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-CREAS-MSE-J4'
  AND eq.nome = 'PPA 153 - RMA CREAS J4 mensal por unidade'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'serie_mensal_unidade_creas_j4'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'apoio',
    'serie_mensal_unidade_creas_c1a5',
    'Serie mensal do RMA CREAS bloco C (C.1 a C.5) para o indicador 152.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-CREAS-CRIANCAS-ADOLESCENTES-C1A5'
  AND eq.nome = 'PPA 152 - RMA CREAS bloco C mensal por unidade'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'serie_mensal_unidade_creas_c1a5'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'apoio',
    'serie_mensal_unidade_cras_c1',
    'Serie mensal do RMA CRAS C.1 para o indicador 158.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-CRAS-ATENDIMENTOS-C1'
  AND eq.nome = 'PPA 158 - RMA CRAS C1 mensal por unidade'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'serie_mensal_unidade_cras_c1'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'apoio',
    'serie_mensal_unidade_cras_c3',
    'Serie mensal do RMA CRAS C.3 para o indicador 121.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-CRAS-ATUALIZACAO-C3'
  AND eq.nome = 'PPA 121 - RMA CRAS C3 mensal por unidade'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'serie_mensal_unidade_cras_c3'
    );

UPDATE ppa_indicador_queries pq
INNER JOIN ppa_indicadores pi ON pi.id = pq.indicador_id
SET pq.ativo = 1
WHERE pi.codigo = 'PPA-CRAS-ATUALIZACAO-C3'
  AND pq.campo_resultado = 'serie_mensal_unidade_cras_c3';

UPDATE ppa_indicador_queries pq
INNER JOIN ppa_indicadores pi ON pi.id = pq.indicador_id
SET pq.ativo = 0
WHERE pi.codigo = 'PPA-CRAS-ATUALIZACAO-C3'
  AND pq.campo_resultado = 'familias_atualizadas_24m';

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'principal',
    'base_familias_atualizacao_meio_sm',
    'Base fixa de familias com renda per capita de ate 1/2 salario minimo para o indicador 121.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-CRAS-ATUALIZACAO-C3'
  AND eq.nome = 'PPA 121 - Base familias ate 1/2 salario minimo por CRAS'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'base_familias_atualizacao_meio_sm'
    );

INSERT INTO ppa_indicador_queries (
    indicador_id,
    external_query_id,
    papel,
    campo_resultado,
    observacao,
    ativo
)
SELECT
    pi.id,
    eq.id,
    'apoio',
    'familias_atualizadas_24m',
    'Familias com cadastro atualizado em ate 24 meses para o indicador 121.',
    1
FROM ppa_indicadores pi
INNER JOIN external_data_queries eq ON eq.ativo = 1
WHERE pi.codigo = 'PPA-CRAS-ATUALIZACAO-C3'
  AND eq.nome = 'PPA 121 - Familias atualizadas ate 24 meses por CRAS'
  AND NOT EXISTS (
        SELECT 1
        FROM ppa_indicador_queries pq
        WHERE pq.indicador_id = pi.id
          AND pq.external_query_id = eq.id
          AND pq.campo_resultado = 'familias_atualizadas_24m'
    );
