
use observatoriosasc

-- Validação do Indicador 121
-- 1. Total de famílias com cadastro atualizado até 24 meses
-- 2. Total de famílias com renda per capita de até 1/2 salário mínimo
-- 3. Total de famílias encaminhadas para atualização (RMA CRAS C.3)

-- =========================================================
-- 1) Total de famílias com cadastro atualizado até 24 meses
-- Regra:
-- - usa a referência mais recente de ref_cad na tabela pessoa
-- - considera famílias distintas
-- - considera somente famílias com renda até 1/2 salário mínimo
-- - usa familia.dat_atual_fam
-- - considera atualizado quando a diferença entre dat_atual_fam e ref_cad
--   for entre 0 e 24 meses
-- =========================================================
SELECT
    COUNT(DISTINCT p.cod_familiar_fam) AS total_familias_atualizadas_24_meses
FROM pessoa p
INNER JOIN familia f
    ON f.cod_familiar_fam = p.cod_familiar_fam
   AND f.ref_cad = p.ref_cad
WHERE p.ref_cad = (
        SELECT MAX(p2.ref_cad)
        FROM pessoa p2
        WHERE COALESCE(TRIM(p2.ref_cad), '') <> ''
    )
  AND (
        COALESCE(p.renda_0, '0') = '1'
        OR COALESCE(p.`>_0_a_1_4`, '0') = '1'
        OR COALESCE(p.`>1_4_a_1_2`, '0') = '1'
    )
  AND COALESCE(TRIM(f.dat_atual_fam), '') <> ''
  AND TIMESTAMPDIFF(
        MONTH,
        COALESCE(
            STR_TO_DATE(NULLIF(TRIM(f.dat_atual_fam), ''), '%Y-%m-%d'),
            STR_TO_DATE(NULLIF(TRIM(f.dat_atual_fam), ''), '%d/%m/%Y')
        ),
        COALESCE(
            STR_TO_DATE(NULLIF(TRIM(p.ref_cad), ''), '%Y-%m-%d'),
            STR_TO_DATE(NULLIF(TRIM(p.ref_cad), ''), '%d/%m/%Y')
        )
    ) BETWEEN 0 AND 24;

-- =========================================================
-- 2) Total de famílias com renda per capita de até 1/2 SM
-- Regra:
-- - usa a referência mais recente de ref_cad na tabela pessoa
-- - considera famílias distintas
-- - usa as faixas:
--   renda_0
--   >_0_a_1_4
--   >1_4_a_1_2
-- =========================================================
SELECT
    COUNT(DISTINCT p.cod_familiar_fam) AS total_familias_ate_meio_salario
FROM pessoa p
WHERE p.ref_cad = (
        SELECT MAX(p2.ref_cad)
        FROM pessoa p2
        WHERE COALESCE(TRIM(p2.ref_cad), '') <> ''
    )
  AND (
        COALESCE(p.renda_0, '0') = '1'
        OR COALESCE(p.`>_0_a_1_4`, '0') = '1'
        OR COALESCE(p.`>1_4_a_1_2`, '0') = '1'
    );

-- =========================================================
-- 3) Total de famílias encaminhadas para atualização
-- Regra:
-- - soma o campo C.3 do RMA CRAS
-- - usa o ano mais recente disponível em rma_cras
-- =========================================================
SELECT
    YEAR(r.mes_referencia) AS ano_referencia,
    SUM(COALESCE(r.c3, 0)) AS total_familias_encaminhadas_atualizacao
FROM rma_cras r
WHERE YEAR(r.mes_referencia) = (
        SELECT MAX(YEAR(r2.mes_referencia))
        FROM rma_cras r2
        WHERE r2.mes_referencia IS NOT NULL
    )
GROUP BY YEAR(r.mes_referencia);

-- =========================================================
-- Extra opcional:
-- visão resumida em uma única linha
-- =========================================================
SELECT
    base.total_familias_ate_meio_salario,
    atualizadas.total_familias_atualizadas_24_meses,
    encaminhadas.total_familias_encaminhadas_atualizacao,
    ROUND(
        (atualizadas.total_familias_atualizadas_24_meses / NULLIF(base.total_familias_ate_meio_salario, 0)) * 100,
        2
    ) AS taxa_real_atualizacao_percentual,
    ROUND(base.total_familias_ate_meio_salario * 0.85, 2) AS meta_85_percentual_base,
    ROUND(
        (atualizadas.total_familias_atualizadas_24_meses / NULLIF(base.total_familias_ate_meio_salario * 0.85, 0)) * 100,
        2
    ) AS execucao_contra_meta_85_percentual
FROM
(
    SELECT
        COUNT(DISTINCT p.cod_familiar_fam) AS total_familias_ate_meio_salario
    FROM pessoa p
    WHERE p.ref_cad = (
            SELECT MAX(p2.ref_cad)
            FROM pessoa p2
            WHERE COALESCE(TRIM(p2.ref_cad), '') <> ''
        )
      AND (
            COALESCE(p.renda_0, '0') = '1'
            OR COALESCE(p.`>_0_a_1_4`, '0') = '1'
            OR COALESCE(p.`>1_4_a_1_2`, '0') = '1'
        )
) base
CROSS JOIN
(
    SELECT
        COUNT(DISTINCT p.cod_familiar_fam) AS total_familias_atualizadas_24_meses
    FROM pessoa p
    INNER JOIN familia f
        ON f.cod_familiar_fam = p.cod_familiar_fam
       AND f.ref_cad = p.ref_cad
    WHERE p.ref_cad = (
            SELECT MAX(p2.ref_cad)
            FROM pessoa p2
            WHERE COALESCE(TRIM(p2.ref_cad), '') <> ''
        )
      AND (
            COALESCE(p.renda_0, '0') = '1'
            OR COALESCE(p.`>_0_a_1_4`, '0') = '1'
            OR COALESCE(p.`>1_4_a_1_2`, '0') = '1'
        )
      AND COALESCE(TRIM(f.dat_atual_fam), '') <> ''
      AND TIMESTAMPDIFF(
            MONTH,
            COALESCE(
                STR_TO_DATE(NULLIF(TRIM(f.dat_atual_fam), ''), '%Y-%m-%d'),
                STR_TO_DATE(NULLIF(TRIM(f.dat_atual_fam), ''), '%d/%m/%Y')
            ),
            COALESCE(
                STR_TO_DATE(NULLIF(TRIM(p.ref_cad), ''), '%Y-%m-%d'),
                STR_TO_DATE(NULLIF(TRIM(p.ref_cad), ''), '%d/%m/%Y')
            )
        ) BETWEEN 0 AND 24
) atualizadas
CROSS JOIN
(
    SELECT
        SUM(COALESCE(r.c3, 0)) AS total_familias_encaminhadas_atualizacao
    FROM rma_cras r
    WHERE YEAR(r.mes_referencia) = (
            SELECT MAX(YEAR(r2.mes_referencia))
            FROM rma_cras r2
            WHERE r2.mes_referencia IS NOT NULL
        )
) encaminhadas;