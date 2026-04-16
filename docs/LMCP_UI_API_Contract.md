# LMCP UI-to-API Contract Sheet

## 1. Purpose

This document defines the minimum UI-to-API contract needed to implement the approved LMCP screens in Flutter mobile and Laravel web/admin without interpretation drift.

It is based on:

- The source architecture documentation
- The UI handoff specification
- The approved interaction patterns

This document separates:

- Source-backed API anchors explicitly present in the architecture
- Contract conventions ratified for implementation safety

Authoritative status:

- This document is normative for API request and response shape, pagination, validation error formatting, authorization error formatting, and enum exposure.
- Where this document conflicts with UI inventories, folder structures, or screen notes, this document wins unless the system architecture document explicitly overrides it.

## 2. Contract Rules

These rules apply across all UI features.

- All create or commit operations must include either `client_request_id` or `Idempotency-Key`
- All UI statuses must map to backend-defined enums
- Validation errors must be field-addressable by the frontend
- Authorization errors must be distinguishable from generic server errors
- Lists should use a consistent pagination format
- Export generation should use an explicit asynchronous job model where generation is non-trivial

## 3. Shared Request Conventions

### Authentication

- Authenticated requests use the Laravel authentication mechanism selected by the platform
- Privileged roles should expect MFA and device-binding flows where configured

### Idempotency

Use on:

- Milk production create
- Marketplace offer submit
- Marketplace finalization
- Finance contributions
- Withdrawal request
- Repayment posting
- Any offline-queueable create action

Rules:

- `client_request_id` is a UUID v4 generated client-side before submission
- `Idempotency-Key` is a UUID header used on commit-style endpoints
- Duplicate requests within the same cooperative scope must return the prior result rather than creating duplicates

### Time and locale

- All timestamps should be returned in UTC ISO 8601 format
- UI should localize display only
- Language negotiation should support `Accept-Language`

## 4. Shared Response Conventions

### Success envelope

Required standard success shape:

```json
{
  "data": {},
  "meta": {
    "request_id": "7d7fbd1f-9e21-4ae2-bec9-fc88d9c55d60",
    "timestamp": "2026-03-15T10:30:00Z"
  }
}
```

Notes:

- All new API endpoints must return `data` and optional `meta`.
- Existing Laravel resources must be wrapped to conform to this shape before frontend integration.

### Validation error shape

Required validation error shape:

```json
{
  "message": "Validation failed.",
  "errors": {
    "quantity": [
      "Quantity is required."
    ],
    "client_request_id": [
      "client_request_id must be a valid UUID."
    ]
  },
  "meta": {
    "request_id": "f22318da-f34e-4db2-b653-e34b7af9245f",
    "timestamp": "2026-03-15T10:31:00Z"
  }
}
```

UI requirements:

- Flutter and Laravel forms must be able to map `errors.<field>` to inline field validation
- Global validation failures should still preserve user-entered values

### Authorization error shape

Required authorization error shape:

```json
{
  "message": "You are not authorized to perform this action.",
  "code": "permission_denied",
  "meta": {
    "request_id": "649b47ce-c8c6-4450-a56c-27f7953b3ab8",
    "timestamp": "2026-03-15T10:31:30Z"
  }
}
```

UI requirements:

- Map to blocked or permission-denied states
- Do not display as unknown system error

### Conflict or duplicate-safe error shape

Required shape:

```json
{
  "message": "This transaction has already been finalized.",
  "code": "already_finalized",
  "data": {
    "transaction_id": "TX-2026-000145"
  },
  "meta": {
    "request_id": "2c5a5cbc-7154-45f1-bd02-488de2c75266",
    "timestamp": "2026-03-15T10:32:00Z"
  }
}
```

UI requirements:

- Use state-specific receipt retrieval or redirect behavior
- Do not show a generic retry prompt

## 5. Shared Pagination Contract

The source docs assume list and report scale. All paginated endpoints must use a consistent structure.

