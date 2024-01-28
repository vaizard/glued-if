-- migrate:up

CREATE TABLE `t_if__actions` (
  `c_deployment_uuid` binary(16) NOT NULL COMMENT 'IF deployment UUID',
  `c_uuid` binary(16) NOT NULL DEFAULT (uuid_to_bin(uuid(),true)) COMMENT 'IF action uuid (v4)',
  `c_data` json NOT NULL COMMENT 'JSON data',
  `c_scheme` varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,_utf8mb4'$.scheme'))) STORED COMMENT '[STORED] Interface Framework action scheme',
  `c_note` varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,_utf8mb4'$.note'))) STORED COMMENT '[STORED] Interface Framework action note',
  `c_freq` varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,_utf8mb4'$.freq'))) STORED COMMENT '[STORED] Interface Framework action frequency',
  PRIMARY KEY (`c_uuid`),
  UNIQUE KEY `uniq_service_scheme` (`c_deployment_uuid`,`c_scheme`),
  KEY `idx_service` (`c_deployment_uuid`),
  KEY `idx_scheme` (`c_scheme`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC COMMENT='Integration framework service instance actions.';

-- migrate:down

DROP TABLE IF EXISTS `t_if__actions`;
