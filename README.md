# glued-if

IF is Glued's integration framework, message queue and scheduler.

**Implementation Overview:**

- `if microservice` is a set of methods implemented as a separate git project `glued-if-service_name` to interact with a foreign service
- `deployment` is a configuration and authorization context of an `if microservice` defined by a row/object of the `if__deployments` table
- `action` is a `deployment:microservice.method` map stored as a row in the `if__actions` table. actions are triggered by calling an API endpoint (webhook) or by the IF scheduler.
- `action state` makes a stateful history using `if__actions_states` 
- `scheduler`is implemented as a set of cron-like rules with some extra sugarcoating (i.e. ttl) stored in the `if__scheduler` table and an associated producer that will periodically fill up the `message queue` with work and runner daemons that will subscribe to this queue and launch workers (perform the webhook api calls)
- `message queue` is implemented as the `if__mq_queue` table configuring message queues clients can subscribe to.
- `messages` are implemented as the `if__mq_messages` table which maps a queue to a message payload and message headers (i.e. ttl, requesting, replies, etc.)
- `notifications` are implemented using the pg_notify capabilities and can be further extended with rabbitmq. 
- `logging` is by default implemented using the monolog library.

## Integrations

IF orchestrates `if microservices` which facilitate integration with anything thats out there.

- Each `if microservice` implements working with a remote/external `service`.
- Each `if microservice` has associated `deployments` (a set of attributes describing usage of an `if microservice`), i.e:
  - deployment metadata (i.e. name, description, etc.)
  - deployment connection (i.e. remote host, auth tokens, rate limits, etc.)
  - deployment RBAC rules* (i.e. who can use the if microservice)
- Methods implemented by an `if microservice` (i.e. CRUD operations against an external service) are associated to deployments as `actions` (actions are if microservice methods runnable in the context of a deployment configuration). actions
  - can run on-demand (i.e. provide a caching data transforming interface to external services such as glued-if-ares_gov_cz)
  - can run according to a schedule (by the scheduler) 
  - can interact with each other (via the message queue)

*) RBAC is provided by glued-core's authorization proxy

## Scheduler

tbd

## Message queue

The message queue is loosely inspired by RabbitMQ to enable an easy transition between the builtin
PostgreSQL based queue and Rabbit. The indented MQ usage is
- to distribute scheduled tasks to workers,
- to facilitate communication between the loosely coupled microservices
- to ensure internal notifications to users are send and individually delivered (with or without user confirmation)

IFMQ, when acting as a RabbitMQ-like message brooker, implements the concepts of
- `producers` / code responsible for generating messages - php implementation is part of glued-lib
- `queues` / 'message destinations' implemented via the `if__queues` table
- `consumers` / clients subscribed to queues
- `exchanges` / code responsible for delivering messages to queues - direct (unicast), fanout (multicast), header (rule based multicast)

Messges can be 