Required pagination shape:

```json
{
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 124,
    "has_more": true
  }
}
```

UI requirements:

- Flutter list screens should support append pagination
- Laravel web tables should support page-based navigation
- Empty state should be shown when `data` is empty, while preserving filters

## 6. Shared Enum Guidance

### UI status enums

Approved UI-facing enums:

- `draft`
- `queued`
- `synced`
- `pending_approval`
- `approved`
- `rejected`
- `failed_sync`
- `blocked`
- `finalized`

### Ledger and finance enums from source architecture

Source-backed values include:

- `draft`
- `pending_approval`
- `posted`
- `reversed`
- `rejected`

Rule:

- The UI may map internal finance `posted` to a user-facing completed or approved state where appropriate, but the raw API value should remain authoritative

### Notification delivery enums

Source-backed values include:

- `queued`
- `sent`
- `delivered`
- `failed`

### Payment provider enums if external rails are enabled

Source-backed values include:

- `initiated`
- `pending`
- `success`
- `failed`
- `expired`
- `reversed`
- `chargeback`

## 7. Screen Contracts

### 7.1 Login

Source-backed endpoint:

- `POST /api/v1/auth/login`

Suggested request:

```json
{
  "phone": "+2348012345678",
  "password": "******",
  "device_name": "Samsung A14",
  "locale": "en"
}
```

Suggested success response:

```json
{
  "data": {
    "user": {
      "id": 42,
      "name": "Amina Ibrahim",
      "role": "member",
      "cooperative_id": 8
    },
    "token": "plain-text-or-session-token",
    "requires_mfa": false
  },
  "meta": {
    "request_id": "d36244aa-57ba-4090-a42d-fc9268d7cedb",
    "timestamp": "2026-03-15T10:40:00Z"
  }
}
```

UI notes:

- Login errors should differentiate invalid credentials, blocked account, and MFA-required continuation
- Offline login should only be allowed for previously authenticated users if local session rules permit it

### 7.2 Member Home

Source status:

- No explicit single endpoint in architecture

Recommended aggregation endpoint:

- `GET /api/v1/member-home`

Suggested query params:

- `include=wallet,recent_activity,notifications`

Suggested success response:

```json
{
  "data": {
    "member": {
      "id": 42,
      "name": "Amina Ibrahim",
      "cooperative_name": "Kukawa Dairy Cooperative"
    },
    "summary": {
      "last_milk_log_quantity": 12,
      "wallet_label": "Internal balance",
      "pending_count": 1
    },
    "attention_items": [
      {
        "id": "sync-1",
        "title": "Milk log waiting to sync",
        "status": "queued"
      }
    ],
    "recent_activity": [
      {
        "id": "milk-1001",
        "type": "milk_log",
        "title": "Milk log submitted",
        "status": "synced",
        "timestamp": "2026-03-15T07:42:00Z"
      }
    ]
  }
}
```

UI notes:

- If this endpoint is not introduced, the client will need multiple requests and local aggregation
- Cached local data should render before full network reconciliation on mobile

### 7.3 Log Milk

Source-backed endpoint:

- `POST /api/v1/milk-production`

Source-backed request requirements:

- `client_request_id` or `Idempotency-Key`

Suggested request:

```json
{
  "client_request_id": "c8f3608a-1d0c-49b0-b573-612ba7ca1b40",
  "member_id": 42,
  "quantity_liters": 12,
  "production_date": "2026-03-15",
  "collection_time": "07:30",
  "quality_notes": "Standard morning batch"
}
```

Suggested online success response:

```json
{
  "data": {
    "id": 1001,
    "member_id": 42,
    "quantity_liters": 12,
    "production_date": "2026-03-15",
    "status": "synced",
    "processed_at": "2026-03-15T07:42:00Z"
  },
  "meta": {
    "request_id": "01ef22ed-d88f-4d6e-93d0-af0a7706ab23",
    "timestamp": "2026-03-15T07:42:00Z"
  }
}
```

