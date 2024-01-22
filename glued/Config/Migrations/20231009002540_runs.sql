-- migrate:up

CREATE TABLE `t_if__runs` (
  `c_action_uuid` binary(16) NOT NULL COMMENT 'IF action UUID',
  `c_uuid` binary(16) NOT NULL DEFAULT (uuid_to_bin(uuid(),true)) COMMENT 'IF run UUID',
  `c_data` json NOT NULL COMMENT 'JSON response body',
  `c_ts_start` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp: run started',
  `c_ts_finish` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp: run finished',
  `c_status` varchar(32) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL COMMENT 'Integration run status: init, ok, fail, skip',
  `c_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'Response data hash',
  `c_fid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Response foreign identifier',
  PRIMARY KEY (`c_uuid`),
  KEY `idx_act_uuid` (`c_action_uuid`),
  KEY `idx_response_hash` (`c_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC COMMENT='Integration framework service logs.';

-- migrate:down

DROP TABLE IF EXISTS `t_if__runs`;
