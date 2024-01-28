-- migrate:up

CREATE TABLE `t_if__deployments` (
 `c_uuid` binary(16) NOT NULL DEFAULT (uuid_to_bin(uuid(),true)) COMMENT 'IF service instance uuid (v4), autogenerated on SQL insert if not provided. NOTE to always insert with UUID_TO_BIN(UUID(), true)',
 `c_data` json NOT NULL COMMENT 'JSON data',
 `c_hash` varchar(32) GENERATED ALWAYS AS (md5(`c_data`)) STORED COMMENT '[STORED] MD5 Hash of the data json (acting as a unique index)',
 `c_service` varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,_utf8mb4'$.service'))) STORED COMMENT '[STORED] Interface Framework service service',
 `c_remote` varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,_utf8mb4'$.remote'))) STORED COMMENT '[STORED] Interface Framework remote remote',
 `c_name` varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,_utf8mb4'$.name'))) STORED COMMENT '[STORED] Interface Framework service deployment name',
 `c_note` varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,_utf8mb4'$.note'))) STORED COMMENT '[STORED] Interface Framework remote note',
  PRIMARY KEY (`c_uuid`),
  UNIQUE KEY `c_hash` (`c_hash`),
  KEY `idx_service` (`c_service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC COMMENT='Integration framework service instances (service / remote remote / authentication).';

-- migrate:down

DROP TABLE IF EXISTS `t_if__deployments`;
