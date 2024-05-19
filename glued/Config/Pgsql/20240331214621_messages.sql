-- migrate:up

CREATE TABLE glued.if__mq_messages (
    uuid uuid DEFAULT gen_random_uuid() NOT NULL,                       -- message uuid
    queue_uuid uuid NOT NULL,                                           -- Recipients subscribe to a queue
    subject text NULL,                                                  -- Message subject, i.e.: `run`
    payload jsonb NULL,                                                 -- json payload
    priority integer NOT NULL DEFAULT 1 CHECK (priority IN (1,2,3,4,5)),-- priority in range: 1 .. 5
    snd_at TIMESTAMP WITHOUT TIME ZONE NULL DEFAULT now(),              -- message created and sent timestamp
    snd_by uuid NULL,                                                   -- message created and sent author
    dlv_at TIMESTAMP WITHOUT TIME ZONE NULL,                            -- delivered at timestamp
    dlv_to uuid NULL,                                                   -- delivered to uuid
    ack_rq TIMESTAMP WITHOUT TIME ZONE NULL,                            -- acknowledgement is required before time (or isnt required if null)
    ack_at TIMESTAMP WITHOUT TIME ZONE NULL,                            -- acknowledged at timestamp
    ack_as TEXT CHECK (ack_as IN ('ack', 'nack', 'reject')),            -- acknowledged as ack (acknowledged / i.e. worker performed an action with an ok status), nack (received but not acknowledged / i.e. worker performed an action, but something failed in the process), reject (rejected / i.e. worker received an invalid payload)
    rpl_to uuid NULL,                                                   -- replies to be sent to queue uuid
    nbf_at TIMESTAMP WITHOUT TIME ZONE NULL,                            -- message not before (delayed messages)
    exp_at TIMESTAMP WITHOUT TIME ZONE NULL,                            -- message expires at (time limit on messages)
    PRIMARY KEY (uuid, queue_uuid)                                      -- Composite primary key on uuid and queue_uuid
);

-- migrate:down

DROP TABLE IF EXISTS glued.if__mq_messages;
