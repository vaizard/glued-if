-- migrate:up

CREATE TABLE `t_if__objects` (
  `c_uuid` binary(16) NOT NULL DEFAULT (uuid_to_bin(uuid(),true)) COMMENT 'Object UUID',
  `c_action` binary(16) NOT NULL COMMENT 'Action UUID (infer action scheme, service, and service deployment)',
  `c_fid` varchar(255) NOT NULL COMMENT 'JSON data object foreign unique identifier',
  `c_rev` int unsigned DEFAULT '0' COMMENT 'JSON data object revision number',
  `c_iat` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of issuance',
  `c_uat` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp of update',
  `c_data` json DEFAULT NULL,
  `c_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`c_data`))) STORED COMMENT 'MD5 hash of c_data',
  `c_run` binary(16) DEFAULT NULL COMMENT 'Response UUID (infer run hash and run fid)',
  PRIMARY KEY (`c_uuid`),
  UNIQUE KEY `unique_action_fid` (`c_action`,`c_fid`),
  KEY `idx_c_action` (`c_action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- migrate:down

DROP TABLE IF EXISTS `t_if__objects`;
