# LMCP Implementation Roadmap

## 1. Purpose

This roadmap defines the full implementation path for LMCP based on the source architecture and design documents, not only the UI handoff artifacts.

It is aligned to:

- `LMCP_System_Architecture_Document.md`
- `LMCP_UI_API_Contract.md`
- `LMCP_UI_Handoff_Spec.md`
- `LMCP_Authorization_Matrix.md`
- `LMCP_Offline_Reconciliation_Spec.md`
- `LMCP-Design.md`
- the derived inventory and structure documents in `docs/`

This plan covers:

- domain-by-domain backend delivery
- Flutter mobile implementation
- Laravel web and admin implementation
- platform primitives
- data model and ledger rollout
- security, deployment, observability, and compliance readiness
- phased delivery from MVP to pilot to national-scale readiness

## 2. Source-Driven Constraints

The roadmap is shaped by the following architecture mandates:

- Flutter mobile and Laravel web backed by PostgreSQL
- modular monolith backend with domain boundaries
- offline-first mobile with server authority
- idempotency mandatory for sync-write and commit flows
- append-only approval event streams for sensitive approvals
- double-entry ledger as mandatory finance source of truth at scale
- marketplace and finance finalization must commit atomically
- maker-checker and separation-of-duties for finance-sensitive operations
- role-based and scope-based access control
- country-bound data isolation
- notification service, queue workers, and audit logging as core platform capabilities
- multilingual support in English, French, and Hausa in Phase 1
- authorization rules enforced through the LMCP authorization matrix
- offline replay behavior enforced through the LMCP offline reconciliation specification

## 3. System Delivery Model

LMCP should not be implemented as a flat list of screens. It should be delivered in five parallel architecture layers:

- Layer A: platform foundations
- Layer B: core operational domains
- Layer C: finance and marketplace integrity
- Layer D: mobile and web user experience
- Layer E: operations, compliance, and scale readiness

Each phase below combines work across those layers so the system evolves as a coherent product.

## 4. Workstreams

### Workstream A: Platform primitives

Core scope:

- authentication and role routing
- RBAC and scoped authorization
- idempotency service
- approval engine
- audit engine
- notification service
- configuration and reference data service
- localization infrastructure
- offline synchronization framework

### Workstream B: Cooperative and structure domain

Core scope:

- cooperatives
- clusters
- members
- assignments
- role scoping
- cooperative governance records

### Workstream C: Dairy and feed operations

Core scope:

- dairy animals
- milk production logs
- feed inventory
- feed distributions
- ops approvals and corrections
- operational dashboards and aggregates

### Workstream D: Vet, paravet, and extension services

Core scope:

- service assignments
- visit reports
- production validation
- health and nutrition recommendations
- advisory and training reporting

### Workstream E: Marketplace domain

Core scope:

- vendor management and verification
- listings and catalog-style commercial metadata
- offers and counter-offer chains
- order and fulfillment tracking
- transaction finalization
- dispute and case management
- optional payment provider integration

### Workstream F: Cooperative finance domain

Core scope:

- accounts
- ledger and balance views
- withdrawals and approvals
- contributions
- repayments
- loans and dividends if enabled
- holds and reversals

### Workstream G: Web and mobile product surfaces

Core scope:

- mobile landing, auth, member, processor, and role-based screens
- web landing, admin dashboard, approvals, reports, and governance screens

### Workstream H: Deployment, observability, and compliance

Core scope:

- VPS deployment baseline
- queues and workers
- monitoring and alerting
- backups and restore drills
- fraud monitoring
- country-bound data isolation
- data privacy controls

## 5. Phase Plan

## 5.1 Phase 0: Architecture Closure and Delivery Setup

Objective:

- lock implementation-critical decisions before feature build begins

Mandatory outputs:

