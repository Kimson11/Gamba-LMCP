# LMCP Laravel Admin Component Inventory

## 1. Purpose

This document maps the approved LMCP web and admin experience to a reusable Laravel component inventory. It is intended to help the portal team implement a maintainable admin UI using shared layouts, shared data table patterns, and consistent permission-aware components.

It should be used alongside:

- [LMCP_UI_Handoff_Spec.md](/Users/apple/Desktop/Developments/LMCP/docs/LMCP_UI_Handoff_Spec.md)
- [LMCP_UI_API_Contract.md](/Users/apple/Desktop/Developments/LMCP/docs/LMCP_UI_API_Contract.md)

Authoritative status:

- This document is normative for Laravel admin component inventory, composition rules, and reusable workflow surfaces.
- It derives from the UI handoff specification and the ratified API contract and must not introduce alternate endpoint, status, or permission semantics.

## 2. Scope

This inventory covers the approved Laravel-facing screens:

- Desktop landing page
- Cooperative Dashboard
- Payout Approval
- Marketplace moderation and vendor review
- Marketplace order operations
- Reports and Export
- Shared web error states
- Shared web empty states

## 3. Component Design Rules

The Laravel implementation should follow these rules:

- Build page composition from reusable layout and panel components
- Separate data presentation components from page orchestration
- Keep permission gating close to action components
- Use one shared status chip component across tables, cards, and drawers
- Use one shared table system across all admin records
- Preserve filters, pagination, and sorting in URL state where appropriate
- Avoid highly bespoke page sections that cannot be reused

## 4. Required Component Layers

### Layer 1: Layout and theme

- App shell
- Sidebar layout
- Top bar layout
- Section container
- Page header layout

### Layer 2: Shared UI primitives

- Buttons
- Chips
- Alerts
- Cards
- Inputs
- Dropdowns
- Tabs

### Layer 3: Data presentation components

- KPI cards
- Exception panels
- Summary cards
- Data tables
- Filter bars
- Detail drawers
- Audit panels

### Layer 4: Workflow components

- Approval queue card
- Decision action group
- Export action group
- Permission guard wrapper

## 5. Required Laravel Building Blocks

Required implementation baseline:

- Blade components for shared UI primitives
- Layout components for app shell and admin pages
- Server-driven filters and pagination for all dense data screens

Interaction implementation may use Blade-only, Livewire, or an approved equivalent, but component responsibility and permission behavior defined here remain mandatory.

## 6. Foundation and Layout Components

### 6.1 `admin-app-shell`

Purpose:

- Main authenticated admin frame

Responsibilities:

- Sidebar region
- Top header region
- Main content slot
- Global search slot
- User menu slot

Used in:

- Dashboard
- Payout Approval
- Reports

### 6.2 `public-site-shell`

Purpose:

- Shared public marketing shell for desktop landing and other pre-auth pages

Responsibilities:

- Public header
- Content slot
- Footer

Used in:

- Desktop landing page

### 6.3 `page-header`

Purpose:

- Standardized page title and actions row

Required props:

- `title`
- `subtitle`
- `actions`
- `breadcrumbs`

Used in:

- Dashboard
- Payout Approval
- Reports

### 6.4 `section-panel`

Purpose:

- Standard card-like panel for grouped content

Required props:

- `title`
- `subtitle`
- `actions`
- `body`

Used in:

- Dashboard sections
- Reports preview sections
- Approval detail sections

## 7. Shared UI Primitives

### 7.1 `status-chip`

Purpose:

- Render a consistent backend-driven state label

Required props:

- `status`
- `label`
- `size`

Supported values:

- `draft`
- `queued`
- `synced`
- `pending_approval`
- `approved`
- `rejected`
- `failed_sync`
- `blocked`
- `finalized`
- `posted`
- `reversed`

Behavior:

- Maps status to icon, tone, and readable label
- Never relies on color alone

Used in:

- Data tables
- Snapshot cards
- Approval detail
- Reports

### 7.2 `primary-button`

Purpose:

- Main page or panel action

Required props:

- `label`
- `href`
- `type`
- `disabled`
- `loading`
- `icon`

### 7.3 `secondary-button`

Purpose:

- Secondary action

Required props:

- `label`
- `href`
- `type`
- `disabled`
- `icon`

### 7.4 `destructive-button`

Purpose:

- Reject, revoke, or delete-style actions

Required props:

- `label`
- `disabled`
- `loading`

Used in:

- Payout rejection

### 7.5 `inline-alert`