Suggested local queued model for Flutter:

```json
{
  "local_record": {
    "local_id": "local-milk-1",
    "client_request_id": "c8f3608a-1d0c-49b0-b573-612ba7ca1b40",
    "status": "queued"
  }
}
```

UI notes:

- Offline save should never display server-confirmed language
- Retry should use the same `client_request_id`

### 7.4 Wallet and Payout Request

Source-backed endpoints:

- `GET /finance/accounts/{id}/ledger`
- `POST /finance/withdrawals/request`

Suggested ledger success response:

```json
{
  "data": {
    "account_id": 501,
    "label": "Internal balance",
    "currency": "NGN",
    "current_balance": "12500.00",
    "transactions": [
      {
        "id": "led-1001",
        "entry_type": "marketplace_settlement",
        "status": "posted",
        "amount": "3500.00",
        "timestamp": "2026-03-14T15:20:00Z",
        "reference": "TX-2026-000145"
      }
    ]
  }
}
```

Suggested payout request:

```json
{
  "client_request_id": "efbd63d8-965b-4201-b620-2078a58dd7a7",
  "account_id": 501,
  "amount": "5000.00",
  "destination_type": "internal"
}
```

Suggested payout request success response:

```json
{
  "data": {
    "request_id": 901,
    "status": "pending_approval",
    "amount": "5000.00",
    "submitted_at": "2026-03-15T09:15:00Z"
  }
}
```

UI notes:

- If insufficient balance or rules block the action, return `blocked` or validation-style error with a user-safe message
- Finance UI should map `posted` to a completed display state while preserving raw ledger values internally

### 7.5 Marketplace Browse

Source-backed endpoints:

- `GET /marketplace/listings`
- `POST /marketplace/offers`
- `GET /marketplace/listings/{id}`
- `GET /marketplace/vendors/{id}`

Marketplace contract scope:

- listing responses must support vendor marketplace behavior, not only minimal supply cards
- listing detail must expose commercial metadata, trust indicators, fulfillment method, and permissible actions
- vendor profile responses must expose trust and verification signals without leaking protected finance or governance data

Suggested browse request:

- `GET /marketplace/listings?coop_id=8&item_type=milk&status=published&quality_grade=A&fulfillment_method=pickup&page=1`

Suggested browse response:

```json
{
  "data": [
    {
      "id": 301,
      "title": "Raw milk supply",
      "vendor_id": 88,
      "seller_name": "Kukawa Dairy Cooperative",
      "quantity": 240,
      "item_type": "milk",
      "quality_grade": "A",
      "fulfillment_method": "pickup",
      "availability_window": "2026-03-15 to 2026-03-17",
      "status": "published",
      "trust_flags": [
        "cooperative_endorsed",
        "vendor_verified"
      ]
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 1,
    "has_more": false
  }
}
```

Suggested offer request:

```json
{
  "client_request_id": "afccfc09-8387-4eff-96cb-627e90bc43ac",
  "listing_id": 301,
  "offer_amount": "18000.00",
  "quantity": 240,
  "delivery_terms_summary": "Pickup at collection center",
  "payment_terms_summary": "Internal ledger settlement",
  "expires_at": "2026-03-15T18:00:00Z"
}
```

Suggested offer success response:

```json
{
  "data": {
    "offer_id": 801,
    "listing_id": 301,
    "status": "submitted",
    "offer_chain_position": 1,
    "submitted_at": "2026-03-15T09:45:00Z"
  }
}
```

UI notes:

- `submitted` is a valid offer workflow state even if not one of the simplified UI cross-screen statuses
- If the product wants a unified chip vocabulary, map offer `submitted` to a display label like `Pending decision` while preserving raw value
- listing detail should expose vendor verification and trust badges separately from offer status

Suggested listing detail response:

