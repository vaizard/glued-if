-- migrate:up

CREATE TABLE glued.if__actions (
   uuid uuid generated always as (((doc ->> 'uuid'::text))::uuid) stored not null,
   doc jsonb not null,
   nonce bytea generated always as ( decode( md5((doc - 'uuid')::text), 'hex')) stored,
   created_at timestamp with time zone default CURRENT_TIMESTAMP,
   updated_at timestamp with time zone default CURRENT_TIMESTAMP,
   path text generated always as (doc->>'path') stored,
   method text generated always as (doc->>'method') stored,
   deployment uuid generated always as (((doc ->> 'deployment'::text))::uuid) stored not null,
   PRIMARY KEY (uuid),
   UNIQUE (nonce),
   FOREIGN KEY (deployment) REFERENCES glued.if__deployments(uuid)
);

COMMENT ON TABLE glued.if__actions IS 'IF service actions are service methods available in an deployment configuration/authorization context.';
COMMENT ON COLUMN glued.if__actions.path IS 'Request path (i.e. /api/if/svc/some_if_service/v1/some_method)';
COMMENT ON COLUMN glued.if__actions.method IS 'Request method (i.e. get/post/put/delete/patch)';
COMMENT ON COLUMN glued.if__actions.deployment IS 'Deployment UUID whose endpoints are requested, defines the configuration/authorization context to a microservice';

-- migrate:down

DROP TABLE IF EXISTS glued.if__actions;