Purpose:

- Lightweight informational or warning message

Required props:

- `variant`
- `title`
- `message`
- `action`

Variants:

- `info`
- `warning`
- `error`
- `success`

### 7.6 `empty-state`

Purpose:

- Shared web empty state

Required props:

- `title`
- `message`
- `primaryActionLabel`
- `primaryActionHref`

Used in:

- Dashboard panels
- Reports
- Approval queue

### 7.7 `error-state`

Purpose:

- Shared recoverable page or panel error state

Required props:

- `title`
- `message`
- `primaryActionLabel`
- `primaryActionHref`
- `referenceId`

Used in:

- Dashboard
- Reports
- Approval queue

## 8. Data Presentation Components

### 8.1 `sidebar-nav`

Purpose:

- Primary admin navigation grouped by domain

Required props:

- `items`
- `activeKey`
- `userRole`

Behavior:

- Supports permission-based item visibility
- Groups links into Ops, Marketplace, Finance, Reports, Admin

Used in:

- All authenticated admin pages

### 8.2 `global-search`

Purpose:

- Search bar for admin records and entities

Required props:

- `placeholder`
- `action`
- `value`

Used in:

- Dashboard header

### 8.3 `date-range-filter`

Purpose:

- Shared date range control

Required props:

- `from`
- `to`
- `action`

Used in:

- Dashboard
- Reports

### 8.4 `kpi-card`

Purpose:

- Display a single top-level metric

Required props:

- `label`
- `value`
- `supportingText`
- `trend`

Used in:

- Dashboard

### 8.5 `kpi-strip`

Purpose:

- Render a row or grid of KPI cards

Required props:

- `items`

Used in:

- Dashboard

### 8.6 `exception-panel`

Purpose:

- Surface issues requiring attention

Required props:

- `title`
- `items`
- `actionLabel`
- `actionHref`

Each item:

- `code`
- `label`
- `count`
- `severity`

Used in:

- Dashboard

### 8.7 `approval-queue-card`

Purpose:

- Show approval counts and quick links

Required props:

- `items`

Each item:

- `type`
- `label`
- `count`
- `href`

Used in:

- Dashboard

### 8.8 `snapshot-card`

Purpose:

- Summarize a domain area such as operations, finance, or marketplace

Required props:

- `title`
- `items`
- `actionHref`

Used in:

- Dashboard

### 8.9 `table-filter-bar`

Purpose:

- Shared filtering area for tables

Required props:

- `fields`
- `action`
- `currentFilters`
- `showSavedFilters`

Behavior:

- Preserves filter values
- Submits GET parameters

Used in:

- Payout Approval
- Reports
- Any admin index screen

### 8.10 `saved-filter-control`

Purpose:

- Load and save named filter presets

Required props:

- `presets`
- `currentPreset`
- `saveAction`

Used in:

- Dense admin views if required

### 8.11 `data-table`

Purpose:

- Shared table shell for dense admin records

Required props:

- `columns`
- `rows`
- `pagination`
- `sort`
- `emptyState`

Behavior:

- Server-side pagination
- Server-side or backend-safe sorting
- Sticky header if frontend stack supports it cleanly
- Row actions slot

Used in:

- Payout Approval
- Recent activity on dashboard if tabular
- Reports preview tables where needed

### 8.12 `row-action-menu`

Purpose:

- Standardize per-row actions

Required props:

- `actions`

Each action:

- `label`
- `href` or `method`
- `isAllowed`
- `isDestructive`

Used in:

- Data tables

### 8.13 `detail-drawer`

Purpose:

- Side panel or in-page detail view for selected record

Required props:

- `title`
- `sections`
- `actions`

Used in:

- Payout Approval

### 8.14 `audit-summary-panel`

Purpose:

- Present compact audit and workflow history

Required props:

- `events`
- `title`

Each event:

- `type`
- `actor`
- `timestamp`
- `summary`

Used in:

- Payout Approval
- Reports export detail if needed

### 8.15 `export-action-group`

Purpose:

- Standardize export actions and statuses

Required props:

- `exportOptions`
- `currentStatus`
- `downloadHref`

Supported export statuses:

- `idle`
- `queued`
- `processing`
- `delivered`
- `failed`

Used in:

- Reports

## 9. Workflow Components

### 9.1 `decision-action-group`

Purpose:

- Render approval and rejection actions in a controlled layout

Required props:

- `canApprove`
- `canReject`
- `approveAction`
- `rejectAction`
- `isSubmitting`

Used in:

- Payout Approval

### 9.2 `rejection-reason-form`

Purpose:

- Capture mandatory reject reason

Required props:

- `action`
- `reasonFieldName`
- `errors`

Used in:

- Payout Approval

### 9.3 `permission-guard`

Purpose:

- Hide or disable actions based on capability

Required props:

- `can`
- `mode`
- `fallbackMessage`

Modes:

- `hide`
- `disable`
- `explain`

Used in:

- Action buttons
- Row menus
- Export actions
- Approval controls

## 10. Screen Composition Map

### 10.1 Desktop Landing Page

Required composition:

- `public-site-shell`
- `page-header` variant for public nav
- `section-panel` or dedicated public sections
- `primary-button`
- `secondary-button`
- `inline-alert` only if system or program availability notice is needed

Notes:

- Keep public content componentized but simpler than admin pages
- Marketplace preview should be a reusable teaser section, not a full data table

### 10.2 Cooperative Dashboard

Required composition:

- `admin-app-shell`
- `sidebar-nav`
- `page-header`
- `global-search`
- `date-range-filter`
- `kpi-strip`
- `exception-panel`
- `approval-queue-card`
- `snapshot-card`
- `section-panel`
- `data-table` for recent activity if included
- `empty-state`
- `error-state`

Notes:

- Dashboard widgets should tolerate permission-based hiding
- The screen should still remain balanced if one or more panels are absent

### 10.3 Payout Approval

Required composition:

- `admin-app-shell`
- `sidebar-nav`
- `page-header`
- `table-filter-bar`
- `data-table`
- `detail-drawer`
- `audit-summary-panel`
- `decision-action-group`
- `rejection-reason-form`
- `status-chip`
- `empty-state`
- `error-state`

Notes:

- Read context and decision actions should be visually separated
- Rejection should be impossible without a reason field
- Queue list and selected detail should support server-driven updates after action success

### 10.4 Reports and Export

Required composition:

- `admin-app-shell`
- `sidebar-nav`
- `page-header`
- `date-range-filter`
- `table-filter-bar`
- `section-panel`
- `kpi-card` or summary metric rows
- `data-table` for preview rows where useful
- `export-action-group`
- `inline-alert` for audit note
- `empty-state`
- `error-state`

Notes:

- Reports should stay filter-first
- Export actions must be permission-gated and status-aware

## 11. Route and State Considerations

Laravel pages should preserve:

- Current filters
- Sort order
- Page number
- Active tab
- Selected date range

Required behavior:

- Use query parameters for filter state
- Use redirect-back-with-query behavior after actions where practical
- Keep success and error flash messages short and operational

## 12. Permission and Policy Guidance

Admin UI must assume partial visibility based on role and scope.

Requirements:

- Navigation items should be filtered by ability
- Action buttons should be hidden or disabled according to business rules
- Approval actions must respect SoD and maker-checker rules
- Export actions should only appear for authorized roles
- UI should never imply that an action is available if the backend will always reject it

## 13. Table Behavior Guidance

Shared table rules:

- Pagination is server-side
- Sorting is explicit and server-safe
- Filter fields preserve values on reload
- Empty filtered results show context-aware empty state
- Row actions are grouped into a consistent menu or action slot
- Status chip rendering is consistent across all tables

## 14. Accessibility and Content Guidance

Laravel component requirements:

- Proper heading structure
- Visible focus states
- Explicit labels for filters and actions
- Table headers associated correctly with cells
- Action labels must be specific
- Error panels must explain next steps

Content requirements:

- Short operational copy
- No bureaucratic or overly technical language in user-facing guidance
- Clear reason fields and audit summaries in review flows

## 15. Locked Web Dependencies

The following implementation dependencies are treated as locked and must be adopted rather than re-designed:

- approved frontend approach for interactivity-heavy admin screens from roadmap Phase 0
- detail view behavior chosen for each workflow surface and documented before implementation begins
- export job lifecycle and polling approach from the ratified API contract
- dashboard aggregation endpoint contract from the ratified API contract
- payout request queue and rejection endpoints from the ratified API contract

## 16. Stage 3 Exit Criteria

This component inventory is ready to use when:

- Design signs off on component naming and expected reuse
- Backend confirms final queue and reporting contracts
- Permission rules are aligned with Laravel policies or gates
- The team agrees on the admin interaction approach for filters, drawers, and exports

## 17. Source-Driven Web and Governance Expansion

This section extends the Laravel inventory to cover architecture and design scope that goes beyond the first admin dashboard, payout, and reports pass.

### 17.1 Additional Web Portal Areas

The source documents require additional cooperative and governance-facing surfaces.

Additional screen families that must be planned in the Laravel component system:

- cooperative profile and governance
- clusters and member management
- assets and governance records
- milk operations queue
- feed operations queue
- services management
- marketplace moderation
- vendor verification and vendor management
- marketplace orders and fulfillment oversight
- dispute and case management
- configuration and reference data
- audit and compliance
- access logs
- fraud alerts

### 17.2 Additional Shared Components

Add these reusable components:

#### `member-directory-table`

Purpose:

- list members with scope-aware actions and audit links

Used in:

- member management
- cooperative and cluster drill-down

#### `approval-history-panel`

Purpose:

- render append-only approval event chains

Used in:

- milk approvals
- feed approvals
- payout approvals
- moderation decisions

#### `case-queue-table`

Purpose:

- list disputes, reversals, chargebacks, and regulatory cases

Used in:

- disputes
- case management

#### `vendor-verification-table`

Purpose:

- review vendor applications, status, trust markers, and approval actions

Used in:

- marketplace vendor management
- governance review

#### `order-fulfillment-table`

Purpose:

- present marketplace orders with operational and settlement state side by side

Used in:

- marketplace operations
- processor oversight

#### `fraud-alert-panel`

Purpose:

- show suspicious activity counts, severity, and resolution actions

Used in:

- audit and compliance
- dashboard exception area

#### `config-value-editor`

Purpose:

- edit governed config values by scope and version

Used in:

- configuration

#### `config-change-history-panel`

Purpose:

- show append-only config change history

Used in:

- configuration
- audit and compliance

#### `access-log-table`

Purpose:

- present privileged access and access-log review

Used in:

- audit and compliance

### 17.3 Additional Workflow Components

Add these workflow composites:

#### `ops-approval-workspace`

Purpose:

- standardized review surface for dairy and feed approvals

Used in:

- milk records approval
- feed distribution approval

#### `moderation-review-panel`

Purpose:

- approve or reject marketplace listings with mandatory reason where needed

Used in:

- marketplace moderation

#### `vendor-review-workspace`

Purpose:

- approve, suspend, or reject vendor onboarding and trust status changes

Used in:

- vendor verification

#### `order-fulfillment-workspace`

Purpose:

- review order, fulfillment, receipt, dispute, and settlement progression together

Used in:

- marketplace order operations
- marketplace dispute review

#### `case-detail-workspace`

Purpose:

- review case data, evidence, SLA, holds, and event history

Used in:

- disputes and unified case management

#### `fraud-resolution-workspace`

Purpose:

- review alert evidence and resolution notes

Used in:

- fraud monitoring workflows

### 17.4 Source-Driven Route Coverage

Add route groups or route areas for:

- `/admin/members`
- `/admin/clusters`
- `/admin/milk-operations`
- `/admin/feed-operations`
- `/admin/services`
- `/admin/marketplace`
- `/admin/marketplace/vendors`
- `/admin/marketplace/orders`
- `/admin/finance`
- `/admin/reports`
- `/admin/configuration`
- `/admin/audit`
- `/admin/cases`
- `/admin/fraud-alerts`

### 17.5 Query and Index Awareness

The architecture requires query-driven indexing and dashboard-supported query patterns.

Laravel admin page design should assume:

- date-range filters
- cooperative and cluster filters
- pending-state filters
- export-safe filtered queries

Component implication:

- table components must be designed around query-string driven filters
- dashboard and queue components should tolerate partial datasets and async aggregation freshness

### 17.6 Security and Governance Components

The source architecture adds mandatory privileged-role controls.

Add UI considerations and components for:

- MFA challenge surfaces for privileged roles
- device trust or device revocation views if exposed in portal
- impersonation warning banners if admin impersonation is enabled
- audit summary panels with actor role snapshot
- blocked action explanation states for SoD violations

### 17.7 Notification and Export Operations

Because notification processing and exports are queue-backed in the architecture, web components should support:

- export queued and processing banners
- notification delivery status views where operationally needed
- job-backed retry or retry-later UX for exports

Required components:

- `job-status-banner`
- `export-history-table`
- `notification-delivery-table`

### 17.8 Laravel Inventory Completion Criteria Updated

The Laravel component inventory is not complete until it supports:

- operational queues beyond payouts
- case and dispute handling
- configuration and governance views
- fraud alert visibility
- audit and access-log review
- role- and scope-aware member and cluster administration
