-- migrate:up

DROP TABLE IF EXISTS glued.if__mq_queue_subscribers CASCADE;
DROP TABLE IF EXISTS glued.if__mq_queues CASCADE;
DROP TABLE IF EXISTS glued.if__actions_valid_response_cache CASCADE;
DROP TABLE IF EXISTS glued.if__mq_messages CASCADE;
DROP TABLE IF EXISTS glued.if__actions CASCADE;


-- migrate:down