```json
{
  "data": {
    "id": 301,
    "title": "Raw milk supply",
    "description": "Morning collection from verified member cluster",
    "vendor": {
      "id": 88,
      "display_name": "Kukawa Dairy Cooperative",
      "verification_status": "active",
      "trust_flags": [
        "cooperative_endorsed",
        "vendor_verified"
      ]
    },
    "quantity": 240,
    "unit": "liters",
    "min_order_quantity": 40,
    "quality_grade": "A",
    "pricing_type": "negotiable",
    "fulfillment_method": "pickup",
    "delivery_terms_summary": "Pickup at collection center",
    "status": "published",
    "allowed_actions": [
      "submit_offer"
    ]
  }
}
```

### 7.6 Marketplace Finalization

Source-backed endpoint:

- `POST /marketplace/transactions/finalize`

Source-backed request requirements:

- `Idempotency-Key`
- `listing_id`
- `offer_id`

Suggested request:

Headers:

```json
{
  "Idempotency-Key": "52dbf742-f1e6-4dc7-8aa0-c59395e1d5e0"
}
```

Body:

```json
{
  "listing_id": 301,
  "offer_id": 801
}
```

Suggested success response:

```json
{
  "data": {
    "transaction_id": "TX-2026-000145",
    "listing_id": 301,
    "offer_id": 801,
    "status": "finalized",
    "settlement": {
      "entry_id": 1201,
      "status": "posted",
      "amount": "18000.00"
    },
    "finalized_at": "2026-03-15T10:10:00Z"
  }
}
```

Suggested duplicate-safe response:

```json
{
  "data": {
    "transaction_id": "TX-2026-000145",
    "listing_id": 301,
    "offer_id": 801,
    "status": "finalized",
    "duplicate_of_prior_request": true
  }
}
```

UI notes:

- The frontend should treat both successful initial finalization and idempotent replay as receipt states
- Connectivity failure before request submission should map to `blocked`

### 7.6.1 Marketplace Orders and Fulfillment

Source-backed endpoint family introduced by marketplace expansion:

- `GET /marketplace/orders/{id}`
- `POST /marketplace/orders/{id}/dispatch`
- `POST /marketplace/orders/{id}/confirm-receipt`
- `POST /marketplace/orders/{id}/quality-verify`
- `POST /marketplace/orders/{id}/open-dispute`

Minimum order response:

```json
{
  "data": {
    "order_id": "ORD-2026-0042",
    "listing_id": 301,
    "accepted_offer_id": 801,
    "status": "in_collection",
    "settlement_status": "pending",
    "fulfillment_method": "pickup",
    "vendor": {
      "id": 88,
      "display_name": "Kukawa Dairy Cooperative"
    },
    "timeline": [
      {
        "event_type": "accepted",
        "created_at": "2026-03-15T09:55:00Z"
      },
      {
        "event_type": "reserved",
        "created_at": "2026-03-15T10:00:00Z"
      }
    ],
    "allowed_actions": [
      "confirm_receipt",
      "open_dispute"
    ]
  }
}
```

UI notes:

- order status must be separate from settlement status
- timeline and allowed actions must be server-driven
- disputes must open from order or transaction context, not free-floating marketplace forms

### 7.7 Cooperative Dashboard

Source status:

- No explicit dedicated dashboard endpoint in the architecture

Recommended aggregation endpoints:

- `GET /api/v1/admin/dashboard-summary`
- `GET /api/v1/admin/recent-activity`

Minimum data required:

- KPI summary
- Exceptions counts
- Pending approvals counts
- Recent activity or queue items
- Domain-specific snapshot counts

Suggested summary response:

```json
{
  "data": {
    "kpis": {
      "active_members": 1284,
      "milk_volume_liters": 18420,
      "pending_payouts": 36,
      "open_disputes": 4
    },
    "exceptions": [
      {
        "code": "missing_review",
        "count": 12
      }
    ],
    "approvals": [
      {
        "type": "payout_requests",
        "count": 36
      }
    ]
  }
}
```

UI notes:

