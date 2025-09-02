-- migrate:up

CREATE TABLE glued.if__ingest_log (
  uuid     uuid DEFAULT gen_random_uuid() NOT NULL,
  version  uuid GENERATED ALWAYS AS (uuid) VIRTUAL NOT NULL,  -- identical to uuid
  doc      jsonb NOT NULL,
  meta     jsonb NOT NULL DEFAULT '{}'::jsonb,
  nonce    bytea GENERATED ALWAYS AS (decode(md5((doc::text)), 'hex')) STORED,
  iat      timestamptz DEFAULT now() NOT NULL,
  uat      timestamptz GENERATED ALWAYS AS ( iat ) VIRTUAL NOT NULL,
  sat      text,
  ext_id   text NOT NULL,
  PRIMARY KEY (nonce, iat)
);
-- CREATE INDEX ingest_ext_iat_desc ON glued.ingest (ext_id, iat DESC);


-- migrate:down

DROP TABLE IF EXISTS glued.if__ingest_log;
