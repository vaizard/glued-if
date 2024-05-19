-- migrate:up

CREATE TABLE glued.if__mq_queue_subscribers (
    queue_uuid uuid NOT NULL,
    subscriber_uuid uuid NOT NULL,
    subscriber_kind text NOT NULL,
    subscriber_note text NULL,
    PRIMARY KEY (queue_uuid, subscriber_uuid),
    FOREIGN KEY (queue_uuid) REFERENCES glued.if__mq_queues(uuid)
);

-- migrate:down

DROP TABLE glued.if__mq_queue_subscribers;