- approve repo strategy: mono-repo or split repos
- scaffold Flutter and Laravel projects using approved folder structures
- adopt the ratified API envelope, pagination, validation error shape, authorization error shape, and conflict error shape from `LMCP_UI_API_Contract.md`
- adopt the enum catalog for statuses, approval events, finance states, notification delivery, payment provider states, and case states from `LMCP_UI_API_Contract.md`
- confirm Flutter state management choice: `Provider` or `Riverpod`
- confirm Laravel admin interaction model: Blade-only, Livewire, or equivalent
- lock finance write path: `gl_journal_entries` and `gl_journal_lines` are the only writable ledger; `finance_transactions` and `finance_balances` are read-only derived projections if retained
- lock processor interaction model: processors transact against cooperative-scoped listings and offers; settlement, disputes, and authorization remain cooperative-scoped with no direct bypass around cooperative governance
- lock processor onboarding rule: processor access requires verification and country or cooperative approval before settlement-capable actions are enabled
- define release engineering policy: CI gates, migration sequencing, rollback expectations, and deployment approval path
- define environment policy: dev, test, staging, pilot, and production separation with masking rules for copied data
- approve `LMCP_Authorization_Matrix.md` as the authoritative policy source for scoped authorization behavior
- approve `LMCP_Offline_Reconciliation_Spec.md` as the authoritative sync replay and conflict-handling source

Architecture dependencies closed in this phase:

- member home aggregation strategy
- cooperative dashboard aggregation strategy
- payout request queue contract
- rejection endpoint contract for payout decisions
- sync center reconciliation contract
- reports export lifecycle contract
- authorization matrix for country, cooperative, cluster, member, processor, finance, and admin scopes
- environment promotion model and rollback playbook for schema-affecting releases
- link Phase 0 implementation tasks to `LMCP_Authorization_Matrix.md` and `LMCP_Offline_Reconciliation_Spec.md`

Exit criteria:

- engineering signoff on architecture assumptions
- source-of-truth docs versioned and approved
- starter repos and CI in place

## 5.2 Phase 1: Core Platform Foundations

Objective:

- build the platform primitives all domains depend on

Backend scope:

- Sanctum auth
- user, role, and `user_roles`
- country and cooperative scoping middleware or global scopes
- idempotency table and service
- audit log table and append-only audit service
- approval event stream foundation
- notification and notification delivery tables
- configuration tables and configuration change events
- base queue setup and worker configuration
- privileged-access foundation: MFA, trusted-device enrollment, session revocation, and secret rotation baseline for privileged actors

Mobile scope:

- app bootstrap
- localization setup with English, French, Hausa ARB files
- encrypted local storage
- local SQLite database
- offline queue store
- connectivity service
- shared theme, status chips, error states, empty states

Web scope:

- public shell
- admin shell
- shared status chip
- shared table/filter components
- permission-aware navigation

Acceptance criteria:

- idempotent write support exists at framework level
- every request can emit `correlation_id`
- queue workers can process notification and async jobs
- localized strings can be served across Flutter and Laravel
- privileged roles cannot access admin or finance-sensitive workflows without MFA and trusted-device checks

## 5.3 Phase 2: Cooperative Structure and Identity Foundation

Objective:

- enable the organizational model that the rest of the system depends on

Backend scope:

- cooperatives
- clusters
- members
- assignment management
- cooperative profile APIs
- cluster member APIs
- member onboarding APIs

Frontend scope:

- public landing
- mobile landing
- login screen
- role routing
- onboarding entry points
- admin member creation and field-assisted onboarding flow entry points

Security scope:

- role and scope checks for member and cooperative access
- privileged action logging

Acceptance criteria:

- users can authenticate and be routed by role
- cooperatives, clusters, and members can be created and viewed in scoped contexts
- onboarding paths exist for self-signup, admin-created member, and field-assisted signup

## 5.4 Phase 3: Dairy Production MVP with Offline-First Sync

Objective:

- deliver the first high-frequency operational workflow end-to-end

Backend scope:

- `milk_production_logs`
- dairy production create endpoint
- cluster production read endpoint
- idempotent replay handling for milk production
- validation rules for quantity, scope, and authority
- operational aggregation jobs for dashboard summaries

Mobile scope:

- Member Home
- Log Milk screen
- recent activity
- queued, synced, failed sync, and rejected states
- sync center basics
- last sync timestamp and retry