- If these endpoints are not introduced, the dashboard will require multiple calls and client aggregation
- Laravel web can still compose this server-side, but the contract should be explicit

### 7.8 Payout Approval Queue

Source-backed endpoint:

- `POST /finance/withdrawals/{id}/approve`

Source-gap note:

- A list endpoint for pending withdrawal requests is needed for the UI but is not explicitly named in the source architecture

Recommended additional endpoints:

- `GET /finance/withdrawals/requests?status=pending_approval`
- `POST /finance/withdrawals/{id}/reject`

Suggested queue response:

```json
{
  "data": [
    {
      "id": 901,
      "member_name": "Amina Ibrahim",
      "amount": "5000.00",
      "status": "pending_approval",
      "submitted_at": "2026-03-15T09:15:00Z"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 1,
    "has_more": false
  }
}
```

Suggested approve response:

```json
{
  "data": {
    "request_id": 901,
    "status": "approved",
    "approved_at": "2026-03-15T10:22:00Z"
  }
}
```

Suggested reject response:

```json
{
  "data": {
    "request_id": 901,
    "status": "rejected",
    "reason": "Insufficient cleared balance",
    "rejected_at": "2026-03-15T10:25:00Z"
  }
}
```

UI notes:

- Rejection must require a reason
- If only one approve endpoint exists, reject behavior still needs a server-side contract before build

### 7.9 Reports and Export

Source-backed endpoint:

- `GET /finance/reports/summary`

Recommended additional endpoints:

- `GET /reports/operations/summary`
- `POST /reports/exports`
- `GET /reports/exports/{id}`

Suggested summary response:

```json
{
  "data": {
    "report_type": "financial",
    "filters": {
      "date_from": "2026-03-01",
      "date_to": "2026-03-15"
    },
    "metrics": {
      "total_withdrawals": "45000.00",
      "total_repayments": "12000.00",
      "pending_payouts": 36
    }
  }
}
```

Suggested export create response:

```json
{
  "data": {
    "export_id": "exp-120",
    "status": "queued"
  }
}
```

Suggested export status response:

```json
{
  "data": {
    "export_id": "exp-120",
    "status": "delivered",
    "download_url": "https://example.test/download/exp-120"
  }
}
```

UI notes:

- The UI should not assume exports are instantly available
- Use queued, processing, delivered, and failed states

## 8. Error Mapping Table

Recommended UI handling by backend response type:

- `422 validation error` -> inline field validation and preserved form input
- `401 unauthenticated` -> redirect to login or session-expired state
- `403 forbidden` -> permission-denied state
- `404 not found` -> missing record state with return path
- `409 conflict` -> state-specific conflict UI such as already finalized
- `429 rate limited` -> retry-later message
- `500+ server error` -> generic error panel with retry and support path

## 9. Locked Core Contracts

The following contracts are mandatory baseline interfaces for Phase 1 and are no longer open design items:

- `GET /api/v1/member/dashboard`
  - returns member summary KPIs, recent activity, wallet summary, and latest notifications in one payload
- `GET /api/v1/admin/dashboard-summary`
  - returns cooperative KPIs, exception counts, pending approvals, and snapshot panels in one payload
- `GET /api/v1/finance/payout-requests?status=pending_approval`
  - returns the payout approval queue with pagination, filters, and scoped actor permissions
- `POST /api/v1/finance/payout-requests/{id}/approve`
  - maker-checker approval only
- `POST /api/v1/finance/payout-requests/{id}/reject`
  - rejection requires reason and creates append-only approval or workflow event
- `POST /api/v1/reports/exports`
  - creates asynchronous export job
- `GET /api/v1/reports/exports/{id}`
  - returns export status and signed download reference when ready
- `GET /api/v1/notifications`
  - returns inbox list with delivery-aware metadata
- `POST /api/v1/notifications/{id}/read`
  - marks a notification as read without changing delivery history
- `POST /api/v1/sync/batch`
  - accepts offline replay batch and returns per-item reconciliation results
