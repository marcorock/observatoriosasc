CREATE TABLE `ppa_indicador_queries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `indicador_id` bigint(20) unsigned NOT NULL,
  `external_query_id` bigint(20) unsigned NOT NULL,
  `papel` enum('principal','base','apoio') NOT NULL DEFAULT 'principal',
  `campo_resultado` varchar(120) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ppa_indicador_queries_indicador_id` (`indicador_id`),
  KEY `idx_ppa_indicador_queries_external_query_id` (`external_query_id`),
  CONSTRAINT `fk_ppa_indicador_queries_indicador`
    FOREIGN KEY (`indicador_id`) REFERENCES `ppa_indicadores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ppa_indicador_queries_external_query`
    FOREIGN KEY (`external_query_id`) REFERENCES `external_data_queries` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
