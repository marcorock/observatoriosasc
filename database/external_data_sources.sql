CREATE TABLE `external_data_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `host` varchar(190) NOT NULL,
  `porta` int(11) NOT NULL DEFAULT 3306,
  `database_name` varchar(120) NOT NULL,
  `username` varchar(120) NOT NULL,
  `password_encrypted` text NOT NULL,
  `charset` varchar(40) NOT NULL DEFAULT 'utf8mb4',
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_external_data_sources_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


describe external_data_sources