-- migrate:up

CREATE TABLE glued.if__actions (
    uuid uuid generated always as (((doc ->> 'uuid'::text))::uuid) stored not null,
    doc jsonb not null,
    nonce bytea generated always as ( decode( md5((doc - 'uuid')::text), 'hex')) stored,
    created_at timestamp with time zone default CURRENT_TIMESTAMP,
    updated_at timestamp with time zone default CURRENT_TIMESTAMP,
    svc_name text generated always as (doc->>'service.name') stored,
    svc_version text generated always as (doc->>'service.version') stored,
    svc_version text generated always as (doc->>'service.version') stored,

    us_version varchar(255),
    us_method varchar(255),
    deployment_uuid uuid NOT NULL,
    props jsonb NULL,
    nonce bytea GENERATED ALWAYS AS ( decode( md5( (deployment_uuid || us_version || us_method || props )::text ), 'hex')) STORED,
    PRIMARY KEY (uuid),
    UNIQUE (nonce),
    FOREIGN KEY (deployment_uuid) REFERENCES glued.if__deployments(uuid)
);

COMMENT ON TABLE glued.if__actions IS 'IF service actions are service methods available in an deployment configuration/authorization context.';
COMMENT ON COLUMN glued.if__actions.us_name IS 'IF microservice name (i.e. gov_ares_cz)';
COMMENT ON COLUMN glued.if__actions.us_version IS 'IF microservice version (i.e. v1)';
COMMENT ON COLUMN glued.if__actions.us_method IS 'IF microservice method (i.e. search)';
COMMENT ON COLUMN glued.if__actions.deployment_uuid IS 'IF Deployment reference, a.k.a. the configuration/authorization context to a microservice';

-- migrate:down

DROP TABLE IF EXISTS glued.if__actions;