- `GET /api/v1/sync/status`
  - returns status and latest reconciliation details for failed or pending sync items

Contract governance rules:

- Path naming is locked at `/api/v1/...` for all new endpoints.
- Response envelope, pagination, validation errors, authorization errors, and conflict error shapes are locked by this document.
- Enums exposed to UI must be versioned here first before implementation changes are released.
- Export generation is asynchronous by default unless explicitly marked lightweight and synchronous.

## 10. Delivery Recommendation

Flutter widget inventory, Laravel component inventory, and folder structures must reference this contract document by version or revision date when implementation starts. They must not define parallel response or enum rules.

## 11. Source-Driven Contract Expansion

This section extends the contract sheet to reflect additional backend architecture explicitly defined in the source system document.

### 11.1 Platform Primitive Contracts

The following platform services should be treated as contract-bearing backend capabilities, not informal implementation details:

- approval engine
- audit engine
- idempotency service
- notification service
- configuration service

Minimum contract expectations:

- every critical write can emit or return a `correlation_id`
- approval-sensitive entities expose latest derived state plus event-history access where appropriate
- notifications expose delivery-aware status
- configuration is scope-aware by `global`, `country`, or `cooperative`

### 11.2 Approval Event Contract

Source-backed architecture requires append-only approval events.

Recommended approval event response shape:

```json
{
  "data": {
    "entity_type": "feed_distribution",
    "entity_id": 501,
    "current_status": "pending_approval",
    "events": [
      {
        "id": 1001,
        "event_type": "submitted",
        "actor_user_id": 44,
        "actor_role_snapshot": "member",
        "reason": "Initial submission",
        "created_at": "2026-03-15T11:20:00Z"
      }
    ]
  }
}
```

UI requirements:

- admin screens can render decision history
- current state must be derived from latest valid event, not mutable fields only

### 11.3 Audit Log Contract

Source architecture requires append-only audit logs with request or correlation context.

Recommended audit event shape:

```json
{
  "data": {
    "correlation_id": "corr-9b26b2e4",
    "actor_user_id": 8,
    "actor_role_snapshot": "coop_admin",
    "entity_type": "withdrawal_request",
    "entity_id": 901,
    "action": "approved",
    "before_summary": {
      "status": "pending_approval"
    },
    "after_summary": {
      "status": "approved"
    },
    "created_at": "2026-03-15T12:10:00Z"
  }
}
```

### 11.4 Notification Contract

Source-backed tables include `notifications` and `notification_deliveries`.

Required endpoints:

- `GET /notifications`
- `GET /notifications/{id}`
- `POST /notifications/{id}/read`

Recommended response shape:

```json
{
  "data": [
    {
      "id": 4001,
      "notification_type": "finance",
      "title_key": "wallet.payout.pending",
      "body_key": "wallet.payout.pending.body",
      "payload": {
        "request_id": 901
      },
      "delivery_status": "delivered",
      "created_at": "2026-03-15T12:20:00Z"
    }
  ]
}
```

### 11.5 Configuration Contract

Source-backed configuration entities:

- `config_keys`
- `config_values`
- `config_change_events`

Required endpoints:

- `GET /config/values`
- `POST /config/values`
- `POST /config/values/{id}/activate`
- `GET /config/change-events`

Minimum response expectations:

- scope type
- scope id
- current version
- active value
- audit trail

### 11.6 Case and Dispute Contract

Source architecture requires unified case management for disputes, reversals, chargebacks, holds, and regulatory issues.

Required endpoints:

- `GET /cases`
- `POST /cases`
- `GET /cases/{id}`
- `POST /cases/{id}/events`
- `POST /cases/{id}/evidence`

Recommended case response shape:

```json
{
  "data": {
    "id": 710,
    "status": "under_review",
    "case_type": "marketplace_dispute",
    "reference_type": "marketplace_transaction",
    "reference_id": "TX-2026-000145",
    "sla_due_at": "2026-03-18T12:00:00Z"
  }
}
```

