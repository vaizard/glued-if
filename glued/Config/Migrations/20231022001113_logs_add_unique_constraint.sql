-- migrate:up

ALTER TABLE t_if__logs
CHANGE `c_response_fid` `c_response_fid` varchar(255) COLLATE 'utf8mb4_bin' NULL COMMENT 'Response foreign identifier' AFTER `c_response_hash`,
CHANGE `c_ok` `c_status` varchar(32) COLLATE 'ascii_general_ci' DEFAULT NULL COMMENT 'Integration run status: init, ok, fail, skip' AFTER `c_ts_responded`;

-- migrate:down

ALTER TABLE t_if__logs
CHANGE `c_response_fid` `c_response_fid` varchar(255) COLLATE 'utf8mb4_0900_ai_ci' NULL COMMENT 'Response foreign identifier' AFTER `c_response_hash`,
CHANGE `c_status` `c_ok` tinyint(1) DEFAULT NULL COMMENT 'Integration run success/fail';