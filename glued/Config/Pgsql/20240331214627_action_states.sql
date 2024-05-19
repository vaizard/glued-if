-- migrate:up

CREATE TABLE glued.if__actions_states (
    uuid uuid NOT NULL DEFAULT gen_random_uuid(), -- uuid state
    action_uuid uuid NOT NULL REFERENCES if__actions(uuid) ON DELETE CASCADE, -- message uuid
    at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fid varchar(255) NOT NULL,
    nonce bytea NULL,
    UNIQUE ("action_uuid", "nonce"),
    UNIQUE ("action_uuid", "fid")
);

-- migrate:down

DROP TABLE IF EXISTS glued.if__actions_states;
