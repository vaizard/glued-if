-- migrate:up

CREATE TABLE glued.if__actions_valid_response_cache (
    action_uuid uuid NOT NULL REFERENCES if__actions(uuid) ON DELETE CASCADE, -- message uuid
    req_payload jsonb NULL,
    req_params jsonb NULL,
    req_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, -- (last) requested at
    res_payload jsonb NULL,
    res_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, -- issued at
    res_replays integer DEFAULT 0,
    fid text NOT NULL,
    nonce bytea GENERATED ALWAYS AS (decode(md5(action_uuid::text || req_payload || req_params || res_payload || fid), 'hex')) STORED PRIMARY KEY
);

-- migrate:down

DROP TABLE IF EXISTS glued.if__actions_valid_response_cache;