### 11.7 Fraud Alert Contract

Source architecture adds `fraud_alerts` and `fraud_alert_events`.

Required endpoints:

- `GET /fraud-alerts`
- `GET /fraud-alerts/{id}`
- `POST /fraud-alerts/{id}/resolve`

UI requirement:

- fraud is admin-only or auditor-only scope
- alert state should not be exposed to normal members

### 11.8 Feed and Operations Approval Contracts

The earlier contract focused heavily on milk, wallet, and payouts. Source scope also requires feed and operational approval flows.

Source-backed endpoints:

- `POST /api/v1/feed-distributions`
- `GET /api/v1/feed-inventory`

Recommended additions:

- `GET /api/v1/feed-distributions?status=pending_approval`
- `POST /api/v1/feed-distributions/{id}/approve`
- `POST /api/v1/feed-distributions/{id}/reject`
- `GET /api/v1/approvals/operations`

Recommended approval success response:

```json
{
  "data": {
    "entity_type": "feed_distribution",
    "entity_id": 501,
    "status": "approved",
    "approved_at": "2026-03-15T13:15:00Z"
  }
}
```

### 11.9 Role-Specific Dashboard Contracts

The architecture defines member, cooperative, and processor dashboards.

Required aggregation endpoints:

- `GET /api/v1/member/dashboard`
- `GET /api/v1/admin/dashboard-summary`
- `GET /api/v1/processor/dashboard`

Each should return:

- summary KPIs
- scoped pending items
- recent activity or recent records
- role-appropriate notices

### 11.10 Offline Reconciliation Contract

Source architecture requires explicit offline sync and reconciliation behavior.

Required endpoints:

- `POST /api/v1/sync/batch`
- `GET /api/v1/sync/status`

Suggested batch response:

```json
{
  "data": {
    "processed": 8,
    "failed": 2,
    "results": [
      {
        "client_request_id": "c8f3608a-1d0c-49b0-b573-612ba7ca1b40",
        "entity_id": 1001,
        "status": "synced"
      },
      {
        "client_request_id": "bad-2",
        "status": "failed_sync",
        "reason": "Validation failed"
      }
    ]
  }
}
```

Server-authoritative reconciliation rules:

- The server is the source of truth for final state, timestamps, actor authority, and conflict resolution.
- Batch items must be processed independently and returned in the same order received for deterministic client replay.
- Each result must include one of: `synced`, `duplicate`, `rejected`, `conflict`, `blocked`, or `failed_sync`.
- `duplicate` means the same `client_request_id` was already committed and the original entity reference is returned.
- `conflict` means the request was validly formed but cannot be applied without user or admin resolution.
- `blocked` means the action belongs to an online-only class and must not be replayed offline.
- Every `conflict` result must include:
  - `conflict_code`
  - `server_state_summary`
  - `resolution_required`
  - `resolution_options`
- Conflict precedence is locked as follows:
  - approved or posted server state overrides unsynced mobile edits
  - admin-authorized reversal or override overrides member-submitted state
  - no client may silently overwrite approved, posted, or reversed records
- Admin conflict resolution must create append-only audit and domain events with reason, actor, and correlation context.

### 11.11 Payment Provider Contract

If external rails are enabled, add:

- `POST /payments/provider/initiate`
- `GET /payments/provider/{id}`
- webhook ingestion endpoint

Status values must match source architecture:

- `initiated`
- `pending`
- `success`
- `failed`
- `expired`
- `reversed`
- `chargeback`

### 11.12 Contract Closure Status

The following interfaces are considered locked by this document and must be implemented rather than re-designed:

- notification list and read contract
- processor, member, and admin dashboard aggregation contracts
- sync batch reconciliation contract
- payout approval queue contract

The following remain extensible but not undefined:

- case management payload detail
- fraud alert payload detail
- configuration management payload detail
- operational approval queue filtering detail across dairy and feed domains
