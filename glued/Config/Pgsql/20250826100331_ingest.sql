-- migrate:up

-- =========================
-- (EXTERNAL) INGEST RAW LOG
-- =========================
-- Append-only record of upstream payloads keyed by ext_id (no dedupe; every event is stored).
-- For forensic replay and auditing of “as received” data (iat/ms; nonce = md5(doc::text)).
-- Use with IngestRawLog class

CREATE TABLE if__ingest_log (
    ext_id   text NOT NULL,
    uuid     uuid DEFAULT gen_random_uuid() NOT NULL,  -- raw row id
    version  uuid DEFAULT uuidv7() NOT NULL,           -- time-sortable tie-break
    doc      jsonb NOT NULL,
    meta     jsonb NOT NULL DEFAULT '{}'::jsonb,
    nonce    bytea GENERATED ALWAYS AS (decode(md5((doc::text)), 'hex')) STORED,
    iat      bigint DEFAULT (EXTRACT(EPOCH FROM clock_timestamp()) * 1000)::bigint NOT NULL,
    uat      bigint GENERATED ALWAYS AS (iat) VIRTUAL NOT NULL,
    dat      bigint DEFAULT NULL,
    sat      text,
    period   int8range GENERATED ALWAYS AS (
        int8range(
                COALESCE((meta->>'nbf')::bigint, iat),
                GREATEST(
                        COALESCE(LEAST(dat, (meta->>'exp')::bigint), dat, (meta->>'exp')::bigint),
                        COALESCE((meta->>'nbf')::bigint, iat)
                ),
                '[)'
        )
        ) STORED,
PRIMARY KEY (uuid)
);

CREATE INDEX if__ingest_log_ext_iat_ver_desc ON if__ingest_log (ext_id, iat DESC, version DESC);
CREATE INDEX if__ingest_log_nonce_iat        ON if__ingest_log (nonce, iat);

-- migrate:down

DROP TABLE IF EXISTS glued.if__ingest_log;
