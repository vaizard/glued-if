-- migrate:up

CREATE TABLE glued.if__mq_queues (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL PRIMARY KEY,
    name TEXT,
    description TEXT,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    ttl integer NULL, -- queue ttl in seconds
    dlq_uuid UUID, -- Reference to another queue acting as DLQ, leave NULL for a cleanup
    FOREIGN KEY (dlq_uuid) REFERENCES glued.if__mq_queues(uuid)
);

COMMENT ON TABLE "glued"."if__mq_queues" IS 'Message queues.';
COMMENT ON COLUMN "glued"."if__mq_queues"."uuid" IS 'Queue UUID.';
COMMENT ON COLUMN "glued"."if__mq_queues"."name" IS 'Queue name.';

-- migrate:down

DROP TABLE glued.if__mq_queues;
