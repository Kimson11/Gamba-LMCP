# LMCP UI Handoff Specification

## 1. Purpose

This document is the design-to-engineering handoff specification for LMCP, a livestock management, cooperative operations, and marketplace platform built for low-connectivity, multilingual environments.

It is intended for:

- Product design
- Flutter mobile engineering
- Laravel web and admin engineering
- Product and delivery stakeholders

The goal is to reduce drift between visual design, frontend implementation, and backend behavior.

Authoritative status:

- This document is normative for UI behavior, layout intent, state presentation, and cross-platform UX rules for LMCP.
- Where this document conflicts with widget or component inventory notes, this document wins unless the ratified API contract or system architecture document explicitly overrides it.

## 2. Product Context

LMCP supports the following primary user groups:

- Members (farmers or herders)
- Cooperative administrators
- Processor users
- Secondary operational roles such as extension workers, veterinary staff, finance roles, and auditors

The platform has two primary product surfaces:

- Flutter mobile application for field operations and lightweight procurement workflows
- Laravel web portal for cooperative administration, approvals, reporting, and governance

The product must work reliably in:

- Low-connectivity environments
- Low-end Android device conditions
- Multilingual contexts
- High-trust operational and finance-sensitive workflows

## 3. Product Principles

The following principles are locked and should guide all design and implementation decisions:

- Trust before conversion
- Action-first mobile UX
- Exception-first admin UX
- No color-only meaning
- Icon plus label for key actions and statuses
- Low-literacy-friendly language and navigation
- Clear receipts and status feedback for high-trust actions
- Conservative UI for financial and marketplace finalization flows
- Reusable components over bespoke one-off layouts

## 4. Supported Platforms

### Mobile

- Framework: Flutter
- Primary target: Android-first mobile app
- Layout strategy: single-column, simple stacked flows
- Interaction strategy: large touch targets, minimal depth, offline-aware

### Web

- Framework: Laravel-based portal
- Primary target: desktop admin and oversight workflows
- Layout strategy: 12-column desktop-first data layouts
- Interaction strategy: filters, tables, queues, drill-down views, audit visibility

## 5. Information Architecture Summary

### Public and Pre-auth

- Landing
- Marketplace browse
- How it works
- Trust and governance
- Support
- Login
- Language selector

### Mobile Member

- Home
- Log milk
- Request feed
- Request service
- Wallet
- Marketplace
- Notifications
- Profile and settings
- Sync center

### Mobile Processor

- Home
- Marketplace procurement
- Offer management
- Finalization review
- Settlement view
- Notifications
- Disputes

### Web Cooperative Admin

- Dashboard
- Members
- Milk operations
- Feed operations
- Services management
- Marketplace moderation
- Finance
- Reports
- Configuration
- Audit and compliance

## 6. Design System Foundations

### Typography

Required implementation-safe font stack:

- Primary UI font: Noto Sans
- Monospace and data font: Noto Sans Mono

Type scale:

- Display: 32
- H1: 28
- H2: 22
- H3: 18
- Body: 16
- Secondary: 14
- Caption: 12

Typography rules:

- Sentence case only
- No long paragraphs in task-heavy screens
- Bold reserved for totals, statuses, key numbers, and headings
- Avoid italics
- Labels should be concrete and short

### Color Tokens

Required token set:

- `color.brand.600 = #2F6B3B`
- `color.brand.700 = #25552F`
- `color.brand.050 = #E8F2EA`
- `color.bg.default = #F6F4EE`
- `color.surface.default = #FFFFFF`
- `color.border.default = #D7D2C7`
- `color.text.default = #1F2A1F`
- `color.text.muted = #5F695F`
- `color.state.success = #2E7D4F`
- `color.state.warning = #A56A00`
- `color.state.error = #B33A2B`
- `color.state.info = #1F5E7A`

Color rules:

- All text and controls must meet WCAG 2.1 AA contrast minimums
- Statuses always use icon plus text plus optional color
- Neutral surfaces should dominate over decorative color usage

### Spacing Tokens

Required scale:

- `space.1 = 4`
- `space.2 = 8`
- `space.3 = 12`
- `space.4 = 16`
- `space.5 = 24`
- `space.6 = 32`
- `space.7 = 40`

### Radius Tokens

- `radius.sm = 8`
- `radius.md = 12`
- `radius.lg = 16`

### Breakpoints

Required implementation breakpoints:

- Mobile: 0 to 599
- Tablet: 600 to 1023
- Desktop: 1024 and above

## 7. Backend State Mapping

All UI states must map to backend-safe values and not invented visual-only labels.

Core statuses:

- `draft`
- `queued`
- `synced`
- `pending_approval`
- `approved`
- `rejected`
- `failed_sync`
- `blocked`
- `finalized`

Meaning:

- `draft`: local or not yet submitted state
- `queued`: saved locally, awaiting sync
- `synced`: accepted by server
- `pending_approval`: awaiting human or workflow review
- `approved`: approved and complete
- `rejected`: reviewed and declined, usually with reason
- `failed_sync`: sync attempted but failed
- `blocked`: action cannot proceed because of connectivity, permission, or policy
- `finalized`: irreversible completion state for transaction flows

Every screen that uses a status must use one of the above or another backend-defined enum approved by engineering.

## 8. Shared Component Inventory

These components should become the base reusable library for design and implementation.

### Navigation

- `AppHeader`
- `TopNav`
- `SideNav`
- `BottomNavBar`
- `LanguageSwitcher`
- `RoleBadge`
- `GlobalSearch`

### Actions

- `PrimaryButton`
- `SecondaryButton`
- `TertiaryButton`
- `DestructiveButton`
- `IconLabelButton`

### Surfaces

- `StandardCard`
- `SummaryCard`
- `ActionCard`
- `DetailCard`
- `DrawerPanel`
- `ModalDialog`

### Data and Feedback

- `StatusChip`
- `InfoBanner`
- `OfflineBanner`
- `ReceiptCard`
- `TimelineTracker`
- `EmptyStatePanel`
- `ErrorStatePanel`
- `SkeletonLoader`

### Forms

- `TextField`
- `NumericTextField`
- `PhoneField`
- `TextArea`
- `SelectField`
- `DateField`
- `InlineValidationMessage`
- `StickyBottomActionBar`

### Lists and Tables

- `RecordListItem`
- `NotificationListItem`
- `ListingCard`
- `DataTable`
- `TableFilterBar`
- `RowActionMenu`
- `SavedFilterControl`

### Governance and Workflow

- `ApprovalQueueCard`
- `AuditSummaryPanel`
- `ModerationReviewPanel`
- `ConflictResolutionPanel`

## 9. Screen Inventory

The following screens are in scope for design and implementation handoff.

### Public and Marketing

- Desktop landing page
- Mobile landing page

### Member Mobile

- Home or Today
- Log Milk
- Wallet
- Marketplace Browse
- Marketplace Finalization

### Admin Web

- Cooperative Dashboard
- Payout Approval
- Reports and Export

### Shared States

- Error states
- Empty states

## 10. Screen Specifications

### 10.1 Desktop Landing Page

Purpose:

- Establish trust
- Explain product value
- Route users into the correct role path

Primary components:

- `TopNav`
- `HeroSection`
- `TrustCardGroup`
- `RolePathCards`
- `ProcessSteps`
- `MarketplacePreview`
- `GovernanceInfoBlock`
- `SupportPanel`

Implementation notes:

- Build using standard section blocks that can be implemented cleanly in Laravel views or components
- Avoid highly bespoke hero compositions
- Keep the page responsive and section-based

Required states:

- Default
- Low-content simplified variant for early rollout

### 10.2 Mobile Landing Page

Purpose:

- Provide a trust-first mobile entry point
- Support signup and browse discovery

Primary components:

- `MobileTopBar`
- `HeroBlock`
- `TrustListCards`
- `RoleEntryCards`
- `MarketplaceTeaserCard`
- `SupportCard`

Implementation notes:

- Flutter-friendly single-column flow
- All cards should share padding and radius tokens
- Keep copy short for language expansion

Required states:

- Default
- Low-bandwidth simplified variant

### 10.3 Member Home

Purpose:

- Provide fast access to repeat field tasks
- Surface sync and approval status
- Reassure users that actions are recorded

Primary components:

- `MemberAppBar`
- `SyncStatusChip`
- `SummaryCard`
- `QuickActionCard`
- `AlertListSection`
- `RecordListItem`
- `BottomNavBar`

Implementation notes:

- Flutter single-column layout
- No charts
- Log Milk must be one tap from Home
- Recent activity should support status-specific item rendering

Required states:

- Default
- Empty first-use state
- Queued offline state
- Failed sync state

### 10.4 Log Milk

Purpose:

- Capture daily milk quickly
- Support offline-first save behavior

Primary components:

- `TopAppBar`
- `NumericTextField`
- `OptionalFieldsSection`
- `OfflineBanner`
- `StickyBottomActionBar`
- `ReceiptCard`

Field rules:

- Quantity is required
- Quantity uses numeric keyboard
- Collection time is optional
- Quality notes are optional

Implementation notes:

- Form validation must work inline and on submit
- Save flow must clearly distinguish `queued` versus `synced`

Required states:

- Default
- Validation error
- Queued success
- Synced success
- Save failure

### 10.5 Wallet

Purpose:

- Show internal ledger balance and transaction history
- Support payout request initiation

Primary components:

- `WalletBalanceCard`
- `InfoBanner`
- `PrimaryActionButton`
- `TransactionList`
- `TransactionListItem`

Implementation notes:

- Avoid consumer fintech styling
- Clearly label balance as internal system balance
- Transactions must show status and timestamp

Required states:

- Default
- No transactions
- Pending payout request
- Rejected payout request

### 10.6 Marketplace Browse

Purpose:

- Let members or processors browse vendor-backed marketplace listings and assess trust, fulfillment, and offer options

Primary components:

- `MarketplaceHeader`
- `SearchField`
- `FilterChipRow`
- `ListingCard`
- `VendorBadge`
- `TrustBadge`
- `StatusChip`

Implementation notes:

- Single-column list on mobile
- Support server-backed pagination and filters
- Listing cards should be data-first, not image-first
- Listing detail must surface vendor identity, quality, fulfillment method, and allowed actions

Required states:

- Default
- Loading skeleton
- Empty results
- API error retry state

### 10.7 Marketplace Finalization

Purpose:

- Support high-trust review and finalization of accepted offers within an order-aware marketplace flow

Primary components:

- `StatusHeader`
- `ReviewCard`
- `IdentityRow`
- `SettlementSummaryCard`
- `OrderStatusTimeline`
- `ConnectivityAlert`
- `WarningPanel`
- `StickyActionBar`
- `ReceiptCard`

Implementation notes:

- Must be online-only
- Duplicate submission must resolve safely to existing finalized receipt
- Must clearly separate review from irreversible action

Required states:

- Review ready
- Blocked offline
- Submitting
- Finalized success
- Already finalized
- Permission denied

### 10.7.1 Marketplace Order Tracking

Purpose:

- Let buyers, sellers, and cooperative actors track fulfillment, receipt, and dispute status after commercial agreement

Primary components:

- `StatusHeader`
- `VendorIdentityCard`
- `OrderStatusTimeline`
- `FulfillmentSummaryCard`
- `EvidenceRow`
- `PrimaryActionBar`

Implementation notes:

- Order state and settlement state must be visually distinct
- Actions such as confirm receipt or open dispute must be server-driven by `allowed_actions`
- The screen must support dispute entry without hiding prior operational timeline

Required states:

- Reserved
- In collection or in transit
- Delivered
- Received
- Quality verified
- Disputed
- Settled
- Cancelled

### 10.8 Cooperative Dashboard

Purpose:

- Provide exception-first operational oversight

Primary components:

- `SideNav`
- `PageHeader`
- `DateRangeFilter`
- `GlobalSearch`
- `KpiCard`
- `ExceptionPanel`
- `ApprovalQueueCard`
- `SnapshotCard`
- `DataTable`

Implementation notes:

- Desktop-first layout
- Server-side pagination and filtering
- Panels must tolerate permission-based visibility
- Avoid card overload

Required states:

- Default
- No pending approvals
- Empty filtered table
- Loading
- Error state

### 10.9 Payout Approval

Purpose:

- Support maker-checker review and decision workflows

Primary components:

- `PageHeader`
- `FilterBar`
- `RequestsTable`
- `DetailPanel`
- `IdentitySummaryCard`
- `AuditSummaryCard`
- `DecisionActionGroup`
- `ReasonForm`

Implementation notes:

- Use master-detail or table-plus-drawer pattern
- Rejection must require a reason
- Permission rules must hide or block actions cleanly

Required states:

- Pending queue
- Request selected
- Approve success
- Reject with reason
- Permission blocked
- Empty queue

### 10.10 Reports and Export

Purpose:

- Let admins filter, review, and export operational and financial reporting

Primary components:

- `ReportHeader`
- `FilterToolbar`
- `ReportTypeTabs`
- `SummaryMetricCard`
- `PreviewPanel`
- `ExportActionGroup`
- `AuditNotice`

Implementation notes:

- Filter-first layout
- Empty states must preserve filter context
- Export actions may be permission-gated and server-driven

Required states:

- Operational tab
- Financial tab
- Empty results
- Export in progress
- Export available
- Export error

## 11. Responsive Rules

### Mobile

- Single-column by default
- No dense tables
- Primary CTA should be visible without long scrolling where possible
- Bottom navigation should be limited to 5 primary destinations

### Tablet

- Simple two-column adaptations only where useful
- Prefer list-detail layouts instead of shrinking desktop tables

### Desktop

- 12-column structure for admin
- Use sticky headers, pinned filters, and data tables where needed
- Group content by domain and priority

## 12. State Rules

Every screen with meaningful interaction should account for these UI states where applicable:

- Loading
- Empty
- Error
- Success
- Offline or blocked
- Permission denied

High-trust actions must also support:

- Review state
- Confirmation state
- Receipt state

## 13. Accessibility Requirements

The following accessibility rules are mandatory:

- WCAG 2.1 AA contrast minimum
- Body text minimum 16px on primary task screens
- Visible focus states for all interactive elements
- No color-only communication
- Semantic labels for all form controls
- Screen-reader-friendly page and form structure
- Error messages must explain what happened and what to do next
- Decorative icons and illustrations should be hidden from assistive technologies

## 14. Flutter Implementation Guidance

Required Flutter output from this spec:

- Shared theme tokens mapped to `ThemeData`
- Reusable widgets for all shared components
- Feature modules by screen domain
- Explicit offline queue UI behavior for applicable actions
- State-driven widgets based on backend enums

Flutter-specific notes:

- Optimize for low-end Android performance
- Avoid unnecessary animation in task-critical flows
- Prefer stable layout patterns over visually complex compositions
- Use one shared status chip widget across modules

## 15. Laravel Implementation Guidance

Required Laravel output from this spec:

- Reusable layout and section partials or components
- Shared admin table component with pagination and filtering
- Permission-gated action rendering
- Clear mapping between backend status enums and UI chips
- Server-driven exports and reports

Laravel-specific notes:

- Admin UI should tolerate partial permission visibility
- Reports and queues should assume server-backed filtering
- Audit and finance actions should favor conservative, explicit UI patterns

## 16. Content Guidance

All copy should be:

- Plain-language
- Short
- Direct
- Professional
- Translatable

Avoid:

- Long descriptive paragraphs
- Marketing-heavy slogans
- Ambiguous button labels
- Technical jargon in member-facing flows

## 17. Design-to-Engineering Checklist

Before a screen is considered ready for build, confirm:

- Components are drawn from the shared library
- Tokens are used consistently
- Statuses match backend enums
- Empty, loading, error, and success states are defined
- Permission and offline behavior are defined
- Copy is final enough for implementation
- Screen works in target breakpoint behavior
- Accessibility requirements are accounted for

## 18. Next Deliverables

Required supporting handoff artifacts:

- Design token file
- Component contract by platform
- Screen-by-screen interaction specification
- API-to-UI state mapping document
- Copy and localization spreadsheet

## 19. Screen-by-Screen Interaction Specification

This section translates each approved screen into implementation-facing interaction rules. It is not intended to replace endpoint documentation. It exists to align UX behavior, frontend state handling, and backend response expectations.

### 19.1 Conventions

Interaction contract rules:

- Every screen must define initial load, empty, error, and success behavior
- Every mutating action must define optimistic or non-optimistic behavior
- High-trust actions must define review, submit, and receipt states
- Frontend should not invent business states outside backend-approved enums
- Permission and connectivity failure must be shown as explicit UI states, not generic errors

Response handling rules:

- All list endpoints should be assumed paginated unless explicitly documented otherwise
- All mutating endpoints should return enough data to rehydrate the relevant card, list item, or receipt state
- Validation failures should map to field-level errors where possible
- Authorization failures should map to blocked or permission-denied states
- Retry-safe actions must be idempotent or treated as idempotent in UI behavior

### 19.2 Desktop Landing Page

User goals:

- Understand what LMCP is
- Decide whether to sign in, join, or browse
- Build trust before entering the system

Primary interactions:

- Open page
- Switch language
- Browse marketplace preview
- Navigate to login
- Navigate to role entry point

API notes:

- This page may be fully static for first release
- Marketplace preview may optionally call `GET /marketplace/listings` with a limited public-safe result set if public browse is enabled
- If dynamic, language-aware copy should respect locale selection and server-rendered or client-selected translations

Frontend state notes:

- Default: render full landing page
- Loading: only needed if marketplace preview is dynamic
- Empty: hide preview list and show marketplace teaser copy
- Error: fallback to static teaser if preview request fails

Implementation notes:

- Laravel must implement this as reusable section partials or components
- Avoid coupling trust content to API availability

### 19.3 Mobile Landing Page

User goals:

- Quickly understand product value on mobile
- Choose a role path
- Start authentication or browse safely

Primary interactions:

- Open page
- Change language
- Tap Join as Member
- Tap Login
- Open marketplace browse

API notes:

- Can be shipped as mostly static content in Flutter
- Optional marketplace teaser can consume `GET /marketplace/listings`

Frontend state notes:

- Default: static sections with optional teaser data
- Low-bandwidth mode: collapse preview list into a single marketplace CTA card
- Error: if teaser fails, keep the rest of the page intact

Implementation notes:

- Flutter must pre-bundle localized strings for this screen
- No screen-critical dependency on remote content

### 19.4 Member Home

User goals:

- Complete common tasks quickly
- Know whether recent work synced
- See what needs attention

Primary interactions:

- Open Home
- Tap quick actions
- View sync status
- Open recent activity item
- Retry failed sync from relevant entry point or sync center

Suggested data dependencies:

- Member profile and cooperative context
- Recent milk activity
- Recent feed and service activity
- Wallet summary
- Notification counts or latest items

API notes:

- The ratified API contract defines a dedicated dashboard aggregation endpoint for this screen; implementation must use that role-scoped summary contract rather than composing ad hoc client-side requests.
- Relevant existing source endpoints include `GET /api/v1/cooperatives/{id}`, `POST /api/v1/milk-production`, `GET /finance/accounts/{id}/ledger`, and notification-related services defined in architecture
- Aggregation endpoints must return summary plus recent activity in one payload to reduce mobile round trips.

State mapping:

- `queued`: locally saved record pending sync
- `failed_sync`: previously attempted sync failed
- `pending_approval`: request submitted and awaiting review
- `approved`: request resolved positively
- `rejected`: request declined with reason available

Frontend state notes:

- Initial load: show summary skeletons and action placeholders
- Default: render summary card, attention list, recent activity
- Empty first-use: replace recent activity with guided empty state
- Offline: show `OfflineBanner` and preserve locally cached records
- Error: preserve cached local data if present, otherwise show retry state

Implementation notes:

- Flutter must prioritize cached local data on startup, then reconcile with server.
- Home must not block rendering on all subrequests completing.

### 19.5 Log Milk

User goals:

- Record daily production with minimal friction
- Trust that the record is saved even when offline

Primary interactions:

- Enter quantity
- Optionally add time or quality notes
- Submit record
- View queued or synced confirmation

Primary endpoint:

- `POST /api/v1/milk-production`

Request notes:

- Include `client_request_id` for idempotent retry behavior where supported by sync-write architecture
- Payload should minimally include member identity, quantity, production date, and optional metadata

Response notes:

- Online success should return authoritative server record and resulting state
- Offline save should create a local record with `queued` status and no server response yet

Field validation notes:

- Quantity is required
- Quantity must be numeric and positive
- Client-side validation should run before submission
- Server validation errors should be displayed inline

State transitions:

- Draft -> queued when saved locally offline
- Draft -> synced when posted online and accepted
- Queued -> synced when replay succeeds
- Queued -> failed_sync when replay fails
- Synced -> approved or rejected only if later approval workflow applies

Frontend state notes:

- Submitting online: disable primary action and show progress state
- Queued success: show receipt card with local reference and sync-later messaging
- Synced success: show receipt card with authoritative reference
- Validation error: retain entered values and highlight specific field issues
- Failed sync: keep record visible with retry affordance in sync center or activity history

Implementation notes:

- Flutter must distinguish local persistence success from server acceptance
- UI copy should never imply server acceptance when only local save occurred

### 19.6 Wallet

User goals:

- Understand internal balance
- Review ledger history
- Start payout request flow if allowed

Primary interactions:

- Open wallet
- Review transactions
- Open a transaction detail or receipt
- Initiate payout request

Primary endpoints:

- `GET /finance/accounts/{id}/ledger`
- `POST /finance/withdrawals/request`

Request notes:

- Payout request should create a reviewable approval item, not directly post disbursement
- UI must respect maker-checker and approval policies

Response notes:

- Ledger endpoint should return append-only transactions with timestamps, references, and current status
- Withdrawal request should return new request state, normally `pending_approval`

State mapping:

- `pending_approval`: payout submitted and awaiting decision
- `approved`: request approved and posted
- `rejected`: request rejected with reason
- `blocked`: request not allowed due to balance, policy, or permission

Frontend state notes:

- Loading: skeleton balance card and list rows
- Empty: show informational empty state and explanation of ledger behavior
- Error: preserve cached balance if available
- Success after payout request: either update list inline or show receipt screen then refetch

Implementation notes:

- Wallet must explicitly label balance as internal system balance in phase 1
- Do not present this screen with consumer banking metaphors

### 19.7 Marketplace Browse

User goals:

- Discover relevant listings
- Evaluate vendor trust and fulfillment fit
- Filter by scope and category
- Open listing detail or offer flow

Primary interactions:

- Load listings
- Search or filter
- Paginate or infinite scroll
- Open listing detail

Primary endpoints:

- `GET /marketplace/listings`
- `POST /marketplace/offers`

Request notes:

- Filter inputs should map to server-side query params such as cooperative, cluster, item type, and status
- Offer submission should include `client_request_id`

Response notes:

- Listing response should include trust indicators, seller scope, quantity, and current state
- Listing response should also include vendor identity, fulfillment method, quality summary, and availability window
- Offer creation should return submitted offer record and resulting state

Frontend state notes:

- Loading: skeleton list cards
- Empty: preserve applied filters and show contextual empty state
- Error: show retry action without clearing filters
- Pagination: append additional results without reloading the whole list

Implementation notes:

- Flutter must use a list-first layout and modal filters.
- If member and processor views differ by permissions, backend should return scoped actions and frontend should hide unsupported CTAs

### 19.8 Marketplace Finalization

User goals:

- Review accepted offer details
- Finalize safely
- Receive durable receipt
- Understand whether fulfillment or settlement remains pending after commercial commit

Primary interactions:

- Open finalization review
- Confirm details
- Submit finalization
- View receipt or blocked state

Primary endpoint:

- `POST /marketplace/transactions/finalize`

Request notes:

- Requires `Idempotency-Key` header
- Requires `listing_id` and `offer_id`
- Must be online-only

Response notes:

- Success should return committed transaction details and finance settlement summary
- Duplicate retry should return the original committed transaction result
- Authorization or business-rule failures should return explicit blocked or error semantics
- Where order tracking is enabled, finalization should also return order reference and next-step fulfillment state

State transitions:

- Accepted offer -> finalized on successful atomic commit
- Accepted offer -> blocked if connectivity, permission, or payment confirmation rules fail

Frontend state notes:

- Review ready: show full summary and warning panel
- Blocked offline: disable final action and explain internet requirement
- Submitting: show blocking progress state and prevent duplicate taps
- Finalized success: show receipt screen with transaction ID and timestamp
- Already finalized: show receipt retrieval path instead of generic error
- Permission denied: explain who can finalize

Implementation notes:

- Flutter should not optimistically mark a transaction finalized before server response
- UI must distinguish finalized transaction from completed fulfillment

### 19.8.1 Marketplace Order Tracking

User goals:

- Follow order progress after offer acceptance
- Confirm receipt or raise dispute from the correct operational context

Primary interactions:

- Open order detail
- View vendor and fulfillment summary
- Confirm receipt
- Submit quality verification
- Open dispute

Primary endpoints:

- `GET /marketplace/orders/{id}`
- `POST /marketplace/orders/{id}/confirm-receipt`
- `POST /marketplace/orders/{id}/quality-verify`
- `POST /marketplace/orders/{id}/open-dispute`

State mapping:

- `reserved`
- `in_collection`
- `in_transit`
- `delivered`
- `received`
- `quality_verified`
- `disputed`
- `settled`
- `cancelled`

Implementation notes:

- Timeline state must be server-authored
- Allowed actions must be returned by backend
- Receipt confirmation and dispute initiation must never be inferred from local UI-only state
- Success state must be treated as a receipt, not a transient toast

### 19.9 Cooperative Dashboard

User goals:

- Monitor what needs action
- Review approvals and exceptions
- Drill into operational records

Primary interactions:

- Load dashboard
- Change date range
- Search
- Open queue or data table item
- Export or navigate into domain pages

Suggested data dependencies:

- KPI summary values
- Exceptions
- Pending approvals counts
- Recent records table
- Snapshot summaries per domain

Primary endpoints:

- Existing architecture implies dashboard and report aggregation endpoints
- Related source endpoint: `GET /finance/reports/summary`
- Product and backend must use the dedicated admin dashboard summary endpoint defined in the ratified API contract.

Frontend state notes:

- Loading: show skeleton KPI cards and table placeholders
- Default: show summary and queues based on role scope
- Empty filtered table: keep filters visible and show context-aware empty state
- Error: allow retry without losing filters or date range

Implementation notes:

- Laravel should render role-scoped widgets and hide unauthorized panels entirely where appropriate
- Frontend must not assume all snapshot cards exist for all roles

### 19.10 Payout Approval

User goals:

- Review payout request details
- Approve or reject with confidence
- Preserve auditability

Primary interactions:

- Load queue
- Filter queue
- Select request
- Review details and audit summary
- Approve or reject

Primary endpoints:

- `POST /finance/withdrawals/{id}/approve`
- Pending approval list endpoints must be implemented as part of the ratified API contract baseline.

Request notes:

- Reject path must require reason
- SoD and maker-checker rules must be enforced server-side

Response notes:

- Approve should return resulting posted or approved state
- Reject should return rejected state and recorded reason
- Authorization failures should be returned as blocked or forbidden responses

State transitions:

- `pending_approval` -> `approved`
- `pending_approval` -> `rejected`
- `pending_approval` -> `blocked` if current actor cannot decide

Frontend state notes:

- Queue loading: show table skeleton
- Request selected: load or reveal detail panel
- Approve success: remove item from pending table and show confirmation
- Reject success: remove item from pending table and show recorded reason
- Permission blocked: disable actions and show explanation
- Empty queue: show positive empty state

Implementation notes:

- Laravel should separate read context from decision controls visually and structurally
- Decision actions must not be available until request detail is loaded

### 19.11 Reports and Export

User goals:

- Filter reports
- Review summary output
- Export safely

Primary interactions:

- Select report type
- Adjust filters
- Load report results
- Trigger export

Primary endpoints:

- `GET /finance/reports/summary`
- Additional operational report endpoints should be implemented for milk, feed, services, and marketplace views
- Export endpoints should be server-driven and audit-logged

Request notes:

- Filter state should be serializable in URL on web where appropriate
- Export requests should carry current applied filters

Response notes:

- Summary endpoints should return metrics and optional preview rows
- Export initiation may return processing state and later downloadable artifact

Frontend state notes:

- Loading: keep filter bar visible and show placeholder metrics
- Empty: preserve selected filters
- Export in progress: disable duplicate export trigger and show processing notice
- Export available: show success banner or downloadable artifact
- Export error: retain filters and surface retry

Implementation notes:

- Laravel must keep this screen filter-first and avoid overproducing charts.
- Export actions must be permission-gated

### 19.12 Shared Error-State Contract

Reusable variants:

- `offline`
- `failed_sync`
- `permission_denied`
- `session_expired`
- `rejected`
- `already_finalized`
- `unknown_error`

Required UI fields:

- Title
- Short explanation
- Data safety message when relevant
- Primary next action
- Optional support path
- Optional reference ID

API notes:

- Validation errors map to field-level errors
- Authorization failures map to blocked or permission-denied panels
- Conflict or duplicate finalization should map to state-specific UI, not generic error banners

### 19.13 Shared Empty-State Contract

Reusable variants:

- `no_activity`
- `no_transactions`
- `no_listings`
- `no_approvals`
- `no_reports`
- `no_notifications`

Required UI fields:

- Heading
- Short explanation
- Primary next action
- Optional filter or context reminder

Implementation notes:

- Empty states must preserve user context
- Admin empty states should feel operationally reassuring, not blank

## 20. API Reference Anchors From Source Architecture

The following source endpoints are explicitly referenced in the architecture material and are the current anchor points for UI planning:

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/cooperatives/{id}`
- `GET /api/v1/clusters/{id}/members`
- `POST /api/v1/members`
- `POST /api/v1/milk-production`
- `GET /api/v1/milk-production/cluster/{cluster_id}`
- `POST /api/v1/feed-distributions`
- `GET /api/v1/feed-inventory`
- `GET /marketplace/listings`
- `POST /marketplace/listings`
- `POST /marketplace/offers`
- `POST /marketplace/offers/{id}/decide`
- `GET /marketplace/orders/{id}`
- `POST /marketplace/orders/{id}/confirm-receipt`
- `POST /marketplace/orders/{id}/open-dispute`
- `POST /marketplace/transactions/finalize`
- `POST /finance/contributions`
- `POST /finance/withdrawals/request`
- `POST /finance/withdrawals/{id}/approve`
- `POST /finance/loans/disburse`
- `POST /finance/repayments`
- `GET /finance/accounts/{id}/ledger`
- `GET /finance/reports/summary`

## 21. Contract Dependency

Implementation must use the ratified `LMCP_UI_API_Contract.md` as the backend contract dependency.

Engineering artifacts must reference that contract for:

- endpoint paths
- request and response examples
- enum values used in production
- pagination format
- validation error format
- authorization error format
- export job behavior

## 22. Source-Driven Architecture Coverage Addendum

This addendum extends the handoff spec to cover architectural scope explicitly present in the source documents but only partially represented in the first UI-focused draft.

### 22.1 Additional Role Surfaces Required

The source architecture defines role-specific application flows beyond members, processors, and cooperative admins.

Additional mobile role surfaces that must be covered in implementation:

- Veterinary Officer dashboard
- Paravet dashboard
- Extension Worker dashboard
- Cluster or cooperative-scoped assignment views
- Processor operational dashboard with quantity and quality input

Minimum UI responsibilities by role:

- Veterinary Officer:
  - assigned clusters
  - member visits
  - health record entry
  - nutrition and feed approval where applicable
  - reports
- Paravet:
  - routine care
  - vaccination and treatment logs
  - milk verification
  - reports
- Extension Worker:
  - member verification
  - training and advisory logs
  - production validation
  - reports
- Processor:
  - collection center
  - milk intake
  - quality input
  - daily summary
  - read-only report access in scoped contexts

### 22.2 Mandatory Platform UX Capabilities

The source architecture defines platform primitives that must be visible in UX decisions.

These are not optional implementation details:

- Approval engine with append-only events
- Audit engine with correlation-aware logs
- Idempotency service for sync-write and commit actions
- Notification service with channel delivery status
- Configuration service with scoped rules

UI implications:

- approval-sensitive screens must display reason, actor, and decision history where appropriate
- trust-sensitive screens must surface receipts and stable references
- retry-safe actions must distinguish between queued, processed, and duplicate-safe replay outcomes
- notification inbox patterns should reflect delivery-aware workflows, not only generic message lists
- admin configuration screens must be treated as governed interfaces with audit visibility

### 22.3 Offline Classification as UX Contract

The architecture defines three offline write classes. These should be treated as first-class UX rules.

Class A: Offline-Queueable Writes

- milk logs
- field visit reports
- listing create
- offer submit

UX requirements:

- user can save locally
- record receives queued state immediately
- retry is automatic when connectivity returns
- user can inspect sync status

Class B: Offline Capture-Only Requests

- withdrawal request draft
- dispute draft

UX requirements:

- user can capture intent or draft
- system must not imply commit occurred
- action requires later online confirmation

Class C: Online-Only Commits

- approvals
- maker-checker decisions
- marketplace finalization
- GL posting

UX requirements:

- action is blocked offline
- UI explains why online session is required
- action state must not be queued as if commit is possible

### 22.4 Additional Admin Information Architecture

The design and architecture documents imply more admin surfaces than the first pass emphasized.

Additional web portal areas to include in implementation planning:

- cooperative and cluster management
- member audit trail and status controls
- asset and governance records
- service assignments and visit reporting
- marketplace moderation
- disputes and case management
- vendor verification and marketplace order tracking
- fraud alerts
- audit and tamper-evident log views
- access log views
- configuration and reference data

### 22.5 Dashboard and KPI Coverage

The source architecture defines dashboard and analytics expectations that must shape admin and role-specific UI.

Dashboard coverage required:

- Member dashboard:
  - daily milk total
  - monthly production trend
  - feed received vs expected
- Cooperative dashboard:
  - total milk per cluster
  - active members
  - feed stock vs distribution
  - animal health incidents
- Processor dashboard:
  - aggregate milk availability
  - seasonal trend analysis

Implementation note:

- mobile should avoid dense chart-heavy rendering for field roles
- web may expose richer summaries where operationally necessary
- analytics should be built on scheduled aggregations or PostgreSQL views, not ad hoc UI-side calculations

### 22.6 Security and Trust UX Requirements

The source architecture adds mandatory security behaviors that affect UX.

UI must account for:

- MFA for privileged roles
- trusted-device and device revocation for privileged roles
- session revocation and compromised-session recovery
- suspended account or blocked access states
- lost device recovery and old-session revocation
- audit visibility for privileged decisions

Member-facing UX must remain simple:

- receipts instead of raw audit logs
- plain-language explanation of pending, approved, rejected, and blocked states

Admin-facing UX must expose:

- decision history
- role snapshot
- reason capture
- access and compliance visibility

### 22.7 Configuration and Compliance Screens

The architecture requires governed configuration and country-bound legal controls.

These screen families should be included in final scope planning even if built after MVP:

- approval authority rules
- maker-checker thresholds
- fee and rounding policies
- payout hold windows
- language and template configuration
- country and cooperative-scoped configuration values
- audit and compliance review screens
- privacy and data-hosting policy visibility where required

### 22.8 UX Completion Criteria Updated

A screen family is not fully specified until it accounts for:

- role scope
- offline class behavior
- status and event visibility
- permission denied state
- audit or receipt visibility appropriate to role
- localization and low-literacy behavior
- security-sensitive recovery paths where applicable
