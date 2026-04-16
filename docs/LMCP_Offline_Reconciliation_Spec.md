# LMCP Offline Reconciliation Specification

## 1. Purpose

This document defines the authoritative offline replay and reconciliation model for LMCP.

It is normative for:

- Flutter offline queue behavior
- Laravel sync batch processing
- API request and response handling for replayed writes
- conflict handling and auditability

This document must be implemented together with:

- `LMCP_System_Architecture_Document.md`
- `LMCP_UI_API_Contract.md`
- `LMCP_Implementation_Roadmap.md`

## 2. Principles

Offline support in LMCP is:

- offline-first for safe operational capture
- server-authoritative for persisted truth
- idempotent for all replayable writes
- auditable for every conflict, override, and rejection

LMCP must never allow:

- offline finance commits
- offline GL posting
- offline approvals or maker-checker decisions
- offline marketplace finalization

## 3. Offline Write Classes

### 3.1 Class A: Offline-Queueable Writes

Allowed behavior:

- capture locally while offline
- queue for replay
- send automatically when connectivity returns

Examples:

- milk log create
- field visit report create
- listing create
- offer submit
- non-settlement order note or fulfillment evidence draft where explicitly classified as replayable by domain rules

Rules:

- every request must have `client_request_id`
- client must show `queued` immediately after local save
- server replay must be idempotent

### 3.2 Class B: Offline Capture-Only Requests

Allowed behavior:

- capture draft locally
- require explicit online confirmation later

Examples:

- withdrawal request draft
- dispute draft
- marketplace receipt confirmation draft when policy requires online confirmation before operational commit

Rules:

- client must not show committed success
- draft becomes committed only after online confirmation and server acceptance

### 3.3 Class C: Online-Only Commits

Allowed behavior:

- no offline queueing of commit action
- action is blocked offline

Examples:

- approvals
- maker-checker decisions
- marketplace finalization
- GL posting

Rules:

- client must display blocked state
- client must not queue the commit as if it can complete offline

## 4. Authoritative Request Contract

### 4.1 Endpoint

- `POST /api/v1/sync/batch`
- `GET /api/v1/sync/status`

### 4.2 Headers

Required:

- `Authorization`
- `Accept-Language`
- `X-Request-Id` or equivalent request correlation header if platform standard exists

Optional:

- device metadata headers for diagnostics where approved by platform standard

### 4.3 Batch Request Shape

```json
{
  "data": {
    "device_id": "android-2af1",
    "sent_at": "2026-03-15T08:10:00Z",
    "items": [
      {
        "client_request_id": "5f2716c0-1fdc-4ef2-a9fd-92a36dc67a14",
        "request_type": "milk_log_create",
        "offline_class": "A",
        "occurred_at": "2026-03-15T07:52:00Z",
        "payload": {
          "member_id": 101,
          "quantity_liters": 8.5,
          "production_date": "2026-03-15"
        }
      }
    ]
  }
}
```

Rules:

- items must be sent in original local queue order
- each item must carry `client_request_id`
- each item must carry `request_type`
- each item must carry `offline_class`
- payload must be normalized before hashing or idempotency comparison on server

## 5. Batch Processing Rules

The server must process each item independently inside a single HTTP request lifecycle, but item success or failure must not implicitly roll back unrelated items in the same batch.

Processing order:

1. validate envelope
2. iterate items in request order
3. validate offline class against request type
4. validate actor scope and permissions
5. perform idempotency lookup
6. apply business validation
7. commit allowed item or reject with reason
8. return per-item reconciliation result in request order

Rules:

- request order must be preserved in results
- processing may use isolated DB transactions per item
- one failed item must not prevent other valid items from reconciling
- replay of a Class C item must return `blocked`

## 6. Result Statuses

Each result must use one of:

- `synced`
- `duplicate`
- `rejected`
- `conflict`
- `blocked`
- `failed_sync`

Definitions:

- `synced`
  - request committed successfully and authoritative entity reference is returned
- `duplicate`
  - same `client_request_id` already committed; return original entity reference and authoritative state
- `rejected`
  - request is structurally valid but fails validation or business rules and requires user correction
- `conflict`
  - request is structurally valid but collides with newer or protected server state and requires resolution
- `blocked`
  - request belongs to online-only class or actor is not permitted to replay this item
- `failed_sync`
  - transient server-side failure; safe to retry later

## 7. Batch Response Contract

```json
{
  "data": {
    "processed": 2,
    "failed": 1,
    "results": [
      {
        "client_request_id": "5f2716c0-1fdc-4ef2-a9fd-92a36dc67a14",
        "request_type": "milk_log_create",
        "status": "synced",
        "entity_type": "milk_log",
        "entity_id": 1001,
        "server_state": "synced",
        "processed_at": "2026-03-15T08:10:03Z"
      },
      {
        "client_request_id": "9f242fce-86fd-4e95-85c8-f777c48302af",
        "request_type": "milk_log_update",
        "status": "conflict",
        "conflict_code": "server_state_superseded",
        "server_state_summary": {
          "entity_id": 1001,
          "status": "approved"
        },
        "resolution_required": true,
        "resolution_options": [
          "view_server_record",
          "create_replacement_submission"
        ],
        "processed_at": "2026-03-15T08:10:04Z"
      }
    ]
  },
  "meta": {
    "request_id": "corr-32f2c4bb",
    "timestamp": "2026-03-15T08:10:05Z"
  }
}
```

