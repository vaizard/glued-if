-- migrate:up

ALTER TABLE t_if__logs
ADD CONSTRAINT uc_act_uuid_response_hash UNIQUE (c_act_uuid, c_response_hash),
ADD CONSTRAINT uc_act_uuid_response_fid UNIQUE (c_act_uuid, c_response_fid),
CHANGE `c_response_fid` `c_response_fid` varchar(255) COLLATE 'utf8mb4_bin' NULL COMMENT 'Response foreign identifier' AFTER `c_response_hash`;


-- migrate:down

ALTER TABLE t_if__logs
CHANGE `c_response_fid` `c_response_fid` varchar(255) COLLATE 'utf8mb4_0900_ai_ci' NULL COMMENT 'Response foreign identifier' AFTER `c_response_hash`,
DROP CONSTRAINT uc_act_uuid_response_hash,
DROP CONSTRAINT uc_act_uuid_response_fid;
