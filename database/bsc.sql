CREATE TABLE `bsc` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `estrategia` text NOT NULL,
  `eixo` varchar(150) NOT NULL,
  `situacao` varchar(30) NOT NULL,
  `status_detalhado` text DEFAULT NULL,
  `data_referencia` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bsc_eixo` (`eixo`),
  KEY `idx_bsc_situacao` (`situacao`),
  KEY `idx_bsc_data_referencia` (`data_referencia`),
  CONSTRAINT `chk_bsc_situacao` CHECK (`situacao` in ('Não iniciado','Em andamento','Concluído','Suspenso'))
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci