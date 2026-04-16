System Architecture Document (SAD)
Livestock Cooperative, Dairy Production, and Feed & Nutrition Management System

Companion implementation-control documents:
	•	LMCP_Authorization_Matrix.md — authoritative action, scope, and privileged-access policy mapping
	•	LMCP_Offline_Reconciliation_Spec.md — authoritative offline replay, conflict, and sync reconciliation behavior

1. Executive Summary
This document describes the system architecture for a Livestock Cooperative Management System designed for deployment across the Lake Chad Basin, with an initial focus on country-bound implementations. The system is intended to digitally manage livestock cooperatives, cluster-based member structures, dairy production, and feed and nutrition distribution, while operating reliably in low-connectivity, low-resource environments.
The architecture prioritizes simplicity, cost efficiency, offline capability, and open-source technologies, making it suitable for phased deployment on VPS or shared hosting infrastructure, with future scalability options.

2. System Objectives & Scope
2.1 Primary Objectives
	•	Digitize and manage cooperative structures, assets, and governance
	•	Track and aggregate dairy production at member, cluster, and cooperative levels
	•	Manage feed and nutrition inventory and distribution to cooperative members
	•	Support veterinary, paraveterinary, and extension services for validation and advisory roles
2.2 System Scope (Expanded)
The system SHALL manage cooperative livestock operations including:
	•	Cooperative, cluster, and member hierarchy
	•	Animal registration and lifecycle tracking
	•	Milk production logging and approvals
	•	Feed distribution tracking
	•	Veterinary and extension services records
	•	Offline-first mobile data capture
	•	Multi-language support
Inclusion: IoT-Based Sensing and Automation (Defined)
The platform SHALL be architecturally prepared (Phase 2+) to integrate IoT-based sensing and automation, defined as:
The deployment of connected physical devices (sensors, meters, controllers) that automatically collect, transmit, and optionally act upon livestock and production data without manual data entry.
Examples within livestock context:
	•	Milk volume sensors at collection points
	•	Smart weighing scales
	•	Animal health wearables (temperature/activity monitors)
	•	Feed silo level sensors
	•	Cold-chain temperature monitors
	•	Automated milk chilling activation based on thresholds
Technical Provision:
	•	Device-to-API ingestion endpoints
	•	Secure token-based device authentication
	•	Time-series storage support
	•	Event-triggered alerts (e.g., abnormal temperature)
	•	Non-dependency on IoT for core operations (manual entry remains primary in Phase 1)
IoT Governance (Phase 2+)When IoT is enabled, the system SHALL provide:
	•	device onboarding and inventory registry
	•	per-device authentication credentials with rotation policy
	•	rate limiting per device and per cooperative
	•	validation rules for sensor payloads (schema + bounds)
	•	retention policy for time-series data (by type)
	•	alert policies tied into Notification Service

This ensures the system remains future-proof while remaining cost-conscious during pilot deployment.

3. Operating Context: Lake Chad Basin
The system is designed with the following realities in mind:
	•	Intermittent or poor internet connectivity
	•	Predominantly Android mobile devices
	•	Low digital literacy among end users
	•	Multi-language environment
	•	Security-sensitive regions
Design Implication: An offline-first mobile application with server-side authority and validation workflows.

4. Stakeholders & User Roles
4.1 User Roles
	•	Super Administrator
	•	Country Administrator
	•	Cooperative Administrator
	•	Cluster Supervisor
	•	Member (Farmer/Herder)
	•	Veterinary Officer
	•	Paraveterinary Officer
	•	Extension Worker
	•	Processor User (Read/Input only)
	•	Auditor / Observer (Read-only)
All members are registered under clusters, and all clusters belong to a cooperative.

5. Technology Stack
5.1 Selected Stack (Initial Phase)
Layer
Technology
Mobile App
Flutter (Android-first)
Backend
Laravel (PHP)
API
RESTful JSON APIs
Database
Postgresql
Authentication
Laravel Sanctum
Hosting
VPS or Shared Hosting
This stack was selected for:
	•	Low operational cost
	•	Large local talent pool
	•	Open-source maturity
	•	Ease of maintenance
5.2 Database Engine (Authoritative Decision)
	•	The system SHALL use PostgreSQL (15+) as the primary relational database for all environments.
	•	The system SHALL NOT support mixed database engines across environments (e.g., MySQL in pilot and PostgreSQL in production) to avoid migration risk and inconsistent query behavior.
	•	PostgreSQL SHALL be configured with:
	•	UTC timestamps as default
	•	Strict foreign keys for referential integrity
	•	Standardized indexing strategy for sync-heavy tables (see Section 11.3 / 11.4 additions below)

6. High-Level Architecture Overview
[ Flutter Mobile App ]            [ Web Dashboards ]
        ↓ REST API                        ↓
[ Laravel Modular Monolith (Domains) ]
  - Cooperative & Structure
  - Dairy
  - Feed
  - Vet/Extension
  - Marketplace
  - Cooperative Finance
        ↓
[ PostgreSQL Database ]
        ↓
[ Reports & Dashboards ]

External Services Notification Service (REQUIRED): SMS/Email/Push delivery with delivery receipts and retry.
	•	Payment Provider (CONDITIONAL REQUIRED): Required if Marketplace settlement uses external rails (bank/mobile money).
	•	IoT Gateway (OPTIONAL, Phase 2+): Required only when IoT devices are onboarded.
The architecture follows a modular monolith approach, allowing future service separation without redesign.
7. Logical Architecture
7.1 Core Domains
	•	Cooperative & Structure Management
	•	Dairy Production Management
	•	Feed & Nutrition Management
	•	Personnel & Field Services
	•	Reporting & Analytics
	•	Security & Audit
	•	Marketplace Domain (Trading & Offer Management)
	•	Cooperative Finance Domain (Contributions, Loans, Repayments, Dividends, Settlement)
	•	Configuration & Reference Data (Governed)
7.2 Platform Primitives (Cross-Cutting Services)To avoid inconsistent implementations across domains, the following SHALL be treated as platform primitives used by all domains:
	•	Approval Engine
	•	Canonical append-only approval events model (submit/approve/reject/reverse)
	•	State derived from latest valid event
	•	Audit Engine
	•	Append-only audit events with correlation_id
	•	Optional tamper-evident hash-chain
	•	Idempotency Service
	•	Canonical idempotency handling and storage (idempotency_keys)
	•	Applies to all sync-write and commit endpoints
	•	Notification Service
	•	As defined in 15.4.1 + tables
	•	Configuration Service
	•	As defined in 9.7 + config tables
Canonical Event Requirements (applies to ALL event tables):
	•	Every event SHALL include: correlation_id, actor_user_id, actor_role_snapshot, created_at, and optional event_version.
	•	Current state SHALL be computed via deterministic state reducers documented per entity type.
Integration note: Marketplace decisions (marketplace_offer_decisions) and finance workflow events (gl_entry_events) SHALL conform to the canonical event requirements above (same correlation_id + event_version + actor snapshot).
8. Organizational Data Model
8.1 Hierarchical Structure
Cooperative
   └── Cluster
         └── Member
All operational data flows bottom-up from Member → Cluster → Cooperative.
9. Domain Architecture
9.1 Cooperative & Cluster Management
Key Entities
	•	cooperatives
	•	clusters
	•	members
	•	cooperative_assets
	•	meetings
	•	resolutions
Core Functions
	•	Member registration
	•	Cluster formation
	•	Asset tracking
	•	Governance records
9.2 Dairy Production Management
Key Entities
	•	dairy_animals
	•	milk_production_logs
	•	milk_collection_centers
	•	quality_tests
Core Functions
	•	Daily milk yield recording (offline capable)
	•	Aggregation by cluster and cooperative
	•	Quality input by processors
9.3 Feed & Nutrition Management
Key Entities
	•	feed_types
	•	feed_inventory
	•	feed_distributions
Core Functions
	•	Feed stock management
	•	Distribution to members
	•	Confirmation and reporting
Approval is single-layer (Cooperative Admin or Vet as configured) with mandatory separation-of-duties(submitter cannot approve own submission) and immutable decision logging (decisions are append-only; reversals are separate events).
9.4 Veterinary, Paravet & Extension Services
Key Entities
	•	veterinary_officers
	•	paravets
	•	extension_workers
	•	service_assignments
	•	visit_reports
Responsibilities
	•	Animal health records
	•	Production validation
	•	Advisory and training reports
9.5 Marketplace Domain (Revenue Driver)
ResponsibilitiesHandles listing creation, offer submission, offer decisions, and transaction finalization for cooperative trade.
Key Entities
	•	marketplace_listings
	•	marketplace_offers (immutable once submitted)
	•	marketplace_offer_decisions (append-only decisions stream)
	•	marketplace_transactions (append-only finalization records)
Core Functions
	•	Create listings (by Members / Coop Admin / Processor as configured)
	•	Submit offers (buyers)
	•	Accept / reject offers (seller or authorized delegate)
	•	Finalize transaction (atomic commit with finance settlement)
Sequence Flow: Offer Acceptance → Finalization (Atomic)
	•	Buyer submits offer (immutable write)
	•	System validates scope + permissions (country/cooperative scoping; buyer eligibility)
	•	Seller (or Coop Admin) accepts offer (decision recorded as append-only decision event)
	•	Finalization request MUST include Idempotency-Key / client_request_id (see SAD 10.2 / 11.2)
	•	System begins single DB transaction
	•	Insert marketplace_transactions row (append-only)
	•	Trigger Finance settlement insert(s) (see 9.6) in the same DB transaction
	•	Commit transaction
	•	Emit audit event(s) with correlation_id
Risk Controls 
	•	Offers are immutable once submitted (no updates; only new offers). 
	•	Finalization is idempotent (retries return the original transaction result). 
	•	No partial commit states permitted: marketplace finalization + finance settlement MUST succeed or fail together (single commit). 
	•	All state changes recorded as append-only events (offer decisions + transaction records).
Offline Rule
	•	Listing creation and offer submission MAY be queued offline, but transaction finalization MUST require server connectivity (to guarantee atomic settlement and prevent double-commit).
9.5.1 Marketplace ↔ Finance Finalization Rule-Set (Mandatory)
A) Non-Negotiable Invariants 
Atomicity
	•	Marketplace finalization + finance settlement MUST commit in a single database transaction.
	•	No partial commits are permitted (e.g., marketplace marked sold but finance not settled, or finance settled but marketplace not sold).
Uniqueness / Single-Finalization
	•	A listing MUST NOT be finalized more than once.
	•	An accepted offer MUST NOT be finalized more than once.
	•	A single finalization request MUST be safely retryable without creating duplicates (idempotency).
Ledger Integrity
	•	Finance ledger entries are append-only (no UPDATE/DELETE; reversals are new entries referencing originals).
	•	Any settlement MUST be represented as a balanced ledger movement:
	•	Either explicit double-entry (preferred), or
	•	Deterministic debit/credit entries whose sum equals zero across involved accounts.
Authorization / Separation of Duties
	•	Only authorized actors can finalize:
	•	Seller (listing owner) OR cooperative admin/marketplace manager within the same scope.
	•	Initiator cannot approve disbursement-type actions if maker-checker is enabled for payouts (see Finance SoD rules).(Note: finalizing a marketplace sale that triggers a payout follows maker-checker policy if configured.)
Consistency Under Concurrency
	•	Concurrency must not allow two finalizers to “win.” Enforcement must be DB-level (locks + unique constraints), not just app checks.
B) State Model (Minimal Safe State Machine)
Listing Status
	•	OPEN → SOLD (only via successful finalization commit)
	•	OPEN → CANCELLED (only before SOLD; cancel is append-only event or logged audit action)
Offer Status (via decisions stream)
	•	SUBMITTED → ACCEPTED or REJECTED
	•	ACCEPTED → REVERSED (only via reversal event; must reference the accepted decision)
Transaction Status
	•	COMMITTED (append-only creation)
	•	REVERSED (append-only reversal record referencing original tx)
Phase 1 simplification (recommended): listing is full-fill only (no partial quantity fills). If partial fills are required later, they must introduce “fills” and remaining_qty invariants.
C) Required DB Constraints (Hard Stops Against Double Credit)
Implement these constraints to make it impossible to double-finalize even with bugs:
	•	marketplace_transactions:
	•	UNIQUE(listing_id)
	•	UNIQUE(offer_id)
	•	UNIQUE(idempotency_key)
	•	finance_transactions (append-only ledger):
	•	UNIQUE(account_id, idempotency_key) (or UNIQUE(cooperative_id, idempotency_key) if you prefer global scope per coop)
	•	idempotency_keys:
	•	UNIQUE(cooperative_id, client_request_id) (as already defined)
	•	Indexing (minimum):
	•	marketplace_offers(listing_id, created_at)
	•	marketplace_offer_decisions(offer_id, created_at)
	•	finance_transactions(account_id, created_at)
	•	finance_balances(account_id) (PK)
D) Finalization Algorithm (Exact Transaction Boundary)
Endpoint: POST /marketplace/transactions/finalizeRequires: Idempotency-Key header (UUID) + listing_id + offer_id
Pre-conditions (before opening DB transaction)
	•	Validate actor authorization (seller/admin/marketplace manager).
	•	Validate request completeness and currency consistency:
	•	listing currency = offer currency = involved accounts currency
	•	Validate offer is ACCEPTED and not reversed.
Inside ONE PostgreSQL transaction (mandatory)
Use READ COMMITTED + explicit locks, or REPEATABLE READ. (Most teams use READ COMMITTED with locks + unique constraints.)
Lock order to avoid deadlocks: Listing → Offer → Finance accounts (sorted by account_id)
	•	Idempotency check
	•	Insert idempotency_keys row for this request (cooperative_id + idempotency_key / client_request_id).
	•	If conflict:
	•	Return the previously stored marketplace_transaction_id / response payload (safe retry).
	•	Lock listing row
	•	SELECT * FROM marketplace_listings WHERE id=? FOR UPDATE
	•	Assert status = OPEN (or no existing marketplace_transactions for listing).
	•	Lock offer row
	•	SELECT * FROM marketplace_offers WHERE id=? AND listing_id=? FOR UPDATE
	•	Assert offer exists, belongs to listing.
	•	Validate accepted decision
	•	Fetch latest decision for offer:
	•	must be ACCEPTED
	•	must not have a later REVERSED
	•	If invalid → rollback and return 409 Conflict.
	•	Hard uniqueness check (defense in depth)
	•	Attempt insert into marketplace_transactions with (listing_id, offer_id, idempotency_key).
	•	If unique violation on listing_id/offer_id → treat as already finalized:
	•	fetch existing tx and return it (idempotent behavior).
	•	Lock finance balance rows
	•	Identify accounts (minimum):
	•	Buyer settlement/savings account (debit)
	•	Seller settlement/savings account (credit)
	•	Cooperative fee account (optional; credit)
	•	Lock each via:
	•	SELECT * FROM finance_balances WHERE account_id IN (…) FOR UPDATE
	•	Compute settlement amounts (all in minor units)
	•	gross = offer_price_minor * offer_quantity (or listing-based formula)
	•	fee = configured_fee_rate * gross (rounding rule: bankers rounding or floor; choose one and make it consistent)
	•	net_to_seller = gross - fee
	•	Validate buyer has sufficient balance if wallet-based.
	•	If external payment required, enforce payment confirmation rule (see Section H below).
	•	Write ledger entries (append-only)
	•	Insert finance transactions (append-only) with the SAME idempotency_key group reference:
	•	Buyer: -gross
	•	Seller: +net_to_seller
	•	Coop fee account: +fee (if fee enabled)
	•	Update finance_balances deterministically in the same tx.
	•	Finalize listing
	•	Update listing status = SOLD (allowed mutable field) AND emit audit event.
	•	OR (preferred) insert a listing_event record and derive status (if you later add listing events).
	•	Commit
	•	Store response payload reference on idempotency_keys row (marketplace_transaction_id, finance_tx_group_id).
Response (stable)
Return:
	•	marketplace_transaction_id
	•	finance_tx_group_id
	•	listing_id, offer_id
	•	amounts: gross, fee, net
	•	status: committed
	•	processed_at timestamp
E) Failure Handling Rules (No “Half-Sold”)
If any validation fails, any insert fails, or any balance check fails:
	•	The DB transaction MUST rollback fully.
	•	The API MUST return an error that is safe to retry:
	•	409 Conflict for already-finalized or invalid state
	•	422 Unprocessable Entity for business rule failures (insufficient funds)
	•	500/503 for transient failures
Critical: never mark listing sold before ledger commit.
F) Reversal & Refund Model (Append-Only, Dispute-Safe)
When reversals are permitted
	•	Only for:
	•	Duplicate settlement detected
	•	Fraud/chargeback (if external payment)
	•	Admin-authorized dispute resolution
How reversal works (exact rules)
	•	Create marketplace_transaction_reversals (or reuse marketplace_offer_decisions with a reversal event) referencing original marketplace_transaction_id.
	•	Insert finance reversal entries as new append-only transactions referencing original finance entries:
	•	Seller: -net_to_seller
	•	Buyer: +gross (or +net + fee depending on fee refund policy)
	•	Coop fee: -fee (if fee refundable; otherwise leave fee)
	•	Apply row locks on balances (SELECT … FOR UPDATE) and commit as one atomic DB transaction.
Fee policy (must be explicit)
Choose one:
	•	Refund fees on reversal (fee debited back from coop account)
	•	Non-refundable fee (common for platforms; requires clear governance)
G) Disputes & Holds (Prevents Instant “Payout-and-Regret”)
If you allow cash-out/payouts to external rails:
	•	Introduce payout_hold_days or “pending settlement” state:
	•	marketplace transaction commits internally
	•	seller payout becomes eligible after X days or after delivery confirmation
Optional hold mechanism (recommended at national scale):
	•	Add finance_holds table:
	•	locks a portion of seller balance from withdrawal until released/expired
	•	Holds are append-only events too.
H) External Payment Provider Rules (If/When Integrated)
If the buyer pays via bank/mobile money:
	•	Finalization MUST be gated by payment confirmation:
	•	Create payment_provider_transactions with status PENDING
	•	Only after provider confirms SUCCESS do you run the atomic finalization that credits seller/fees.
	•	Never trust client “paid” flags.
I) Audit & Observability (Mandatory Fields)
Every finalization MUST generate:
	•	correlation_id / request_id
	•	actor_user_id + role snapshot
	•	listing_id, offer_id, marketplace_transaction_id, finance_tx_group_id
	•	idempotency_key
	•	amounts (gross/fee/net)
	•	timestamps (UTC)
	•	source (web/mobile/admin)
Metrics to track:
	•	finalization p95 latency
	•	finalization error rate
	•	idempotency conflict rate
	•	reversal rate
	•	insufficient funds rate

J) Minimum Acceptance Tests (Must Pass Before Go-Live)
	•	Retry safety: same Idempotency-Key called 10 times → exactly one tx created.
	•	Race condition: two users finalize same accepted offer simultaneously → one succeeds, the other returns “already finalized”.
	•	Rollback integrity: force DB error after finance insert → marketplace not sold, balances unchanged.
	•	Reversal correctness: reversal restores balances exactly per policy.
	•	Authorization: buyer cannot finalize; random coop member cannot finalize others’ listings.
	•	Insufficient funds: no ledger entries created, no listing sold.
9.5.2 Payment Provider Integration (Required if external payment rails are used)If buyer payments occur via bank/mobile money:
	•	The system SHALL implement payment_provider_transactions with an explicit state machine.
	•	Finalization SHALL be gated on provider confirmation (SUCCESS).
State Model (minimum):INITIATED → PENDING → SUCCESS | FAILED | EXPIRED | REVERSED | CHARGEBACK
Webhook processing requirements:
	•	Webhook ingestion MUST be idempotent (provider_event_id unique).
	•	Webhooks MUST update payment state and emit audit events with correlation_id.
	•	A reconciliation job SHALL run periodically to resolve mismatches (PENDING too long, provider SUCCESS but no settlement, etc.).
9.5.3 Dispute & Case Management (Unified)The system SHALL implement a unified case model for: marketplace disputes, finance reversals, chargebacks, holds, and regulatory issues.
Case lifecycle (minimum):OPEN → UNDER_REVIEW → ACTION_REQUIRED → RESOLVED → CLOSED
Capabilities:
	•	link cases to marketplace transaction / GL entry / payment transaction
	•	attach evidence (files/notes)
	•	enforce SLA + escalation notifications
	•	apply holds (gl_holds) during investigation where policy requires
Tables (add to 16.2): cases, case_events (append-only), case_evidence

9.5.4 Robust Vendor Marketplace Expansion (Mandatory for Marketplace Scale)
The marketplace domain SHALL be treated as a multi-subdomain commerce capability, not only a listing-offer-finalize workflow.
Marketplace subdomains:
	•	Vendor Management
	•	Catalog & Listing Management
	•	Negotiation & Offer Management
	•	Order & Fulfillment Management
	•	Settlement & Payout Control
	•	Marketplace Governance, Moderation, and Trust
Vendor Management
The system SHALL support explicit vendor identities for:
	•	member-backed sellers
	•	cooperative sellers
	•	processor buyers acting in approved procurement scope
	•	future external vendor classes if enabled by governance
Vendor capabilities (minimum):
	•	vendor profile and legal display identity
	•	vendor verification / approval workflow
	•	vendor status (draft, pending_verification, active, suspended, revoked)
	•	payout and settlement policy assignment
	•	trust indicators (verified, cooperative-endorsed, quality-assured, restricted)
Catalog & Listing Management
Listings SHALL support richer commercial meaning than quantity + asking price.
Minimum listing fields beyond current base schema:
	•	title
	•	description
	•	quality_grade / quality_summary
	•	location or pickup point
	•	availability window
	•	expiry timestamp
	•	pricing_type (fixed / negotiable / policy-driven)
	•	min_order_quantity
	•	max_order_quantity (nullable)
	•	visibility_scope
	•	moderation_status
	•	fulfillment_method
	•	delivery_terms_summary
Listing lifecycle (minimum):
	•	DRAFT → PENDING_MODERATION → PUBLISHED → RESERVED_PARTIAL | RESERVED_FULL → CLOSED | EXPIRED | REJECTED | CANCELLED
Listing status SHOULD be event-derived via listing_events in scaled deployments.
Negotiation & Offer Management
Offers SHALL remain immutable per submission, but the domain SHALL support multi-step negotiation by creating new offers or counter-offers linked by chain.
Offer capabilities (minimum):
	•	expiry timestamp
	•	counter_offer_to_offer_id (nullable)
	•	delivery_terms_summary
	•	payment_terms_summary
	•	fulfillment_window
	•	offer_source (mobile / web / admin / api)
Offer lifecycle (minimum):
	•	SUBMITTED → COUNTERED | ACCEPTED | REJECTED | EXPIRED | WITHDRAWN
Order & Fulfillment Management
An accepted offer SHALL create a marketplace order or reservation context before final commercial completion is treated as operationally complete.
Minimum order capabilities:
	•	order creation from accepted offer
	•	reserved quantity tracking
	•	fulfillment states
	•	pickup / delivery confirmation
	•	receipt confirmation
	•	quality verification outcome
	•	operational exception capture
Order lifecycle (minimum):
	•	PENDING_FULFILLMENT → RESERVED → IN_COLLECTION | IN_TRANSIT → DELIVERED → RECEIVED → QUALITY_VERIFIED | DISPUTED → SETTLED | CANCELLED
Phase policy:
	•	Phase 1 MAY combine order creation and settlement more tightly for simplicity, but the data model SHALL still reserve first-class support for marketplace_orders and fulfillment events.
Partial Fills & Reservations
The marketplace SHALL be architecturally ready for partial fills and reservation windows.
If Phase 1 remains full-fill only:
	•	the system SHALL explicitly enforce full-fill validation
	•	the schema SHALL still leave room for listing_reservations / order lines / remaining quantity expansion
If partial fills are enabled:
	•	remaining quantity MUST be derived or atomically maintained
	•	reservations MUST expire or be released by policy
	•	competing offers MUST respect reserved quantity
Governance, Moderation, and Trust
The marketplace SHALL support:
	•	listing moderation events
	•	vendor suspension / restriction workflow
	•	policy violation reasons
	•	trust badge derivation
	•	dispute classification specific to marketplace operations
Marketplace dispute classes (minimum):
	•	quality_mismatch
	•	short_delivery
	•	no_show_pickup
	•	late_delivery
	•	payment_dispute
	•	duplicate_or_fraudulent_listing
	•	settlement_mismatch
Marketplace-specific invariants:
	•	no processor-to-member settlement path SHALL bypass cooperative governance
	•	no listing may exceed seller scope or approved commodity scope
	•	no accepted or reserved quantity may exceed available quantity
	•	no fulfillment completion may be back-dated without audit event
Additional marketplace entities to add to 16.2:
	•	vendors
	•	vendor_profiles
	•	vendor_verifications
	•	listing_events
	•	listing_reservations
	•	marketplace_orders
	•	marketplace_order_events
	•	marketplace_fulfillment_events
	•	marketplace_moderation_events
	•	vendor_payout_policies
	•	vendor_metrics_snapshots

9.6 Cooperative Finance Domain (Revenue Driver)
ResponsibilitiesManages member contributions, savings withdrawals, loans, repayments, and dividend allocations, plus marketplace settlement into member/cooperative accounts.
Key Entities
	•	finance_accounts
	•	finance_balances (single row per account, locked for updates)
	•	finance_transactions (append-only ledger entries)
	•	finance_loans (optional, if loans enabled)
	•	finance_dividend_allocations (optional, if dividends enabled)
	•	payment_provider_transactions (optional, if integrating external payment rails)
Transaction Model (EMATS-aligned)
	•	Finance transactions are append-only (no updates/deletes; reversals are new entries referencing original). 
LMCP EMATS
	•	All balance-affecting operations execute inside PostgreSQL transactions using row-level locking (SELECT … FOR UPDATE) to prevent race conditions. 
LMCP EMATS
	•	Idempotency keys are mandatory for all balance-affecting operations to prevent duplicate submissions. 
LMCP EMATS
Sequence Flow: Balance-Affecting Operation (Contribution/Withdrawal/Settlement)
	•	Validate Idempotency-Key / client_request_id
	•	Begin DB transaction
	•	Lock account balance row: SELECT … FOR UPDATE
	•	Validate business rule (e.g., sufficient balance for withdrawal)
	•	Insert finance_transactions (append-only)
	•	Update finance_balances deterministically within the same transaction
	•	Commit
	•	Emit audit event with correlation_id
Separation-of-Duties (Finance-Specific)
	•	Any cash-out / withdrawal / loan disbursement / dividend payout SHALL require maker-checker approval:
	•	Initiated by Finance Officer
	•	Approved by Treasurer/Coop Admin
	•	Initiator ≠ Approver (ties into SAD 10.3 / 10.4)
Offline Rule
	•	Balance-affecting operations MUST be online-only (or “captured offline as pending request” but not committed to ledger until server-side approval + locking transaction completes).
All balance-affecting finance operations SHALL be recorded using a double-entry journal (gl_journal_entries + gl_journal_lines) as defined in Section 11.4. Balance caches (gl_account_balances) are performance-only and updated atomically when entries are posted. All postings are idempotent and append-only; reversals are new journal entries.
9.6.2 Finance Source of Truth (Mandatory)
	•	The double-entry ledger (gl_journal_entries + gl_journal_lines) SHALL be the sole source of truth for all balance-affecting finance operations and marketplace settlement.
	•	finance_transactions and finance_balances SHALL be treated as DEPRECATED in national-scale builds.
	•	If retained for backward compatibility, they MUST be derived views/materialized views computed from posted GL entries/lines and MUST NOT be written to directly by application workflows.
	•	All settlement and finance commits SHALL post GL entries (and update gl_account_balances) atomically.

9.7 Configuration & Reference Data Domain (Governed)Purpose: Centralize all configurable rules and reference data with auditability and environment promotion discipline.
Config Examples (minimum):
	•	approval authority rules (Admin vs Vet)
	•	maker-checker enablement + thresholds
	•	marketplace fee policy + rounding rule
	•	payout hold windows + dispute hold policies
	•	supported languages + message templates
	•	rate limits + device policies (IoT)
Rules:
	•	All configuration changes SHALL be audited (who/when/what changed).
	•	Configuration SHALL be scoped (global/country/cooperative).
	•	Configuration SHALL support promotion between environments via versioned provisioning (no “hand edits” in production without audit).


10. Data Entry & Validation Model
10.1 Data Authority Levels
Role
Authority
Member
Self-reported input
Extension Worker
Verification
Paravet
Routine validation
Veterinary Officer
Health & nutrition authority
Coop Admin
Operational approval
The server is the final authority on all persisted records.
10.2 Idempotency & Safe Retry Model (Mandatory for Offline Sync)To prevent duplicate records caused by intermittent connectivity and client retries:
	•	Every “create” operation originating from mobile or web SHALL include a client_request_id (UUID v4) generated client-side before submission.
	•	The server SHALL enforce idempotency for all create endpoints (e.g., milk production logs, feed distributions, health interventions):
	•	If a request with the same client_request_id has already been processed for the same cooperative scope, the server SHALL return the prior result (same created entity ID) without creating duplicates.
	•	Idempotency SHALL apply even when:
	•	The client resends a request after timeout
	•	The same payload is re-submitted due to offline queue replay
	•	The server SHALL return a stable response signature (entity_id + status + processed_at) to allow the client to reconcile local pending records.
Required validation rules (minimum):
	•	client_request_id MUST be unique per cooperative scope for at least 30 days
	•	The server SHALL reject requests missing client_request_id for all endpoints classified as “sync-write”
10.3 Separation-of-Duties (SoD) Controls for ApprovalsTo prevent self-approval and approval fraud:
	•	Any record requiring approval (e.g., feed distribution approval, production validation) SHALL enforce:
	•	submitted_by_user_id ≠ approved_by_user_id
	•	Approval authority SHALL be role-based AND scope-based:
	•	Approvers MUST be within the same cooperative scope (and country scope if enabled)
	•	Where a cooperative config allows “Admin OR Vet” approval, the system SHALL still enforce SoD:
	•	The same user cannot submit and approve the same item
	•	All approvals SHALL require:
	•	reason/comment (minimum length enforced)
	•	timestamp
	•	approving role captured at time of approval (not inferred later)
10.4 Immutable Decisions & Controlled ReversalsApprovals SHALL be immutable to ensure audit defensibility:
	•	Once an approval decision is recorded, it SHALL NOT be edited or overwritten.
	•	If an approval must be changed, the system SHALL create a new reversal event referencing the original decision, including:
	•	reversal_reason
	•	reversed_by_user_id
	•	reversed_at
	•	reference_to_original_approval_event_id
	•	The current status of an item SHALL be derived from the latest valid event in its approval event stream (see Section 11.3).

11. Database Architecture
11.1 Core Tables (Simplified)
users
roles
user_roles

cooperatives
clusters
members

veterinary_officers
paravets
extension_workers
service_assignments

dairy_animals
milk_production_logs
quality_tests

feed_types
feed_inventory
feed_distributions

processors
processor_users
marketplace_listings
marketplace_offers
marketplace_offer_decisions
marketplace_transactions
vendors
vendor_profiles
vendor_verifications
listing_events
listing_reservations
marketplace_orders
marketplace_order_events
marketplace_fulfillment_events
marketplace_moderation_events
vendor_payout_policies
vendor_metrics_snapshots
gl_accounts
gl_journal_entries
gl_journal_lines
gl_entry_events
gl_account_balances
gl_holds (optional)
finance_loans (if loans enabled)
finance_dividend_allocations (if dividends enabled)
audit_logs
All tables are scoped by country and cooperative. 
Legacy finance_transactions / finance_balances are deprecated; if present, they are read-only derived projections of GL postings.”
11.2 Idempotency & Deduplication Tables (Mandatory)Add a dedicated table to enforce idempotency at database level:
idempotency_keys
	•	id (PK)
	•	cooperative_id (FK)
	•	user_id (FK, nullable for system operations)
	•	client_request_id (UUID, indexed)
	•	request_type (e.g., milk_production_create, feed_distribution_create)
	•	request_hash (hash of normalized payload)
	•	response_entity_id (FK/UUID reference)
	•	status (processed/failed)
	•	created_at, expires_at
Constraints:
	•	Unique constraint on (cooperative_id, client_request_id)
	•	Retain for minimum 30 days (or longer if audit policy requires)
11.3 Approval as an Immutable Event Stream (Append-Only)Replace “approved_by” style mutable fields for sensitive workflows with event-driven approvals.
Add:
approval_events (append-only)
	•	id (PK)
	•	cooperative_id (FK)
	•	entity_type (milk_log, feed_distribution, etc.)
	•	entity_id (FK/UUID)
	•	event_type (submitted, approved, rejected, reversed)
	•	actor_user_id (FK)
	•	actor_role_snapshot (string)
	•	reason/comment (text)
	•	created_at
	•	previous_event_id (nullable; links event chain)
Rules:
	•	No UPDATE or DELETE allowed at application level for approval_events
	•	Current approval status is computed from latest event in chain
	•	Reversal MUST reference original approval event via previous_event_id / metadata
11.4 Double-Entry Ledger Schema (Mandatory for Finance Domain at Scale)
Objective
Represent all finance movements (contributions, withdrawals, marketplace settlement, fees, dividends, loan disbursements, repayments, reversals) as balanced journal entries with debit/credit lines, ensuring:
	•	Append-only ledger (no updates/deletes to posted entries)
	•	Idempotent posting (safe retries)
	•	Maker-checker for sensitive postings
	•	Concurrency-safe balances (row-level locks)
11.4.1 Core Tables
A) gl_accounts (Chart of Accounts)
Defines accounts for members, cooperative, platform fees, settlement, escrow/holds.
Columns (minimum)
	•	id (PK)
	•	cooperative_id (FK)
	•	code (string, unique per coop) — e.g., MEM:123:SAVINGS, COOP:FEES, COOP:CASH, COOP:ESCROW
	•	name (string)
	•	account_type (enum: asset, liability, equity, income, expense)
	•	currency (string, e.g., NGN)
	•	owner_type (enum: member, cooperative, system)
	•	owner_id (nullable)
	•	status (active/disabled)
	•	created_at
Constraints
	•	Unique: (cooperative_id, code)
	•	Currency is immutable once used in postings.
B) gl_journal_entries (Journal Header)
One journal entry per logical business transaction (or grouped transaction).
Columns (minimum)
	•	id (PK, UUID recommended)
	•	cooperative_id (FK)
	•	entry_type (enum: contribution, withdrawal, marketplace_settlement, fee, loan_disbursement, repayment, dividend, reversal, adjustment)
	•	reference_type (string) — e.g., marketplace_transaction
	•	reference_id (string/UUID)
	•	idempotency_key (UUID) — REQUIRED for all postings
	•	status (enum: draft, pending_approval, posted, reversed, rejected)
	•	created_by_user_id (FK)
	•	approved_by_user_id (FK, nullable)
	•	posted_at (timestamp nullable)
	•	created_at
Constraints
	•	Unique: (cooperative_id, idempotency_key)
	•	Optional uniqueness: (cooperative_id, reference_type, reference_id, entry_type) if you want extra safety.
C) gl_journal_lines (Postings)
Each line is a debit or credit to one account. A journal entry MUST balance.
Columns (minimum)
	•	id (PK)
	•	journal_entry_id (FK)
	•	account_id (FK -> gl_accounts)
	•	side (enum: debit, credit)
	•	amount_minor (bigint, positive)
	•	currency (string, must match entry)
	•	memo (nullable)
	•	created_at
Constraints
	•	amount_minor > 0
	•	currency matches the parent entry’s currency (enforce by rule/trigger)
	•	No updates/deletes once entry is posted
D) gl_entry_events (Append-Only Event Trail)
Tracks immutable workflow history for approvals/posting/reversal.
Columns (minimum)
	•	id (PK)
	•	journal_entry_id (FK)
	•	event_type (enum: created, submitted_for_approval, approved, rejected, posted, reversed)
	•	actor_user_id (FK)
	•	reason (text nullable)
	•	previous_event_id (nullable, to chain)
	•	created_at
Rule
	•	Append-only. Never update/delete.
E) gl_account_balances (Materialized Balance Cache)
For performance, keep a “current balance” table updated only when entries are posted.
Columns (minimum)
	•	account_id (PK)
	•	balance_minor (bigint) — signed
	•	updated_at
Rule
	•	Updated only in the same DB transaction that posts the journal entry.
	•	Balance MUST be derivable from posted journal lines for audit (the cache is performance only).
F) Optional: gl_holds (Pending/Payout Holds)
To prevent instant withdraw-after-sale (recommended if payouts exist).
Columns
	•	id (PK)
	•	account_id (FK)
	•	amount_minor (bigint)
	•	currency
	•	reason
	•	status (active/released/expired)
	•	reference_type, reference_id
	•	created_at, released_at
11.4.2 Balancing Rule (Debits = Credits)
Invariant: For every gl_journal_entries.id where status becomes posted:
Σ(debit.amount_minor) = Σ(credit.amount_minor)and all lines share the same currency.
Enforcement options (choose one, but MUST enforce at DB-level):
	•	Database trigger on posting (recommended): validate totals before allowing posted_at to be set / posted event to be inserted.
	•	Store total_debits_minor and total_credits_minor on gl_journal_entries and apply a check constraint at posting time (still typically needs a trigger to compute).
11.4.3 Posting Workflow (Idempotent + Concurrency-Safe)
Step 1 — Create Entry (Draft)
	•	Insert into gl_journal_entries with status draft or pending_approval.
	•	Insert all gl_journal_lines.
	•	Insert gl_entry_events: created.
Step 2 — Maker-Checker (if required)
For withdrawals / payouts / loan disbursement / reversals:
	•	Transition requires distinct users:
	•	created_by_user_id ≠ approved_by_user_id
	•	Insert gl_entry_events: approved or rejected.
Step 3 — Post Entry (Atomic)
Within one PostgreSQL transaction:
	•	Idempotency: upsert/lock by (cooperative_id, idempotency_key) — if already posted, return existing result.
	•	Lock all impacted balance rows in deterministic order:SELECT ... FROM gl_account_balances WHERE account_id IN (...) FOR UPDATE
	•	Validate sufficient funds if account is constrained (e.g., wallet cash account):
	•	available_balance = balance_minor - active_holds
	•	Validate balancing rule.
	•	Apply balance updates:
	•	Debit line: balance += amount_minor (for asset accounts) OR use a unified sign rule (see below)
	•	Credit line: balance -= amount_minor(Pick one consistent rule and stick to it; most systems maintain a unified “balance sign” per account type. For simplicity in MVP, treat balance as “net postings” and compute with a fixed rule: debit +, credit -.)
	•	Insert gl_entry_events: posted, set posted_at.
	•	Commit.
Important: Only entries with posted_at IS NOT NULL are considered effective in reporting and settlement.
11.4.4 Reversal Model (Strict Append-Only)
To reverse an entry:
	•	Create a new gl_journal_entries row with entry_type = reversal, reference_id = original_entry_id.
	•	Create lines that mirror the original with sides swapped:
	•	original debit → reversal credit
	•	original credit → reversal debit
	•	Post the reversal entry using the same posting workflow (maker-checker may be required).
	•	Insert gl_entry_events: reversed on both the new entry and (optionally) an event referencing the original.
No edits to the original posted entry. Ever.
11.4.5 Marketplace Settlement Mapping (Example)
When a marketplace transaction finalizes (atomic boundary with marketplace tx):Create one journal entry with 3 lines:
	•	Buyer account: Credit gross (reduces buyer funds)
	•	Seller account: Debit net_to_seller (increases seller funds)
	•	Coop fee account: Debit fee (increases coop fee balance)
This entry MUST be:
	•	entry_type = marketplace_settlement
	•	reference_type = marketplace_transaction
	•	reference_id = marketplace_transaction_id
	•	idempotency_key = finalization_idempotency_key
And it MUST post in the same DB transaction as marketplace_transactions insertion (your atomic finalization rule-set).

11.4.6 Required Indexes (Minimum)
	•	gl_journal_entries(cooperative_id, posted_at)
	•	gl_journal_entries(cooperative_id, idempotency_key) UNIQUE
	•	gl_journal_entries(reference_type, reference_id)
	•	gl_journal_lines(journal_entry_id)
	•	gl_journal_lines(account_id, created_at)
	•	gl_account_balances(account_id) PK
	•	gl_entry_events(journal_entry_id, created_at)
11.5 Indexing Standards & Query Patterns (Mandatory)Index standards (minimum):
	•	All operational tables SHALL include composite indexes aligned to scoping + time:
	•	(country_id, cooperative_id, created_at) where country is present
	•	(cooperative_id, created_at) for coop-scoped tables
	•	“Pending work” tables SHALL include partial indexes for common states (e.g., approvals pending, payout pending, disputes open).
Query-driven indexing:For each dashboard screen, the system SHALL document:
	•	primary filters (date ranges, cooperative, cluster)
	•	expected sort order
	•	the exact index supporting that query

12. Data Flow Example
	•	Member records daily milk yield offline
	•	Data stored locally on device
	•	Sync occurs when connectivity is available
	•	Backend validates and stores record
	•	Aggregates updated for cluster and cooperative dashboards
13. UI / UX Architecture
13.1 Mobile Application
	•	Role-based navigation
	•	Icon-driven interfaces
	•	Offline data capture
	•	Local language support (phased)
13.2 Web Dashboards
	•	Cooperative performance
	•	Cluster productivity
	•	Feed stock status
	•	Dairy output trends
14. Security Architecture
14.1 Authentication & Access Control
	•	Token-based authentication (Sanctum)
	•	Role-based access control (RBAC)
	•	Cooperative and country data scoping
Privileged Access Controls (Mandatory for Finance/Admin Roles)
	•	MFA SHALL be mandatory for: System Admin, Country Admin, Coop Admin, Finance Officer, Treasurer, Marketplace Manager.
	•	Device binding SHALL be enforced for privileged roles (trusted device list + revocation).
	•	Session revocation MUST be supported (invalidate all tokens for a user on compromise).
	•	Admin impersonation (if enabled) MUST be: time-boxed, explicitly approved, and fully audited.
	•	All privileged actions SHALL be logged with correlation_id + before/after summaries.

14.2 Data Security
	•	HTTPS enforced
	•	Encrypted local mobile storage
	•	Daily database backups
	•	Full audit logging for sensitive operations
14.3 Fraud Monitoring & Alerts (Minimum Controls)The system SHALL implement a basic fraud monitoring layer that:
	•	detects anomalies (rules-based minimum):
	•	repeated failed approvals / repeated reversals
	•	unusually high marketplace settlement volume per account
	•	excessive idempotency collisions
	•	rapid withdrawal attempts after sale events
	•	creates alert records and notifies administrators (Notification Service)
	•	supports investigation notes and resolution status
Tables (add to 16.2): fraud_alerts, fraud_alert_events (append-only)

15. Deployment Architecture
15.1 Initial Deployment
	•	Prototype only: shared hosting MAY be used for demos only.
	•	Pilot minimum: VPS deployment with minimum isolation (firewall rules, restricted DB access, backups aligned to RPO/RTO, and baseline monitoring as per 15.5).
	•	Nginx + PHP-FPM + PostgreSQL
	•	Cron-based background jobs
15.2 Scalability Strategy
	•	Vertical scaling first
	•	Database replication later
	•	Service separation in future phases
15.3 Non-Functional Requirements (Measurable SLO Targets)
Availability
	•	Pilot: 99.5% monthly availability (excluding planned maintenance)
	•	National scale: 99.9% monthly availability
Performance (API)
	•	Read endpoints p95 latency:
	•	Pilot: ≤ 500ms
	•	National: ≤ 350ms
	•	Write/sync endpoints p95 latency (server accept/commit):
	•	Pilot: ≤ 900ms
	•	National: ≤ 700ms
	•	Batch sync ingestion (offline queue flush):
	•	Server must accept payload and return reconciliation within ≤ 5 seconds for batches up to 200 records
Concurrency (definition required)
	•	Pilot target: 300 concurrent active users
	•	National target: 5,000 concurrent active users
	•	“Concurrent active user” = user making at least one API request within a rolling 60 seconds.
Throughput
	•	Pilot: sustain ≥ 30 req/s
	•	National: sustain ≥ 250 req/s (horizontally scalable app tier)
Data Integrity
	•	All sync-write operations MUST be idempotent (see Section 10.2 / 11.2)
	•	Approvals MUST be append-only immutable events (see Section 11.3)
15.4 Queue Strategy (Mandatory for Scale & Reliability)
	•	Laravel queues SHALL be enabled for:
	•	sync ingestion post-processing
	•	aggregation jobs (dashboards/KPIs)
	•	notifications (SMS/email where applicable)
	•	exports/reports generation
	•	Queue backend:
	•	Pilot: Redis recommended (DB queue allowed only for very small pilots)
	•	National: Redis required
	•	Worker strategy:
	•	Minimum 2 worker processes in pilot
	•	Separate worker node in scale-out phase (already referenced in Section 33.2)
	•	Retry policy:
	•	Exponential backoff
	•	Max retries per job type defined (e.g., 5)
	•	Dead-letter handling for poison messages
15.4.1 Notification Processing (Mandatory)
	•	The system SHALL implement a Notification Service that supports:
	•	SMS, Email, Push (channel configurable per user/cooperative)
	•	delivery status tracking (queued/sent/delivered/failed)
	•	retries with exponential backoff
	•	template-based localization (i18n keys)
	•	Notifications SHALL be sent asynchronously via queue workers.
	•	Critical workflows requiring notifications include (minimum): approvals, maker-checker finance actions, marketplace offer decisions, transaction finalization, disputes/case updates, password resets/security alerts.

15.5 Observability (Monitoring, Logging, Alerting)Minimum monitoring SHALL include:
Metrics
	•	API p95 latency (read/write)
	•	error rate (4xx/5xx)
	•	request rate
	•	queue depth + job failure rate
	•	database CPU, memory, disk, slow queries
	•	replication lag (if replicas enabled)
Logging
	•	Centralized log collection recommended for national scale
	•	Logs MUST include correlation_id per request and be retained:
	•	Pilot: 30 days
	•	National: 180 days (or policy-defined)
Alerting (minimum)
	•	API error rate > 2% for 5 minutes
	•	p95 latency breach for 10 minutes
	•	queue backlog exceeds threshold (e.g., > 10,000 pending jobs)
	•	disk usage > 80%
	•	failed backup / failed restore drill
15.6 Backup & Disaster Recovery (Measurable RPO/RTO)
Backups
	•	Automated daily full backups + continuous WAL (or equivalent) recommended
	•	Backup encryption at rest REQUIRED
RPO (Recovery Point Objective)
	•	Pilot: ≤ 24 hours
	•	National: ≤ 15 minutes
RTO (Recovery Time Objective)
	•	Pilot: ≤ 8 hours
	•	National: ≤ 4 hours
Restore drills
	•	Pilot: quarterly restore test
	•	National: monthly restore test for at least one environment
Post-Restore Reconciliation (Mandatory for Ledger Integrity)After any restore drill or production restore:
	•	Recompute balances from posted GL lines and compare to gl_account_balances.
	•	Identify and report mismatches as incidents.
	•	Verify uniqueness invariants (idempotency keys, single-finalization constraints).
	•	Produce a signed reconciliation report for governance/audit.

16. Detailed Technical Architecture
16.1 Entity–Relationship (ER) Overview
Core Relationships
	•	A Cooperative has many Clusters
	•	A Cluster has many Members
	•	A Member owns many Dairy Animals
	•	Milk Production Logs belong to a Member and Cluster
	•	Feed Distributions belong to a Member and Cluster
	•	Veterinary / Paravet / Extension Workers are assigned to Clusters or Cooperatives
Referential integrity is enforced at the database level.
16.2 Core Table Definitions (Logical)
cooperatives
	•	id (PK)
	•	country_id
	•	name
	•	registration_number
	•	status
	•	created_at
clusters
	•	id (PK)
	•	cooperative_id (FK)
	•	name
	•	location_description
members
	•	id (PK)
	•	cluster_id (FK)
	•	member_code
	•	full_name
	•	gender
	•	phone
dairy_animals
	•	id (PK)
	•	member_id (FK)
	•	animal_tag
	•	breed
	•	production_status
milk_production_logs
	•	id (PK)
	•	member_id (FK)
	•	cluster_id (FK)
	•	quantity_liters
	•	production_date
	•	entered_by
feed_distributions
	•	id (PK)
	•	member_id (FK)
	•	feed_type_id (FK)
	•	quantity
	•	distribution_date
	•	approved_by
marketplace_listings
	•	id (PK)
	•	cooperative_id (FK)
	•	seller_member_id (FK, nullable if cooperative-owned listing)
	•	vendor_id (FK)
	•	item_type (milk/feed/animal/asset/service)
	•	item_ref_id (nullable, FK/UUID)
	•	title
	•	description
	•	quality_grade (nullable)
	•	quantity
	•	unit
	•	min_order_quantity (nullable)
	•	max_order_quantity (nullable)
	•	asking_price_minor (integer)
	•	pricing_type (fixed/negotiable/policy)
	•	currency
	•	status (draft/pending_moderation/published/reserved_partial/reserved_full/closed/expired/rejected/cancelled)
	•	moderation_status
	•	availability_starts_at (nullable)
	•	availability_ends_at (nullable)
	•	expires_at (nullable)
	•	fulfillment_method (pickup/delivery/collection_center)
	•	delivery_terms_summary (nullable)
	•	created_at
marketplace_offers (immutable)
	•	id (PK)
	•	listing_id (FK)
	•	buyer_member_id (FK)
	•	buyer_vendor_id (nullable FK)
	•	offer_price_minor (integer)
	•	offer_quantity
	•	terms_summary
	•	delivery_terms_summary (nullable)
	•	payment_terms_summary (nullable)
	•	expires_at (nullable)
	•	counter_offer_to_offer_id (nullable)
	•	client_request_id (UUID)
	•	created_atConstraint: unique(listing_id, client_request_id)
marketplace_offer_decisions (append-only)
	•	id (PK)
	•	offer_id (FK)
	•	decision_type (accepted/rejected/reversed/countered/expired/withdrawn)
	•	actor_user_id (FK)
	•	reason
	•	previous_decision_id (nullable)
	•	created_at
marketplace_orders
	•	id (PK)
	•	cooperative_id (FK)
	•	listing_id (FK)
	•	accepted_offer_id (FK)
	•	buyer_vendor_id (nullable FK)
	•	seller_vendor_id (FK)
	•	reserved_quantity
	•	order_status (pending_fulfillment/reserved/in_collection/in_transit/delivered/received/quality_verified/disputed/settled/cancelled)
	•	settlement_status (pending/posted/held/reversed)
	•	fulfillment_method
	•	created_at
marketplace_order_events
	•	id (PK)
	•	order_id (FK)
	•	event_type
	•	actor_user_id (FK)
	•	reason (nullable)
	•	created_at
marketplace_fulfillment_events
	•	id (PK)
	•	order_id (FK)
	•	event_type (reserved/dispatch_started/delivered/received/quality_verified/failed/disputed)
	•	actor_user_id (FK)
	•	location_summary (nullable)
	•	evidence_ref (nullable)
	•	created_at
marketplace_transactions (append-only finalization)
	•	id (PK)
	•	listing_id (FK)
	•	offer_id (FK)
	•	order_id (nullable FK)
	•	finance_tx_group_id (UUID / reference)
	•	idempotency_key (UUID)
	•	created_atConstraint: unique(idempotency_key)
finance_accounts
	•	id (PK)
	•	cooperative_id (FK)
	•	owner_type (member/cooperative)
	•	owner_id (FK)
	•	account_type (savings/loan/settlement)
	•	currency
	•	status
	•	created_at
finance_balances (locked row)
	•	account_id (PK, FK)
	•	balance_minor (bigint)
	•	updated_at
finance_transactions (append-only ledger)
	•	id (PK)
	•	cooperative_id (FK)
	•	account_id (FK)
	•	tx_type (contribution/withdrawal/loan/repayment/dividend/marketplace_settlement/reversal)
	•	amount_minor (signed bigint)
	•	reference_type, reference_id (e.g., marketplace_transaction)
	•	idempotency_key (UUID)
	•	created_by_user_id (FK)
	•	created_atConstraint: unique(account_id, idempotency_key)
notifications
	•	id (PK)
	•	cooperative_id (FK, nullable for system-wide)
	•	user_id (FK, nullable if broadcast/group)
	•	notification_type (enum: approval, finance, marketplace, dispute, security, system)
	•	title_key (string)
	•	body_key (string)
	•	payload (json)
	•	priority (low/normal/high)
	•	created_at
notification_deliveries
	•	id (PK)
	•	notification_id (FK)
	•	channel (sms/email/push)
	•	destination (phone/email/device_token)
	•	provider_message_id (nullable)
	•	status (queued/sent/delivered/failed)
	•	last_error (nullable)
	•	attempts (int)
	•	sent_at (nullable)
	•	delivered_at (nullable)
	•	created_at
config_keys
	•	id (PK)
	•	key (string, unique)
	•	description (text)
	•	value_type (enum: string, int, bool, json)
	•	created_at
config_values
	•	id (PK)
	•	scope_type (enum: global, country, cooperative)
	•	scope_id (nullable)
	•	config_key_id (FK)
	•	value (json)
	•	version (int)
	•	is_active (bool)
	•	created_by_user_id (FK)
	•	created_at
config_change_events (append-only)
	•	id (PK)
	•	config_value_id (FK)
	•	actor_user_id (FK)
	•	event_type (created/activated/deactivated/rolled_back)
	•	reason (text, nullable)
	•	created_at
payment_provider_transactions
	•	id (PK)
	•	cooperative_id (FK)
	•	provider (string)
	•	provider_reference (string, unique)
	•	amount_minor (bigint)
	•	currency
	•	status (initiated/pending/success/failed/expired/reversed/chargeback)
	•	related_reference_type (e.g., marketplace_transaction)
	•	related_reference_id
	•	idempotency_key (UUID)
	•	created_at
	•	updated_at
payment_provider_events (append-only)
	•	id (PK)
	•	provider (string)
	•	provider_event_id (string, unique)
	•	payment_provider_transaction_id (FK)
	•	payload (json)
	•	received_at
	•	processed_at (nullable)
iot_devices
	•	id (PK)
	•	cooperative_id (FK)
	•	device_type (string)
	•	serial_number (string, unique)
	•	status (active/disabled/revoked)
	•	last_seen_at (nullable)
	•	created_at
iot_device_credentials (append-only)
	•	id (PK)
	•	device_id (FK)
	•	credential_hash
	•	issued_at
	•	revoked_at (nullable)
iot_measurements (time-series)
	•	id (PK)
	•	device_id (FK)
	•	metric_type (string)
	•	value (decimal)
	•	captured_at (timestamp)
	•	received_at (timestamp)


Finance (Double-Entry Ledger) — Core Tables
gl_accounts (Chart of Accounts)
Purpose: Defines ledger accounts for members, cooperative, fees, settlement, escrow/holds.
Columns
	•	id (PK)
	•	cooperative_id (FK)
	•	code (string, unique per cooperative) — examples: MEM:123:SAVINGS, COOP:FEES, COOP:CASH, COOP:ESCROW
	•	name (string)
	•	account_type (enum: asset, liability, equity, income, expense)
	•	currency (string, e.g., NGN)
	•	owner_type (enum: member, cooperative, system)
	•	owner_id (nullable)
	•	status (enum: active, disabled)
	•	created_at
Constraints
	•	Unique: (cooperative_id, code)
	•	Currency is immutable once the account has been used in posted lines.
gl_journal_entries (Journal Header)
Purpose: One journal entry per business transaction; header holds idempotency, references, workflow status.
Columns
	•	id (PK, UUID recommended)
	•	cooperative_id (FK)
	•	entry_type (enum: contribution, withdrawal, marketplace_settlement, fee, loan_disbursement, repayment, dividend, reversal, adjustment)
	•	reference_type (string) — e.g., marketplace_transaction
	•	reference_id (string/UUID)
	•	idempotency_key (UUID, REQUIRED)
	•	status (enum: draft, pending_approval, posted, reversed, rejected)
	•	created_by_user_id (FK)
	•	approved_by_user_id (FK, nullable)
	•	posted_at (timestamp, nullable)
	•	created_at
Constraints
	•	Unique: (cooperative_id, idempotency_key)
	•	(Recommended) Unique: (cooperative_id, reference_type, reference_id, entry_type) for settlement-type entries.
gl_journal_lines (Journal Postings / Lines)
Purpose: Debit/credit lines that must balance per entry. Append-only once posted.
Columns
	•	id (PK)
	•	journal_entry_id (FK → gl_journal_entries.id)
	•	account_id (FK → gl_accounts.id)
	•	side (enum: debit, credit)
	•	amount_minor (bigint, positive)
	•	currency (string, must match entry currency)
	•	memo (nullable)
	•	created_at
Constraints
	•	amount_minor > 0
	•	Line currency must equal entry currency.
	•	No UPDATE/DELETE allowed for lines once entry is posted.
gl_entry_events (Journal Workflow Events — Append-Only)
Purpose: Immutable event trail for creation, approvals, posting, and reversals (audit defensibility).
Columns
	•	id (PK)
	•	journal_entry_id (FK → gl_journal_entries.id)
	•	event_type (enum: created, submitted_for_approval, approved, rejected, posted, reversed)
	•	actor_user_id (FK)
	•	reason (text, nullable)
	•	previous_event_id (nullable, links event chain)
	•	created_at
Rules
	•	Append-only. Never update/delete.
gl_account_balances (Materialized Balance Cache)
Purpose: Performance cache of current balances; updated only during posting.
Columns
	•	account_id (PK, FK → gl_accounts.id)
	•	balance_minor (bigint, signed)
	•	updated_at
Rules
	•	Updated only in the same DB transaction that posts a journal entry.
	•	Must always be recomputable from posted lines for audit.
gl_holds (Optional — Funds Holds / Escrow)
Purpose: Prevent immediate withdrawal of funds (payout holds, disputes, delivery confirmation windows).
Columns
	•	id (PK)
	•	account_id (FK → gl_accounts.id)
	•	amount_minor (bigint, positive)
	•	currency (string)
	•	reason (string/text)
	•	status (enum: active, released, expired)
	•	reference_type (string, nullable)
	•	reference_id (string/UUID, nullable)
	•	created_at
	•	released_at (nullable)
Rules
	•	Holds reduce available balance: available = balance_minor - sum(active holds)
	•	Holds are never edited into a different amount; release/expire is a state transition with audit trail.
Balancing Rule (Mandatory)
	•	For any entry with status = posted, total debits MUST equal total credits (same currency).
	•	Reversals are new journal entries with mirrored lines (side swapped), referencing the original entry.
	•	All posted entries and lines are append-only (no overwrite edits).

(Optional tables for loans/dividends can be added as an extension if enabled in scope.)
16.3 API Architecture
All system interactions are exposed through RESTful APIs.
API Design Principles
	•	JSON payloads
	•	Token-based authentication
	•	Versioned endpoints (/api/v1)
Sample Endpoints
	•	POST /api/v1/auth/login
	•	GET /api/v1/cooperatives/{id}
	•	GET /api/v1/clusters/{id}/members
	•	POST /api/v1/milk-production
	•	POST /api/v1/feed-distributions
Idempotency Requirement (Write Endpoints)
	•	All sync-write endpoints (POST/PUT that create/commit domain records) SHALL accept either:
	•	Idempotency-Key header (UUID), or
	•	client_request_id in body (UUID)
	•	The server SHALL treat duplicates as safe retries and return the original success response.
Transactional Boundary Rule (Marketplace ↔ Finance)
Marketplace transaction finalization SHALL be implemented as one atomic DB transaction that:
	•	inserts the marketplace_transactions record (append-only), AND
	•	creates and posts a GL journal entry (gl_journal_entries + gl_journal_lines) for settlement, AND
	•	updates gl_account_balances under row locks (SELECT … FOR UPDATE)If any step fails, the entire operation SHALL rollback. No partial commit states are allowed.

16.4 Role–Permission Matrix (Summary)
“Role-to-Permission Matrix (RBAC)”

Legend
	•	✅ Allowed
	•	⚠️ Allowed with restrictions (scope/ownership/assignment)
	•	⛔ Not allowed
	•	🔒 Special rule applies (e.g., SoD / online-only / immutable / maker-checker)
Roles (columns):MEM Member | EXT Extension Officer | VET Vet/Paravet | CAD Coop Admin | FIN Finance Officer | TRE Treasurer | MKT Marketplace Manager | PRO Processor | SYS System Admin

1) Cooperative & Member Management
Permission ID
Description
MEM
EXT
VET
CAD
FIN
TRE
MKT
PRO
SYS
coop.profile.view
View cooperative profile
⚠️
⚠️
⚠️
✅
✅
✅
✅
⚠️
✅
coop.profile.update
Update cooperative profile/settings
⛔
⛔
⛔
✅
⛔
⛔
⛔
⛔
✅
member.self.view
View own profile
✅
✅
✅
✅
✅
✅
✅
✅
✅
member.self.update
Update own profile fields
⚠️
⚠️
⚠️
⚠️
⚠️
⚠️
⚠️
⚠️
✅
member.manage.create
Create/onboard member
⛔
⚠️
⛔
✅
⛔
⛔
⛔
⛔
✅
member.manage.update
Update member records
⛔
⚠️
⛔
✅
⛔
⛔
⛔
⛔
✅
member.manage.deactivate
Deactivate/disable member
⛔
⛔
⛔
✅
⛔
⛔
⛔
⛔
✅
assignment.manage
Assign members to clusters/officers
⛔
⛔
⛔
✅
⛔
⛔
⛔
⛔
✅
roles.assign.coop
Assign cooperative-level roles
⛔
⛔
⛔
⚠️
⛔
⛔
⛔
⛔
✅
Restriction notes
	•	EXT “create/update member” = only for field onboarding fields; final activation is CAD/SYS.
	•	SYS can do anything but must be fully audited.

2) Dairy & Feed Operations (Sync-write, idempotent)
Permission ID
Description
MEM
EXT
VET
CAD
FIN
TRE
MKT
PRO
SYS
dairy.log.create
Create milk production log
✅
⚠️
⛔
✅
⛔
⛔
⛔
⛔
✅
dairy.log.view
View milk logs
⚠️
⚠️
⚠️
✅
⚠️
⚠️
⚠️
⚠️
✅
feed.dist.create
Create feed distribution record
⚠️
⚠️
⛔
✅
⛔
⛔
⛔
⛔
✅
ops.approval.decide
Approve/reject ops records (feed/dairy etc.)
⛔
⛔
⚠️
✅ 🔒
⛔
⛔
⛔
⛔
✅
ops.approval.reverse
Reverse ops approval (append-only)
⛔
⛔
⛔
✅ 🔒
⛔
⛔
⛔
⛔
✅
Special rules
	•	🔒 Separation of Duties: submitter cannot approve own submissions.
	•	All create endpoints require client_request_id / Idempotency-Key.

3) Veterinary & Extension Services
Permission ID
Description
MEM
EXT
VET
CAD
FIN
TRE
MKT
PRO
SYS
ext.visit.create
Create field visit record
⛔
✅
⚠️
✅
⛔
⛔
⛔
⛔
✅
vet.intervention.create
Record treatment/vaccination
⛔
⛔
✅
✅
⛔
⛔
⛔
⛔
✅
vet.records.view
View health records
⚠️
⚠️
✅
✅
⛔
⛔
⛔
⚠️
✅

4) Marketplace (Listings, Offers, Disputes, Finalization)
Permission ID
Description
MEM
EXT
VET
CAD
FIN
TRE
MKT
PRO
SYS
mkt.listing.create
Create marketplace listing
⚠️
⛔
⛔
✅
⛔
⛔
✅
⚠️
✅
mkt.listing.moderate
Edit/close/cancel listings (policy-based)
⛔
⛔
⛔
⚠️
⛔
⛔
✅
⛔
✅
mkt.offer.submit
Submit offer
⚠️
⛔
⛔
⚠️
⛔
⛔
⛔
✅
✅
mkt.offer.decide
Accept/reject offer (append-only decision)
⚠️
⛔
⛔
✅ 🔒
⛔
⛔
✅ 🔒
⚠️
✅
mkt.dispute.manage
Manage disputes & resolution actions
⛔
⛔
⛔
⚠️
⛔
⛔
✅
⛔
✅
mkt.tx.finalize
Finalize transaction (atomic + idempotent + online-only)
⛔
⛔
⛔
✅ 🔒
⛔
⚠️ 🔒
✅ 🔒
⚠️
✅
Special rules
	•	🔒 mkt.tx.finalize is ONLINE-ONLY and must be atomic marketplace+finance.
	•	Offer decisions are append-only (immutable; reversals are new events).

5) Cooperative Finance (Ledger, Payouts, Maker-Checker)
Permission ID
Description
MEM
EXT
VET
CAD
FIN
TRE
MKT
PRO
SYS
fin.account.view.self
View own account summary
✅
⚠️
⚠️
⚠️
⚠️
⚠️
⚠️
⚠️
✅
fin.ledger.view.self
View own ledger
✅
⛔
⛔
⚠️
⚠️
⚠️
⛔
⚠️
✅
fin.ledger.view.coop
View cooperative finance ledgers
⛔
⛔
⛔
⚠️
✅
✅
⛔
⚠️ (settlement only)
✅
fin.contribution.initiate
Initiate contribution (idempotent)
⚠️
⛔
⛔
⚠️
✅
⚠️
⛔
⚠️
✅
fin.withdrawal.request
Request withdrawal/payout (creates pending)
⚠️
⛔
⛔
⚠️
✅ 🔒
⚠️
⛔
⚠️
✅
fin.withdrawal.approve
Approve withdrawal/payout (maker-checker)
⛔
⛔
⛔
⚠️ 🔒
⛔
✅ 🔒
⛔
⛔
✅
fin.loan.disburse.request
Request loan disbursement (pending)
⛔
⛔
⛔
⚠️
✅ 🔒
⚠️
⛔
⛔
✅
fin.loan.disburse.approve
Approve loan disbursement
⛔
⛔
⛔
⚠️ 🔒
⛔
✅ 🔒
⛔
⛔
✅
fin.repayment.post
Post repayment (idempotent)
⚠️
⛔
⛔
⚠️
✅
⚠️
⛔
⚠️
✅
fin.tx.reverse
Reverse finance transaction (append-only)
⛔
⛔
⛔
⚠️ 🔒
⛔
✅ 🔒
⛔
⛔
✅
Special rules (must be stated under the matrix)
	•	🔒 Maker-Checker: FIN can request/initiate, TRE can approve. Initiator ≠ approver (enforced).
	•	Finance entries are append-only; reversal is a new ledger entry referencing original.
	•	Balance-affecting writes are ONLINE-ONLY (or “pending request offline, commit online”).

6) Reporting, Audit, System Ops
Permission ID
Description
MEM
EXT
VET
CAD
FIN
TRE
MKT
PRO
SYS
report.view.basic
View dashboards (basic)
⚠️
⚠️
⚠️
✅
✅
✅
✅
⚠️
✅
report.view.finance
Finance dashboards
⛔
⛔
⛔
⚠️
✅
✅
⛔
⚠️ (settlement)
✅
audit.view.scope
View audit events (scoped)
⛔
⛔
⛔
✅
✅
✅
✅
⚠️
✅
audit.view.global
View global audit logs
⛔
⛔
⛔
⛔
⛔
⛔
⛔
⛔
✅
config.global.manage
Global configs, reference data
⛔
⛔
⛔
⛔
⛔
⛔
⛔
⛔
✅

Implementation Notes (add under the matrix)
	•	Scope enforcement: “⚠️” always means constrained by cooperative_id + assignment (cluster/member ownership).
	•	SoD enforcement: for any permission marked 🔒, enforce initiator_user_id != approver_user_id at DB + app layer.
	•	Idempotency: any “create/commit” permission must require client_request_id or Idempotency-Key.
	•	Online-only: mkt.tx.finalize and all balance-affecting finance commits must be online.

16.5 Offline Synchronization Model
	•	Mobile app uses local encrypted storage
	•	Records are marked as pending until synced
	•	Server performs validation and conflict resolution
	•	Server state always overrides local state
16.5.1 Offline Write Classification Contract (Mandatory)
Class
Definition
Client Behavior
Server Behavior
Examples
A — Offline-Queueable Writes
Safe to queue and replay
Store locally, auto retry
Must be idempotent; accept replay
milk logs, field visit reports, listing create, offer submit
B — Offline Capture-Only Requests
May be captured offline but NOT committed
Store as “pending request” only
Must require online confirmation before commit
withdrawal request (capture), dispute ticket draft
C — Online-Only Commits
Must never be queued as a commit
Block action offline
Must use locks + atomic commit
GL posting, marketplace finalization, approvals/maker-checker decisions
Enforcement:
	•	Mobile client SHALL enforce this classification in UI/UX.
	•	API SHALL enforce it server-side (reject class C commits without online session constraints if needed).
	•	All Class A writes MUST include idempotency keys.

16.6 Audit & Logging Model
All critical actions are logged:
	•	Feed distribution
	•	Production edits
	•	Health interventions
	•	Approvals
Logs include user, role, timestamp, and affected entity.
Tamper-Evident Audit Requirement
	•	Audit records SHALL be append-only.
	•	Each audit event SHALL store:
	•	request_id / correlation_id
	•	actor_user_id + role snapshot
	•	entity_type + entity_id
	•	action
	•	before/after summaries (where applicable)
	•	timestamp (UTC)
	•	Optional (recommended for national scale): store a hash-chain field to make log tampering detectable (each event includes hash(previous_hash + current_payload)).

17. Detailed API Contracts
17.1 Authentication APIs
POST /api/v1/auth/login
	•	Request: phone/email, password
	•	Response: access_token, user_role, expires_at
POST /api/v1/auth/logout
	•	Invalidates token
17.2 Cooperative & Structure APIs
GET /api/v1/cooperatives/{id}
	•	Returns cooperative profile and summary metrics
GET /api/v1/clusters/{id}/members
	•	Returns members within a cluster
POST /api/v1/members
	•	Creates new member under a cluster (Admin only)
17.3 Dairy Production APIs
POST /api/v1/milk-production
	•	Request: member_id, quantity_liters, production_date
	•	Validation: role-based
GET /api/v1/milk-production/cluster/{cluster_id}
	•	Aggregated production data
17.4 Feed & Nutrition APIs
POST /api/v1/feed-distributions
	•	Request: member_id, feed_type_id, quantity
	•	Approval required (Vet or Coop Admin)
GET /api/v1/feed-inventory
	•	Current stock levels
17.5 Marketplace APIs
	•	GET /api/v1/vendors/{id} (vendor profile + trust signals)
	•	POST /api/v1/vendors/applications (vendor onboarding / verification request)
	•	POST /api/v1/marketplace/listings (create listing)
	•	GET /api/v1/marketplace/listings (filter by coop/cluster/item_type/status/vendor/quality/availability)
	•	GET /api/v1/marketplace/listings/{id} (listing detail)
	•	POST /api/v1/marketplace/offers (submit offer — requires client_request_id)
	•	POST /api/v1/marketplace/offers/{id}/decide (accept/reject/counter/withdraw — append-only decision)
	•	GET /api/v1/marketplace/orders/{id} (order + fulfillment + settlement summary)
	•	POST /api/v1/marketplace/orders/{id}/reserve
	•	POST /api/v1/marketplace/orders/{id}/dispatch
	•	POST /api/v1/marketplace/orders/{id}/confirm-receipt
	•	POST /api/v1/marketplace/orders/{id}/quality-verify
	•	POST /api/v1/marketplace/orders/{id}/open-dispute
	•	POST /api/v1/marketplace/transactions/finalize (finalize — requires Idempotency-Key; atomic marketplace+finance commit)
17.6 Cooperative Finance APIs
	•	POST /finance/contributions (idempotent, balance-affecting)
	•	POST /finance/withdrawals/request (creates approval request; maker-checker)
	•	POST /finance/withdrawals/{id}/approve (SoD enforced)
	•	POST /finance/loans/disburse (if enabled; maker-checker)
	•	POST /finance/repayments (idempotent)
	•	GET /finance/accounts/{id}/ledger (append-only transactions)
	•	GET /finance/reports/summary (read replica recommended at scale)

18. Backend (Laravel) Application Architecture
18.1 Project Structure
app/
 ├── Http/
 │   ├── Controllers/
 │   ├── Middleware/
 │   └── Requests/
 ├── Models/
 ├── Services/
 ├── Policies/
 ├── Jobs/
 └── Observers/
18.2 Architectural Principles
	•	Fat services, thin controllers
	•	Policy-based authorization
	•	Form Request validation
	•	Eloquent ORM with scoped queries
18.3 Data Scoping
	•	Global scopes for country and cooperative
	•	Prevents cross-boundary access
19. Mobile Application (Flutter) Architecture
19.1 App Structure
lib/
 ├── data/
 │   ├── models/
 │   ├── local_db/
 │   └── repositories/
 ├── services/
 ├── state/
 ├── ui/
 │   ├── screens/
 │   └── widgets/
 └── utils/
19.2 Offline-First Strategy
	•	Local encrypted SQLite database
	•	Sync queue for pending records
	•	Conflict resolution handled server-side
19.3 State Management
	•	Provider / Riverpod
	•	Role-based UI rendering
20. Synchronization Workflow (Mobile → Server)
	•	User enters data offline
	•	Record saved locally with status=PENDING
	•	Background sync triggers on connectivity
	•	API submission to backend
	•	Backend validates and persists
	•	Status updated to SYNCED
21. Technical Readiness
With the inclusion of API contracts, backend structure, and mobile architecture, this document now represents a complete technical blueprint ready for immediate implementation using Laravel, Flutter, and postgres in low-resource environments.
22. Flutter Mobile Screen Flow Diagrams (By Role)
This section defines role-specific screen flows for the Flutter mobile application. Flows are optimized for offline-first usage, minimal steps, and low digital literacy.
22.1 Common Entry Flow (All Roles)
Splash → Language Select → Login
      → Sync Check → Role Router
	•	Offline login supported for previously authenticated users
	•	Role Router directs users to role-specific home screens
22.2 Member Screen Flow
Member Home
 ├── My Profile
 ├── My Cluster
 ├── Milk Production
 │     └── Add Daily Yield (Offline)
 ├── Feed Received
 │     └── Confirm Distribution
 ├── Requests
 │     ├── Vet Visit
 │     └── Feed Request
 └── Notifications
Key Characteristics
	•	Simple icons and numeric inputs
	•	No approval or validation actions
	•	All submissions marked Pending until reviewed
22.3 Cooperative Admin Screen Flow
Coop Admin Dashboard
 ├── Cooperative Overview
 ├── Clusters
 │     └── Cluster Details → Members
 ├── Approvals
 │     ├── Milk Records
 │     └── Feed Distributions
 ├── Assets
 ├── Reports
 └── Settings
Key Characteristics
	•	Aggregated metrics
	•	Approval and correction authority
	•	Offline review with deferred sync
22.4 Veterinary Officer Screen Flow
Vet Dashboard
 ├── Assigned Clusters
 ├── Member Visits
 │     └── Health Record Entry
 ├── Nutrition & Feed
 │     └── Approve Distribution
 ├── Animal Records
 └── Reports
Key Characteristics
	•	Health and nutrition authority
	•	Can override member-reported data
	•	GPS optional for visit logs
22.5 Paravet Screen Flow
Paravet Dashboard
 ├── Assigned Clusters
 ├── Routine Care
 │     └── Vaccination / Treatment Logs
 ├── Milk Verification
 └── Reports
Key Characteristics
	•	Limited approval scope
	•	Focus on routine and preventive care
22.6 Extension Worker Screen Flow
Extension Dashboard
 ├── Assigned Clusters
 ├── Member Verification
 ├── Training & Advisory Logs
 ├── Production Validation
 └── Reports
Key Characteristics
	•	Verification-focused
	•	Advisory documentation
	•	No feed approval authority
22.7 Processor User Screen Flow
Processor Dashboard
 ├── Collection Center
 ├── Milk Intake
 │     └── Quantity & Quality Input
 ├── Daily Summary
 └── Reports (Read-only)
Key Characteristics
	•	Read + input only
	•	No access to cooperative governance data
22.8 Offline & Sync UX States (All Roles)
	•	Pending Sync Badge
	•	Sync Failed Retry
	•	Conflict Resolved Notification
	•	Last Sync Timestamp
These states are visible across all data-entry screens to maintain user trust and transparency.
22.9 Navigation Principles
	•	Bottom navigation for primary actions
	•	No deep nesting beyond 3 levels
	•	Contextual actions only (role-based)
23. Flutter Screen Wireframe Descriptions
This section provides screen-level functional wireframes describing fields, actions, and validations. These are textual wireframes intended for direct handoff to Flutter developers.
23.1 Login Screen
Fields
	•	Phone / Email
	•	Password
Actions
	•	Login
	•	Retry (offline cached users only)
Rules
	•	Offline login allowed for previously authenticated users
	•	Language selection persists locally
23.2 Member – Daily Milk Entry Screen
Fields
	•	Date (default = today)
	•	Quantity (liters, numeric)
Actions
	•	Save (Offline)
Status Indicators
	•	Pending Sync
	•	Synced
23.3 Member – Feed Confirmation Screen
Fields
	•	Feed Type (read-only)
	•	Quantity (read-only)
	•	Distribution Date
Actions
	•	Confirm Receipt
23.4 Vet – Health & Nutrition Screen
Fields
	•	Member
	•	Animal Tag
	•	Diagnosis
	•	Treatment
	•	Nutrition Recommendation
Actions
	•	Save Visit Report
	•	Approve Feed (if applicable)
23.5 Cooperative Admin – Approval Screen
Lists
	•	Pending Milk Records
	•	Pending Feed Distributions
Actions
	•	Approve
	•	Reject with Comment
24. Database Migration Blueprint (Laravel)
This section defines initial Laravel migration structures. Field types are indicative and may be adjusted during implementation.
24.1 cooperatives
	•	id (bigIncrements)
	•	country_id (unsignedBigInteger)
	•	name (string)
	•	registration_number (string, nullable)
	•	timestamps
24.2 clusters
	•	id (bigIncrements)
	•	cooperative_id (foreignId)
	•	name (string)
	•	location_description (string, nullable)
	•	timestamps
24.3 members
	•	id (bigIncrements)
	•	cluster_id (foreignId)
	•	member_code (string)
	•	full_name (string)
	•	phone (string)
	•	gender (enum)
	•	timestamps
24.4 dairy_animals
	•	id (bigIncrements)
	•	member_id (foreignId)
	•	animal_tag (string)
	•	breed (string)
	•	production_status (string)
	•	timestamps
24.5 milk_production_logs
	•	id (bigIncrements)
	•	member_id (foreignId)
	•	cluster_id (foreignId)
	•	quantity_liters (decimal)
	•	production_date (date)
	•	entered_by (foreignId)
	•	status (enum: pending, approved)
	•	timestamps
24.6 feed_distributions
	•	id (bigIncrements)
	•	member_id (foreignId)
	•	feed_type_id (foreignId)
	•	quantity (decimal)
	•	distribution_date (date)
	•	approved_by (foreignId, nullable)
	•	status (enum: pending, approved)
	•	timestamps
24.7 audit_logs
	•	id (bigIncrements)
	•	user_id (foreignId)
	•	action (string)
	•	entity_type (string)
	•	entity_id (unsignedBigInteger)
	•	metadata (json)
	•	created_at
26. Laravel Migration Code (Initial Version)
Below are implementation-ready Laravel migration examples. These can be used directly with minor adjustments.
26.1 create_cooperatives_table.php
Schema::create('cooperatives', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('country_id');
    $table->string('name');
    $table->string('registration_number')->nullable();
    $table->timestamps();
});

26.2 create_clusters_table.php
Schema::create('clusters', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cooperative_id')->constrained();
    $table->string('name');
    $table->string('location_description')->nullable();
    $table->timestamps();
});

26.3 create_members_table.php
Schema::create('members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cluster_id')->constrained();
    $table->string('member_code')->unique();
    $table->string('full_name');
    $table->string('phone');
    $table->enum('gender', ['male','female']);
    $table->timestamps();
});

26.4 create_dairy_animals_table.php
Schema::create('dairy_animals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('member_id')->constrained();
    $table->string('animal_tag')->unique();
    $table->string('breed')->nullable();
    $table->string('production_status');
    $table->timestamps();
});

26.5 create_milk_production_logs_table.php
Schema::create('milk_production_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('member_id')->constrained();
    $table->foreignId('cluster_id')->constrained();
    $table->decimal('quantity_liters', 8, 2);
    $table->date('production_date');
    $table->foreignId('entered_by')->constrained('users');
    $table->enum('status', ['pending','approved'])->default('pending');
    $table->timestamps();
});

27. Eloquent Model Relationships
27.1 Cooperative.php
class Cooperative extends Model {
    public function clusters() {
        return $this->hasMany(Cluster::class);
    }
}
27.2 Cluster.php
class Cluster extends Model {
    public function cooperative() {
        return $this->belongsTo(Cooperative::class);
    }

    public function members() {
        return $this->hasMany(Member::class);
    }
}
27.3 Member.php
class Member extends Model {
    public function cluster() {
        return $this->belongsTo(Cluster::class);
    }

    public function animals() {
        return $this->hasMany(DairyAnimal::class);
    }

    public function milkLogs() {
        return $this->hasMany(MilkProductionLog::class);
    }
}

28. Flutter Data Models & Repository Pattern
28.1 Member Model (Flutter)
class Member {
  final int id;
  final String name;
  final String phone;

  Member({required this.id, required this.name, required this.phone});

  factory Member.fromJson(Map<String, dynamic> json) {
    return Member(
      id: json['id'],
      name: json['full_name'],
      phone: json['phone'],
    );
  }
}
28.2 Milk Production Repository
class MilkProductionRepository {
  final LocalDatabase db;
  final ApiService api;

  Future<void> saveOffline(MilkLog log) async {
    await db.insertMilkLog(log);
  }

  Future<void> sync() async {
    final pending = await db.getPendingMilkLogs();
    for (var log in pending) {
      await api.post('/milk-production', log.toJson());
      await db.markSynced(log.id);
    }
  }
}
30. API Endpoint Specification (Laravel)
All APIs are RESTful, JSON-based, and secured via token authentication (Sanctum).
30.1 Authentication
	•	POST /api/login
	•	POST /api/logout
30.2 Cooperative & Structure
	•	GET /api/cooperatives
	•	POST /api/cooperatives
	•	GET /api/cooperatives/{id}/clusters
	•	POST /api/clusters
30.3 Members & Animals
	•	GET /api/members
	•	POST /api/members
	•	GET /api/members/{id}/animals
	•	POST /api/animals
30.4 Milk Production
	•	POST /api/milk-production
	•	GET /api/milk-production?cluster_id=&date=
	•	POST /api/milk-production/{id}/approve
30.5 Feed Distribution
	•	POST /api/feed-distributions
	•	POST /api/feed-distributions/{id}/approve
31. Roles & Permission Matrix
Role
Scope
Key Permissions
Member
Own data
Milk entry, feed confirmation
Vet / Paravet
Cluster
Health records, feed recommendation
Extension Worker
Cluster
Monitoring, advisory input
Cooperative Admin
Cooperative
Approvals, reporting, user mgmt
Processor
Cooperative
Read-only production access
System Admin
Global
Configuration, audits
Permissions enforced using Laravel Policies and Gates.
32. Dashboards, KPIs & Analytics
32.1 Member Dashboard
	•	Daily milk total
	•	Monthly production trend
	•	Feed received vs expected
32.2 Cooperative Dashboard
	•	Total milk per cluster
	•	Active members
	•	Feed stock vs distribution
	•	Animal health incidents
32.3 Processor Dashboard
	•	Aggregate milk availability
	•	Seasonal trend analysis
Analytics implemented using postgresql views and scheduled aggregation jobs.
33. VPS Hosting & Deployment Specification
33.1 Initial VPS (Pilot)
	•	2 vCPU
	•	4 GB RAM
	•	80 GB SSD
	•	Ubuntu LTS
	•	Nginx + PHP-FPM
	•	Postgresql 
Target: up to 5,000 registered users with ~300 concurrent active users (see Section 15.3).
33.2 Scaling Path
	•	Vertical scaling first (RAM/CPU)
	•	Read replicas for postgresql
	•	Separate worker node for queues
	•	Cloud migration optional (future)
Introduce connection pooling (e.g., PgBouncer) and enforce DB connection limits per app node at national scale.

34. Multi-Language (i18n) Technical Provision
The system SHALL support multi-language operation to reflect linguistic realities of the Lake Chad Basin and ensure usability across low-literacy and multi-ethnic contexts.
34.1 Supported Languages (Initial)
Phase 1 (Mandatory)
	•	English (System default)
	•	French
	•	Hausa
Phase 2 (Optional / Country-Specific)
	•	Kanuri
	•	Fulfulde
	•	Arabic (Modern Standard)
Languages are selectable per user and persisted locally on mobile devices.
34.2 Backend (Laravel) i18n Architecture
Laravel's built-in localization framework SHALL be used.
	•	Language files stored in: resources/lang/{locale}
	•	JSON-based translations for simplicity
	•	Example locales: en, fr, ha
Example
__('milk.production.saved');
All system messages, validation errors, and notifications MUST use translation keys.
34.3 API Language Handling
	•	Mobile client sends Accept-Language header
	•	Backend resolves locale per request
	•	Fallback to English if translation missing
No business logic depends on language.
34.4 Flutter i18n Architecture
Flutter SHALL use intl and flutter_localizations packages.
	•	ARB files per language
	•	Generated localization classes
	•	Language selected at login or first launch
	•	Stored locally (SharedPreferences)
Offline support: All language resources are bundled with the app.
34.5 UI/UX Considerations for Low-Literacy Contexts
	•	Icon-first navigation
	•	Short labels
	•	Numeric-heavy data entry
	•	Avoid long text blocks
	•	Consistent symbols for actions (save, sync, approve)
34.6 Governance & Translation Management
	•	Language strings version-controlled
	•	Translations reviewed per country
	•	No hardcoded text allowed in codebase
35. Country Data Isolation & Legal Compliance
The platform SHALL enforce strict country-bound data governance to support multi-country deployment across the Lake Chad Basin.
35.1 Country-Level Data Isolation Model
Each cooperative SHALL be bound to a country_id.
Data isolation enforced at three levels:
	•	Application Layer – All queries scoped by country_id
	•	Authorization Layer – Users restricted to their assigned country
	•	Reporting Layer – Aggregations cannot cross country boundaries unless System Admin
Optional (Scale Phase):
	•	Separate databases per country
	•	Separate VPS instances per country
35.2 Legal & Regulatory Compliance Principles
The system SHALL comply with:
	•	National data protection regulations of each participating country
	•	Cross-border data transfer restrictions
	•	Agricultural data sovereignty principles
Core Compliance Controls:
	•	Explicit role-based access control (RBAC)
	•	Audit logging of data changes
	•	Data retention policy configuration
	•	Ability to export cooperative data upon request
	•	Secure HTTPS-only communication
35.3 Data Hosting Strategy
Phase 1 (Pilot):
	•	Country-specific VPS hosting where required
	•	No cross-border database replication
Phase 2 (Scale):
	•	Geo-located cloud regions (if adopted)
	•	Country-specific backup storage
35.4 Audit & Transparency
The system SHALL maintain:
	•	Immutable activity logs
	•	Approval traceability
	•	Device audit trails (for IoT integrations)
This ensures suitability for:
	•	Government oversight
	•	Donor-funded programs
	•	Regulatory inspections
35.5 Data Privacy Controls (PII)
	•	The system SHALL classify fields as: Public / Operational / PII / Sensitive PII.
	•	The system SHALL implement:
	•	data minimization (collect only what is needed)
	•	access logging for PII reads (who accessed which record)
	•	retention rules by data type (ledger/audit retained longer than session logs)
	•	breach response plan (detection → containment → notification → recovery)
	•	Exports SHALL respect PII policies (role-based masking where applicable).
35.6 Compliance Operations & Export Governance
	•	The system SHALL implement a governed export workflow for cooperative and user data requests.
	•	Every export request SHALL record:
	•	requestor identity
	•	request scope
	•	legal or operational basis
	•	approving actor where approval is required
	•	generated artifact reference
	•	expiry timestamp for download
	•	Exports containing PII or Sensitive PII SHALL require scoped authorization and, for privileged bulk exports, maker-checker approval.
	•	Data-subject style requests SHALL be handled through an auditable case or service-request workflow with SLA tracking, resolution status, and export or denial reason captured.
	•	Breach-notification ownership SHALL be assigned to a named operational security function or program owner per country deployment.
35.7 Authorization Boundary Clarifications
	•	Authorization SHALL be enforced by explicit role + scope + action checks, not role checks alone.
	•	Scope inheritance SHALL be:
	•	System Admin → all countries
	•	Country Administrator → assigned country only
	•	Cooperative Administrator / Treasurer / Finance Officer / Marketplace Manager → assigned cooperative only
	•	Cluster Supervisor / Extension / Vet / Paravet → assigned cooperative and assigned clusters only
	•	Member → self-owned records and records explicitly shared back to the member
	•	Processor → marketplace and procurement records explicitly visible within approved cooperative scope
	•	Processors MAY submit offers against cooperative-scoped listings, including member-originated listings, but settlement, disputes, and audit visibility SHALL remain cooperative-scoped. No direct processor-to-member settlement path SHALL bypass cooperative governance.
35.8 Supportability & Incident Operations
	•	The system SHALL define severity levels, escalation paths, support ownership, and minimum response expectations for:
	•	authentication outages
	•	sync failures
	•	finance posting failures
	•	marketplace finalization failures
	•	backup or restore failures
	•	security incidents
	•	Runbooks SHALL exist for restore, reconciliation, queue backlog, provider webhook failure, and privileged account compromise.
	•	Operational dashboards SHALL map alerts to named owners or teams.
35.9 Security Operations Clarifications
	•	MFA, trusted-device enrollment, session revocation, and secret rotation for privileged roles SHALL be treated as pilot-entry requirements, not post-pilot hardening items.
	•	Device binding SHALL define enrollment, revocation, recovery, and support override workflow with full audit logging.
	•	Secrets for notification providers, payment providers, IoT credentials, and webhooks SHALL be stored in managed secret storage or equivalent protected configuration and rotated on a defined schedule.
35.10 Baseline Load Model
	•	Non-functional targets in Section 15.3 SHALL be validated against a documented workload model.
	•	The baseline pilot workload model SHALL include, at minimum:
	•	offline sync batches up to 200 records
	•	peak morning and evening milk-log submission windows
	•	concurrent approval queue usage by cooperative staff
	•	report export generation under active dashboard traffic
	•	marketplace settlement contention on the same listing or wallet accounts
	•	The system SHALL document expected record volumes, queue depth assumptions, notification throughput, and dashboard refresh behavior for pilot and national-scale profiles.
35.11 Release & Environment Governance
	•	The system SHALL maintain separate development, test, staging, pilot, and production environments.
	•	Production data SHALL NOT be copied into lower environments without masking or anonymization controls appropriate to data classification.
	•	Schema-affecting releases SHALL follow a documented migration policy covering:
	•	forward-only or reversible migration expectation per change type
	•	expand-contract sequencing where zero-downtime behavior is required
	•	pre-release backup and rollback checkpoint
	•	post-release verification of ledger integrity, queue health, and scoped authorization behavior
	•	CI or deployment gates SHALL include tests for critical finance flows, idempotency, approval event behavior, and migration safety.
35.12 Localization & Accessibility Quality Controls
	•	Each supported language SHALL have an assigned reviewer or owner for translation quality.
	•	User-facing financial, approval, security, and legal text SHALL be reviewed for meaning consistency across supported languages before release.
	•	The system SHALL test text expansion, right-sized line lengths, and icon-plus-label comprehension across low-literacy flows.
	•	Mobile releases SHALL define a low-end device support budget covering startup time, memory pressure, and offline queue visibility on target Android devices.
35.13 State Authority Model
	•	The system SHALL classify persisted state into three authority models:
	•	Event-derived state
	•	Mutable operational state
	•	Cached or projected read state
	•	Event-derived state SHALL apply to:
	•	approval status
	•	marketplace offer decisions
	•	GL posting and reversal workflow state
	•	case event history
	•	Mutable operational state SHALL be limited to fields where current value is the business truth and full event sourcing is not required, such as cooperative profile metadata, assignment records, and non-sensitive display configuration.
	•	Cached or projected read state SHALL apply to dashboard aggregates, notification counters, `finance_transactions` projections if retained, `finance_balances` projections if retained, and other read models derived from authoritative records.
	•	Any API or report exposing projected state SHALL identify its authoritative source in implementation documentation.