Web scope:

- admin or cooperative review of milk records if included in early ops view

Offline model requirements:

- milk production is Class A: offline-queueable
- local record saved with pending or queued state
- server remains final authority
- replay uses same `client_request_id`

Acceptance criteria:

- member can record milk offline
- queued records replay safely without duplication
- sync reconciliation updates local state with authoritative server response
- cooperative-level aggregate output becomes visible

## 5.5 Phase 4: Feed and Nutrition Operations

Objective:

- add feed distribution tracking and approval workflow

Backend scope:

- `feed_types`
- `feed_inventory`
- `feed_distributions`
- feed create and inventory read APIs
- approval events for feed decisions
- SoD enforcement for feed approvals

Mobile scope:

- feed request or feed confirmation screens
- pending approval and approval result states

Web scope:

- feed operations queue
- approval decision UI
- inventory visibility

Architecture notes:

- feed distribution is operational but approval-sensitive
- where configured, approver may be Coop Admin or Vet, but submitter cannot approve own item

Acceptance criteria:

- feed distributions can be created with idempotent writes
- approval decisions are append-only
- pending work can be surfaced in admin queues

## 5.6 Phase 5: Vet, Paravet, and Extension Services

Objective:

- implement field services and validation roles

Backend scope:

- veterinary officers
- paravets
- extension workers
- service assignments
- visit reports
- health and nutrition data capture
- validation and override logic where approved by role

Mobile scope:

- vet dashboard
- paravet dashboard
- extension worker dashboard
- service entry and visit reports

Web scope:

- services management
- assignments
- reports on visits and validations

Acceptance criteria:

- service roles can only access assigned scopes
- visit and advisory records can be captured and audited
- role-specific mobile flows route correctly

## 5.7 Phase 6: Finance Foundation and Ledger Architecture

Objective:

- establish the finance layer before exposing high-trust financial UX broadly

Mandatory backend scope:

- `gl_accounts`
- `gl_journal_entries`
- `gl_journal_lines`
- `gl_entry_events`
- `gl_account_balances`
- optional `gl_holds` if payout holds are enabled in Phase 1
- posting workflow with row locks
- balancing rule enforcement
- reversal model
- maker-checker workflow for withdrawals and similar disbursement actions

Transitional scope if needed:

- read-only compatibility projections for `finance_transactions` and `finance_balances`

Web scope:

- finance read views sufficient for audit and verification

Mobile scope:

- wallet display can consume derived ledger views, not write directly to finance tables

Acceptance criteria:

- posted finance entries are append-only and balanced
- posting is idempotent and concurrency-safe
- balances are recomputable from posted lines
- maker-checker workflow exists for sensitive postings

## 5.8 Phase 7: Wallet, Withdrawals, and Payout Requests

Objective:

- expose member-facing finance initiation on top of a safe backend foundation

Backend scope:

- account ledger API
- withdrawal request API
- pending withdrawal request queue
- approve or reject decision APIs
- holds policy if payout windows are enabled

Mobile scope:

- wallet screen
- payout request UI
- pending approval and rejection handling
- receipt and transaction status visibility

Web scope:

- payout approval screen
- audit summary panel
- rejection with comment

Architecture notes:

- withdrawal request is Class B when captured offline: may be drafted but not committed offline
- approval or posting is Class C: online-only commit

Acceptance criteria:

- members can view internal balance and ledger history
- payout request enters `pending_approval`
- treasurer or coop admin can approve or reject under SoD rules
- receipt and audit visibility are present

## 5.9 Phase 8: Marketplace Core

Objective:

- implement vendor-ready marketplace foundations: vendors, listings, offers, moderation, and order-ready data structures

Backend scope:

- vendors
- vendor profiles
- vendor verification workflow
- marketplace listings
- marketplace offers with immutable writes
- offer decision event stream
- listing events
- listing moderation events
- listing browse and filter APIs
- listing detail API
- scope validation and seller or delegate decision rights
- pricing type, quality, availability, and fulfillment metadata on listings
- order-ready schema reservation for marketplace orders and fulfillment events even if Phase 1 keeps finalization tightly coupled

Mobile scope:

- marketplace browse
- listing detail
- offer submission
- my requests or offers
- vendor trust and listing detail visibility

Web scope:

- marketplace moderation
- vendor review and verification queue
- listing management

Offline classification:

- listing create and offer submit are Class A offline-queueable writes
- finalization remains deferred to later phase and is Class C online-only

Acceptance criteria:

- listings can be created and browsed
- vendor verification state exists and gates settlement-capable processor access
- offers are immutable once submitted
- offer decisions are append-only and auditable
- listings carry enough commercial metadata for operational decision-making

## 5.10 Phase 9: Marketplace Finalization and Atomic Settlement

Objective:

- implement the highest-risk workflow in the system correctly

Mandatory backend scope:

- `POST /api/v1/marketplace/transactions/finalize`
- marketplace order creation or reservation context on accepted offer
- unique constraints for listing, offer, and idempotency key
- exact lock ordering and transaction boundary rules
- atomic insert of marketplace transaction plus GL posting plus balance update
- already-finalized and duplicate-safe replay behavior
- insufficient funds and invalid state handling
- audit event emission with `correlation_id`
- order or fulfillment status linkage to finalization outcome

Mobile and web scope:

- finalization review screen
- blocked offline state
- success receipt with transaction and settlement references
- already-finalized state handling
- order detail and next-step visibility after finalization

Acceptance criteria:

- same `Idempotency-Key` can be retried without duplicate settlement
- concurrent finalization attempts do not create double credit
- no half-sold state is possible
- success always returns durable receipt data
- accepted offers create a durable commercial context for downstream fulfillment or dispute handling

Go-live tests required before enabling this in production:

- same key submitted repeatedly -> one transaction
- concurrent finalize -> one success, one already finalized
- forced error mid-transaction -> no listing sold and no ledger update
- authorization check -> invalid actor cannot finalize

## 5.11 Phase 10: Disputes, Holds, and Optional External Payments

Objective:

- add marketplace fulfillment safety, dispute controls, and external payment readiness

Backend scope:

- unified case model
- case events
- case evidence
- hold policies via `gl_holds`
- optional payout hold windows
- marketplace orders
- marketplace order events
- marketplace fulfillment events
- receipt confirmation and quality verification events
- dispute classification for short delivery, quality mismatch, no-show, payment dispute, and listing fraud

Optional payment rail scope:

- payment provider transactions
- payment provider events
- webhook idempotency
- reconciliation job
- state machine from initiated to chargeback

Frontend scope:

- dispute queue
- case detail views
- hold status visibility where appropriate
- order progress and fulfillment status views
- dispute creation from order or transaction context

Acceptance criteria:

- disputes can be opened, reviewed, escalated, and resolved
- order and fulfillment state is visible end-to-end
- payment-provider-backed flows gate settlement on provider success
- holds affect available balance correctly

### 5.11A Marketplace Phase 1 Implementation Blueprint

This subsection is the implementation-ready blueprint for Phase 1 marketplace delivery.

Phase 1 marketplace is a controlled cooperative vendor marketplace supporting:

- verified vendor participation
- vendor-scoped listings with commercial metadata
- immutable offers and append-only decisions
- cooperative-governed moderation
- atomic transaction finalization with finance settlement
- order-aware post-acceptance tracking
- dispute initiation from order or transaction context

Phase 1 intentionally excludes:

- open public vendor settlement
- external vendor classes beyond approved cooperative marketplace actors
- free-form auction mechanics
- true partial fill execution
- multi-order line splitting
- dynamic pricing engines

Implementation assumptions:

- `gl_journal_entries` and `gl_journal_lines` remain the only writable finance source of truth
- processor users participate only within approved procurement scope
- Phase 1 remains `full-fill only`, but order records still exist as first-class entities
- internal ledger settlement is the default; external payment rails are optional

Marketplace subdomains for implementation:

- Vendor Management
- Listings
- Negotiation
- Orders
- Settlement
- Governance

Required backend entities in Phase 1:

- vendors
- vendor profiles
- vendor verifications
- marketplace listings
- listing events
- marketplace offers
- marketplace offer decisions
- marketplace orders
- marketplace order events
- marketplace fulfillment events
- marketplace transactions

Required Laravel module grouping:

- `Domain/Marketplace/VendorManagement`
- `Domain/Marketplace/Listings`
- `Domain/Marketplace/Offers`
- `Domain/Marketplace/Orders`
- `Domain/Marketplace/Settlement`
- `Domain/Marketplace/Moderation`
- `Domain/Marketplace/Disputes`

Required Laravel surface:

- controllers:
  - `VendorsController`
  - `ListingsController`
  - `OffersController`
  - `OrdersController`
  - `TransactionsController`
  - `MarketplaceModerationController`
- services:
  - `VendorVerificationService`
  - `MarketplaceListingService`
  - `MarketplaceOfferService`
  - `MarketplaceOrderService`
  - `MarketplaceFinalizationService`
  - `MarketplaceDisputeService`
  - `MarketplaceTrustBadgeService`
- policies:
  - `VendorPolicy`
  - `MarketplaceListingPolicy`
  - `MarketplaceOfferPolicy`
  - `MarketplaceOrderPolicy`
  - `MarketplaceTransactionPolicy`

Required API surface in Phase 1:

- `GET /api/v1/marketplace/vendors/{id}`
- `POST /api/v1/marketplace/vendors/applications`
- `POST /api/v1/marketplace/vendors/{id}/approve`
- `POST /api/v1/marketplace/vendors/{id}/suspend`
- `POST /api/v1/marketplace/listings`
- `GET /api/v1/marketplace/listings`
- `GET /api/v1/marketplace/listings/{id}`
- `POST /api/v1/marketplace/listings/{id}/publish`
- `POST /api/v1/marketplace/listings/{id}/cancel`
- `POST /api/v1/marketplace/listings/{id}/moderate`
- `POST /api/v1/marketplace/offers`
- `POST /api/v1/marketplace/offers/{id}/decide`
- `GET /api/v1/marketplace/offers/{id}`
- `GET /api/v1/marketplace/orders/{id}`
- `POST /api/v1/marketplace/orders/{id}/confirm-receipt`
- `POST /api/v1/marketplace/orders/{id}/quality-verify`
- `POST /api/v1/marketplace/orders/{id}/open-dispute`
- `POST /api/v1/marketplace/transactions/finalize`

Required Flutter scope in Phase 1:

- browse published listings
- view listing detail
- submit offer
- view own offers or requests
- finalize accepted offer where authorized
- track order status after acceptance or finalization
- open dispute from order context

Required mobile states:

- `MarketplaceBrowseUiState`
- `MarketplaceListingDetailUiState`
- `MarketplaceFinalizeUiState`
- `MarketplaceOrderUiState`

Offline classification for marketplace:

- Class A:
  - listing create
  - offer submit
- Class B:
  - dispute draft capture
- Class C:
  - offer decision
  - vendor approval or suspension
  - listing moderation decision
  - transaction finalization
  - settlement posting
  - authoritative receipt confirmation

Critical transaction rules:

- one accepted offer may produce only one order
- one order may produce only one final settlement transaction in Phase 1
- finalization must lock listing, accepted offer, and impacted GL balance rows in deterministic order
- if finalization fails at any point, there must be:
  - no marketplace transaction
  - no posted GL entry
  - no settlement status change
  - no sold listing state

Required Phase 1 testing:

- listing create
- offer submit
- moderation decision
- vendor approval gating
- finalization idempotency
- double-finalization race protection
- duplicate order creation protection
- cancellation vs finalization race
- order tracking and dispute initiation

Phase 1 marketplace trade-off decision:

- do not implement a “listing-offer-finalize only” marketplace
- implement vendor and order primitives in Phase 1 even if operations remain constrained
- keep partial fills out of Phase 1, but preserve schema and workflow room for later expansion

## 5.12 Phase 11: Admin Dashboards, Reports, and Analytics

Objective:

- deliver operational and governance visibility for cooperative and processor roles

Backend scope:

- dashboard aggregation jobs
- PostgreSQL views or scheduled aggregates for KPIs
- cooperative dashboard summaries
- member dashboard summaries
- processor dashboard summaries
- financial report summaries
- export jobs

Web scope:

- cooperative dashboard
- approvals and exception panels
- reports page
- export center
- saved filters where needed

Mobile scope:

- lightweight member or processor summaries only where justified

Acceptance criteria:

- dashboards load with query-backed indexes
- filters and exports work reliably
- exports are audit-logged

## 5.13 Phase 12: Configuration, Governance, and Country Isolation

Objective:

- complete governed behavior required for multi-cooperative and multi-country deployment

Backend scope:

- configuration keys and values
- config change events
- scope-aware configuration resolution
- approval authority rules
- maker-checker thresholds
- fee policy and rounding
- payout hold policy
- language and notification templates
- country-bound data access controls

Web scope:

- configuration admin screens
- approval policy screens
- audit and compliance pages

Acceptance criteria:

- config changes are audited and scoped
- country and cooperative isolation is enforced in queries and reports
- no cross-country leakage exists in dashboard or report layers

## 5.14 Phase 13: Advanced Fraud Monitoring and Operational Hardening

Objective:

- bring the system to pilot-grade security and operational readiness

Security scope:

- admin impersonation controls if enabled
- encrypted local mobile storage validation
- HTTPS enforcement verification
- secret rotation audit and recovery drills

Fraud scope:

- fraud alerts
- fraud alert events
- anomaly rules for repeated reversals, failed approvals, unusual settlement volumes, excessive idempotency collisions, and rapid withdrawals after sale

Observability scope:

- request, error, queue, and DB metrics
- centralized logging strategy
- alert thresholds
- queue backlog monitoring

Acceptance criteria:

- privileged role controls are enforceable
- fraud alerts can be generated and reviewed
- operational dashboards exist for API, queue, and DB health

## 5.15 Phase 14: Deployment, Backup, Restore, and Pilot Readiness

Objective:

- prepare the system for pilot deployment and recovery assurance

Infrastructure scope:

- pilot VPS deployment
- separate staging and pilot environments before production rollout
- Nginx, PHP-FPM, PostgreSQL
- Redis for queues
- minimum 2 worker processes
- cron scheduling

Data resilience scope:

- daily backups
- WAL or equivalent continuous recovery support
- restore drills
- post-restore balance reconciliation
- uniqueness and integrity verification after restore
- named operational owner for restore drills and incident evidence retention

Acceptance criteria:

- pilot environment meets minimum spec
- restore drill passes
- signed reconciliation report available after restore
- deployment pipeline enforces tests, migration safety checks, and environment promotion rules

## 6. UI and UX Delivery Roadmap

The design document adds important sequencing constraints that need to be respected during implementation.

### 6.1 Public and onboarding surfaces

Deliver in this order:

- public landing
- how it works
- trust and governance
- login
- language selector
- role-aware onboarding chooser

Reason:

- trust-first public entry is central to adoption
- onboarding must be mobile-first and low-literacy-friendly

### 6.2 Member app sequence

Deliver in this order:

- Home or Today
- Log Milk
- Milk history and receipts
- Feed request or confirmation
- Service request
- Wallet
- Marketplace browse and listing detail
- Offer submission and my offers
- Marketplace order tracking
- Notifications
- Profile and settings
- Sync center

Reason:

- design explicitly prioritizes action-first daily operations over navigation depth

### 6.2A Processor app sequence

Deliver in this order:

- Processor home
- Marketplace browse and vendor trust visibility
- Listing detail and offer submission
- My offers and accepted offer review
- Marketplace finalization
- Marketplace order tracking
- Dispute initiation and status
- Notifications

Reason:

- processor experience is now a first-class marketplace delivery surface, not a later add-on

### 6.3 Admin web sequence

Deliver in this order:

