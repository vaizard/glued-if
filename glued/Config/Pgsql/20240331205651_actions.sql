-- migrate:up

CREATE TABLE glued.if__actions (
    uuid uuid generated always as (((doc ->> 'uuid'::text))::uuid) stored not null,
    doc jsonb not null,
    nonce bytea generated always as ( decode( md5((doc - 'uuid')::text), 'hex')) stored,
    created_at timestamp with time zone default CURRENT_TIMESTAMP,
    updated_at timestamp with time zone default CURRENT_TIMESTAMP,
    svc_name text generated always as (doc->>'service.name') stored,
    svc_version text generated always as (doc->>'service.version') stored,
    svc_method text generated always as (doc->>'service.method') stored,
    svc_deployment uuid generated always as (((doc ->> 'service.deployment'::text))::uuid) stored not null,
    PRIMARY KEY (uuid),
    UNIQUE (nonce),
    FOREIGN KEY (svc_deployment) REFERENCES glued.if__deployments(uuid)
);

COMMENT ON TABLE glued.if__actions IS 'IF service actions are service methods available in an deployment configuration/authorization context.';
COMMENT ON COLUMN glued.if__actions.svc_name IS 'IF microservice name (i.e. gov_ares_cz)';
COMMENT ON COLUMN glued.if__actions.svc_version IS 'IF microservice version (i.e. v1)';
COMMENT ON COLUMN glued.if__actions.svc_method IS 'IF microservice method (i.e. search)';
COMMENT ON COLUMN glued.if__actions.svc_deployment IS 'IF Deployment reference, a.k.a. the configuration/authorization context to a microservice';

-- migrate:down

DROP TABLE IF EXISTS glued.if__actions;