## 8. Conflict Model

### 8.1 Conflict Conditions

A `conflict` result must be returned when:

- server record already moved to protected state such as `approved`, `posted`, `reversed`, or `finalized`
- same logical record was changed by an authorized actor after local capture
- replay attempts to overwrite authoritative data that cannot be safely merged
- request sequence depends on a prior item that was rejected or blocked

### 8.2 Conflict Precedence Rules

These rules are mandatory:

- approved server state overrides unsynced local edits
- posted finance state overrides unsynced local edits
- finalized marketplace state overrides unsynced local edits
- admin-authorized override or reversal overrides member-submitted state
- no client may silently overwrite approved, posted, reversed, or finalized records

### 8.3 Conflict Codes

Minimum codes:

- `server_state_superseded`
- `approval_state_locked`
- `ledger_state_locked`
- `finalization_state_locked`
- `scope_changed`
- `dependency_failed`
- `duplicate_with_payload_mismatch`

### 8.4 Resolution Rules

- members cannot directly resolve protected conflicts by overwriting server truth
- admin resolution requires explicit reason
- admin resolution must create append-only audit and domain events
- resolution may produce:
  - new replacement submission
  - explicit reversal event where allowed
  - rejection with corrective guidance

## 9. Duplicate Handling

Duplicates are determined by:

- cooperative scope
- `client_request_id`
- request type

Rules:

- if same `client_request_id` and same normalized payload already committed, return `duplicate`
- if same `client_request_id` exists but normalized payload differs, return `conflict` with `duplicate_with_payload_mismatch`
- duplicate detection must not create a second domain record

## 10. Ordering and Dependency Rules

Queue replay ordering must be deterministic.

Rules:

- client sends items in original local order
- server returns results in same order
- server may reject dependent items if prerequisite item failed

Examples:

- a local edit to a record must not be applied before the create exists on server
- a feed confirmation replay may be blocked if the referenced distribution was never accepted

## 11. Retry Safety

Safe retry behavior:

- `duplicate` is safe to stop retrying
- `synced` is safe to stop retrying
- `rejected` is not retried automatically until client data is corrected
- `conflict` is not retried automatically until user or admin resolution occurs
- `blocked` is not retried until action is performed through correct online workflow
- `failed_sync` may be retried automatically with backoff

Backoff requirements:

- exponential backoff
- jitter recommended on client
- retry cap configurable by request type

## 12. Audit Requirements

Every replayed item must support audit traceability.

Audit fields:

- `correlation_id`
- actor user id
- actor role snapshot
- cooperative id
- request type
- `client_request_id`
- result status
- entity reference if created
- denial or conflict code if not committed
- processed timestamp

Admin conflict resolution must also record:

- resolver user id
- resolver role snapshot
- resolution reason
- original server state summary
- resulting action taken

## 13. Flutter Client Requirements

The mobile client must:

- persist local queue in encrypted local storage
- preserve original request ordering
- tag each item with offline class
- show `queued` immediately for Class A local saves
- show draft-only state for Class B records
- show blocked state for Class C actions when offline
- never display server acceptance when only local persistence occurred
- expose sync center visibility for `failed_sync`, `conflict`, and `blocked`

Local queue item fields:

- local id
- `client_request_id`
- request type
- offline class
- payload snapshot
- local created timestamp
- retry count
- last attempted at
- last result status
- last result code

## 14. Laravel Server Requirements

The backend must implement:

- idempotency table lookup per replayable create
- request-type validator
- scope and policy validation before commit
- per-item DB transaction boundaries
- append-only audit event emission
- correlation-aware logs

Must not:

- batch-commit all items in one global DB transaction
- auto-merge protected conflicts
- allow replay of online-only commit operations

## 15. Error Mapping

Per-item result is preferred over whole-request failure whenever possible.

Whole-request failures:

- `400` malformed envelope
- `401` unauthenticated
- `403` actor cannot use sync endpoint
- `413` payload too large
- `429` rate limited
- `500` internal error before item processing begins

Per-item failures are returned in `results`.

## 16. Testing Requirements

Minimum automated coverage:

- duplicate replay returns original entity once
- same `client_request_id` with changed payload returns `conflict`
- Class C replay returns `blocked`
- request order is preserved in results
- approved server record rejects stale local overwrite
- one item failure does not prevent unrelated item success
- transient failure returns `failed_sync`
- admin override creates audit events

## 17. Change Control

Changes to this spec require coordinated updates to:

- API contract
- UI handoff specification
- implementation roadmap
- Flutter sync layer
- Laravel sync controllers and services