- dashboard
- pending approvals
- milk and feed operations
- marketplace moderation
- vendor review and verification
- marketplace orders and disputes
- finance and payout approvals
- reports and exports
- configuration
- audit and compliance

Reason:

- design prioritizes exception-first, domain-based navigation to avoid unusable mega dashboards

## 7. Cross-Cutting Build Requirements

These must be implemented as shared capabilities, not feature-specific shortcuts.

### 7.1 Offline classification enforcement

Class A:

- milk logs
- field visit reports
- listing create
- offer submit

Client behavior:

- save locally
- auto retry

Server behavior:

- idempotent accept and replay-safe

Class B:

- withdrawal request drafts
- dispute draft capture

Client behavior:

- save as pending request

Server behavior:

- require online confirmation before commit

Class C:

- GL posting
- marketplace finalization
- approvals and maker-checker decisions

Client behavior:

- block offline

Server behavior:

- enforce online-only atomic commit

### 7.2 Event and audit model

Every critical workflow should emit:

- `correlation_id`
- actor identity and role snapshot
- entity type and entity id
- event or action type
- UTC timestamp

### 7.3 Localization governance

Required in Phase 1:

- English
- French
- Hausa

Required implementation rules:

- no hardcoded user-facing strings
- backend validation and notification messages use translation keys
- mobile bundles language resources offline

## 8. Sequencing Risks

The roadmap will stall if these are not handled early:

- ledger design delayed while wallet UI proceeds
- marketplace finalization built before atomic settlement infrastructure
- dashboard UI built before aggregate query design and indexes
- payout UI built before SoD and maker-checker rules are enforceable
- offline UX built before idempotency and reconciliation are implemented
- export UI built before queue and async export model

## 9. Team Plan

Recommended team split:

- Backend platform engineer:
  - auth
  - idempotency
  - audit
  - config
  - queues
- Backend domain engineer 1:
  - cooperative
  - dairy
  - feed
  - services
- Backend domain engineer 2:
  - marketplace
  - finance
  - reports
  - cases and payment provider
- Flutter engineer:
  - app shell
  - offline stack
  - member and processor mobile surfaces
  - marketplace order tracking flows
- Laravel engineer:
  - public site
  - admin shell
  - dashboard
  - approvals
  - reports
- QA and product validation:
  - workflow tests
  - low-connectivity test plans
  - multilingual and accessibility review
  - finance and audit verification

## 10. Suggested Milestones

Milestone 1:

- architecture closure
- repo scaffolds
- shared primitives
- auth and localization foundation

Milestone 2:

- cooperative structure
- member onboarding
- milk logging with offline sync

Milestone 3:

- feed workflows
- field services roles
- operational approvals

Milestone 4:

- finance ledger foundation
- wallet
- payout requests and approvals

Milestone 5:

- vendor verification
- marketplace listings and offers
- marketplace orders and order tracking
- atomic finalization
- disputes and holds

Milestone 6:

- dashboards
- reports
- exports
- configuration and compliance

Milestone 7:

- security hardening
- fraud monitoring
- pilot deployment and restore validation

## 11. Definition of Done

A feature is only complete when:

- its API contract is stable
- permissions and scope rules are enforced
- loading, empty, error, success, and blocked states exist
- audit and correlation behavior is implemented where required
- localization keys exist
- accessibility rules are met
- tests cover the happy path and key failure path

High-trust workflows are only complete when:

- idempotency is proven
- receipt state exists
- append-only history is visible where required
- rollback and reversal behavior has been tested

## 12. Immediate Next Actions

The most practical next actions are:

1. execute Phase 0 decision closure: repo strategy, Flutter state management, Laravel admin interaction model, release policy, and environment policy
2. scaffold the Flutter and Laravel projects using the approved folder structures
3. implement shared platform primitives first: auth, scoped authorization, status enums, idempotency service, offline reconciliation, audit logging, and shared UI primitives
4. begin the first full vertical slice: login, role routing, Member Home, Log Milk offline queueing, sync replay, and admin visibility of resulting production data
5. treat ledger, payout approval, vendor verification, and marketplace finalization as architecture milestones, not just feature tickets
