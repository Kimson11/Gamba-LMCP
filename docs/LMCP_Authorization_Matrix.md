# LMCP Authorization Matrix

## 1. Purpose

This document defines the authoritative authorization matrix for LMCP implementation.

It converts architecture-level RBAC, scope, SoD, and privileged-access rules into implementation-ready policy guidance for:

- Laravel policies, gates, middleware, and scoped queries
- Flutter role routing and blocked-state handling
- API authorization behavior
- dashboard, report, and export visibility

This document is normative for action authorization unless the system architecture document explicitly overrides it.

## 2. Enforcement Model

Authorization in LMCP is always the combination of:

- role
- scope
- action
- state or workflow restrictions

Role alone is never sufficient.

Every authorization decision must evaluate:

1. actor identity
2. actor role
3. actor assigned scope
4. target resource scope
5. workflow state restrictions
6. separation-of-duties restrictions
7. privileged-access requirements where applicable

## 3. Scope Hierarchy

The hierarchy is:

`country -> cooperative -> cluster -> member/resource`

Scope inheritance rules:

- `System Admin`
  - all countries
- `Country Administrator`
  - assigned country only
- `Cooperative Administrator`
  - assigned cooperative only
- `Marketplace Manager`
  - assigned cooperative only
- `Treasurer`
  - assigned cooperative only
- `Finance Officer`
  - assigned cooperative only
- `Cluster Supervisor`
  - assigned cooperative and assigned clusters only
- `Veterinary Officer`
  - assigned cooperative and assigned clusters only
- `Paraveterinary Officer`
  - assigned cooperative and assigned clusters only
- `Extension Worker`
  - assigned cooperative and assigned clusters only
- `Member`
  - self-owned records and records explicitly shared back to the member
- `Processor User`
  - approved cooperative procurement scope only
- `Auditor / Observer`
  - explicitly granted read-only audit or report scope only

Non-negotiable scope rules:

- No country-crossing read or write is allowed except for `System Admin`.
- No cooperative-crossing read or write is allowed below country admin.
- Cluster-scoped roles must not read or mutate records outside assigned clusters.
- Processor users must never bypass cooperative governance for settlement, disputes, or protected audit data.

## 4. Role Definitions

### 4.1 System Admin

Responsibilities:

- platform-wide governance
- environment and country oversight
- cross-country exception handling

Constraints:

- must use MFA and trusted device controls
- cannot bypass append-only or ledger invariants
- privileged actions must emit full audit context

### 4.2 Country Administrator

Responsibilities:

- country deployment governance
- country-scoped reporting and policy oversight

Constraints:

- no access outside assigned country
- no silent override of cooperative finance records

### 4.3 Cooperative Administrator

Responsibilities:

- cooperative operations
- member and cluster administration
- approvals where policy permits

Constraints:

- cannot approve own submissions
- no cross-cooperative access

### 4.4 Cluster Supervisor

Responsibilities:

- assigned cluster oversight
- operational review within assigned clusters

Constraints:

- no cooperative-wide administration by default
- no finance approval rights unless separately assigned

### 4.5 Member

Responsibilities:

- self-service operational submission
- own wallet and marketplace participation where permitted

Constraints:

- cannot approve own or others’ sensitive records
- cannot view other members’ protected data

### 4.6 Veterinary Officer / Paraveterinary Officer / Extension Worker

Responsibilities:

- assigned field validation
- visit reports
- advisory and operational verification

Constraints:

- limited to assigned cooperative and clusters
- approval rights depend on configured approval authority rules
- SoD still applies when approval rights exist

### 4.7 Marketplace Manager

Responsibilities:

- listing moderation
- marketplace operational oversight
- delegated marketplace decisions where policy allows

Constraints:

- no finance posting rights unless separately assigned
- must remain within cooperative scope

### 4.8 Finance Officer

Responsibilities:

- initiate cooperative finance workflows
- create payout, withdrawal, disbursement, and posting requests

Constraints:

- cannot approve own sensitive finance actions
- cannot post disbursement-type actions without maker-checker approval where required

### 4.9 Treasurer

Responsibilities:

- approve sensitive finance flows

Constraints:

- cannot approve own initiated records
- cooperative scope only

### 4.10 Processor User

Responsibilities:

- procurement workflow
- offer submission
- intake and quality recording where in scope

Constraints:

- processor access must be approved before settlement-capable actions are enabled
- may submit offers against cooperative-scoped listings
- may not settle directly with member accounts outside cooperative governance
- may not view protected finance or governance data outside granted procurement scope

### 4.11 Auditor / Observer

Responsibilities:

- read-only review

Constraints:

- no mutating actions
- explicit scope grant required

## 5. Sensitive Workflow Rules

### 5.1 Separation of Duties

Applies to:

- operational approvals
- payout approvals
- withdrawals
- loan disbursements
- dividend payouts
- reversals where configured

Rules:

- initiator must not equal approver
- approval actor and role snapshot must be recorded
- approval decisions are append-only
- reversals are new events, not edits

### 5.2 Privileged Access Controls

Mandatory for:

- System Admin
- Country Administrator
- Cooperative Administrator
- Finance Officer
- Treasurer
- Marketplace Manager

Requirements:

- MFA
- trusted-device enrollment
- session revocation support
- correlation-aware audit logging

### 5.3 Export Governance

Rules:

- ordinary users may export only data explicitly allowed by role and scope
- bulk or PII-bearing exports require scoped authorization
- privileged bulk exports require maker-checker approval where policy requires
- all exports must be logged with requestor, scope, legal or operational basis, and artifact reference

## 6. Action Matrix

Legend:

- `A` allowed
- `R` allowed with scope or state restriction
- `M` maker-checker or explicit second-party approval required
- `N` not allowed

| Action | SYS | CADM | COOP | CLUSTER | MEMBER | FIELD | MKT | FIN | TRE | PROC | AUD |
|---|---|---|---|---|---|---|---|---|---|---|---|
| View country-scoped reports | A | A | N | N | N | N | N | N | N | N | R |
| View cooperative dashboard | A | R | A | R | N | R | R | R | R | N | R |
| Manage cooperative profile | A | R | A | N | N | N | N | N | N | N | N |
| Create or update cluster | A | R | A | N | N | N | N | N | N | N | N |
| Register member | A | R | A | R | N | N | N | N | N | N | N |
| View member own profile | A | R | R | R | A | R | N | N | N | N | R |
| View other member protected record | A | R | R | R | N | R | N | N | N | N | R |
| Submit milk log | A | R | R | N | A | R | N | N | N | N | N |
| Approve milk or feed operation | A | R | R | R | N | R | N | N | N | N | N |
| Reverse operational approval | A | R | R | N | N | R | N | N | N | N | N |
| Submit service report | A | R | R | N | R | A | N | N | N | N | N |
| Create listing | A | R | R | R | R | N | R | N | N | N | N |
| Approve or suspend vendor | A | R | R | N | N | N | A | N | N | N | N |
| Submit offer | A | R | R | N | R | N | R | N | N | A | N |
| Moderate listing | A | R | R | N | N | N | A | N | N | N | N |
| View marketplace order | A | R | R | R | R | R | A | N | N | R | R |
| Confirm receipt or quality outcome | A | R | R | N | R | R | A | N | N | R | N |
| Finalize marketplace transaction | A | R | R | N | R | N | A | N | N | R | N |
| View wallet own account | A | R | R | N | A | N | N | N | N | N | N |
| Initiate payout or withdrawal request | A | R | R | N | R | N | N | A | N | N | N |
| Approve payout or withdrawal | A | R | R | N | N | N | N | N | A | N | N |
| Post GL entry | A | N | N | N | N | N | N | A | M | N | N |
| Reverse GL entry | A | N | N | N | N | N | N | R | M | N | N |
| View audit logs | A | R | R | N | N | N | N | N | N | N | A |
| View fraud alerts | A | R | R | N | N | N | N | N | N | N | A |
| Manage config values | A | R | R | N | N | N | N | N | N | N | N |
| Export PII-bearing scoped data | A | R | R | N | N | N | N | N | N | N | R |

Role aliases:

- `SYS` = System Admin
- `CADM` = Country Administrator
- `COOP` = Cooperative Administrator
- `CLUSTER` = Cluster Supervisor
- `FIELD` = Veterinary Officer / Paravet / Extension Worker
- `MKT` = Marketplace Manager
- `FIN` = Finance Officer
- `TRE` = Treasurer
- `PROC` = Processor User
- `AUD` = Auditor / Observer

## 7. Action Constraints by Domain

### 7.1 Cooperative & Identity

- member creation requires cooperative or cluster scope
- member merge, deduplication, suspension, and transfer are admin-only workflows
- cross-cluster transfer requires cooperative-level authorization and audit logging

### 7.2 Dairy, Feed, and Services

- members may create only self-scoped operational submissions
- field roles may validate only within assigned scope
- approval authority is governed by configuration, but SoD always applies

### 7.3 Marketplace

- listings are cooperative-scoped records even when member-originated
- vendor approval and suspension are cooperative or country governance actions
- processor users may browse and submit offers only in approved procurement scope
- order and fulfillment actions must be scoped to actor participation plus cooperative oversight
- finalization requires valid state, server connectivity, and authorized actor
- direct processor-to-member settlement is prohibited

### 7.4 Finance

- only finance workflows may create ledger-affecting requests
- only authorized approval roles may approve sensitive disbursement actions
- ledger posting must use role + workflow + SoD checks, not broad admin privilege

### 7.5 Reports, Audit, and Exports

- report visibility is scope-bound
- audit views must be explicitly granted
- export permissions are narrower than report-view permissions where PII is involved

## 8. API Enforcement Rules

Every protected endpoint must enforce:

- authenticated user
- role check
- scope check
- state transition validity
- SoD where applicable
- privileged-session requirement where applicable

Authorization failure behavior:

- `401` for unauthenticated
- `403` for forbidden or blocked by role or scope
- `409` for valid actor but invalid workflow state
- `422` for business-rule failure where state is valid but inputs are not

Every denial for sensitive actions must emit:

- `correlation_id`
- actor id
- actor role snapshot
- target entity type and id
- denial reason code

## 9. Laravel Implementation Guidance

Implement using:

- route middleware for authentication and privileged-session requirements
- policies for resource-level authorization
- gates for cross-resource ability checks
- query scoping at repository or query layer
- explicit policy methods for every sensitive action

Required policy groups:

- `CooperativePolicy`
- `ClusterPolicy`
- `MemberPolicy`
- `MilkLogPolicy`
- `FeedDistributionPolicy`
- `ServiceReportPolicy`
- `MarketplaceListingPolicy`
- `MarketplaceOfferPolicy`
- `MarketplaceTransactionPolicy`
- `WithdrawalRequestPolicy`
- `GlJournalEntryPolicy`
- `AuditLogPolicy`
- `ConfigValuePolicy`
- `CasePolicy`
- `FraudAlertPolicy`

## 10. Testing Requirements

Minimum test coverage must include:

- role denial outside scope
- country-bound isolation
- cluster-bound isolation
- SoD denial when initiator equals approver
- processor denial outside approved procurement scope
- export denial without approval
- privileged-route denial without MFA or trusted device
- audit visibility only for granted roles

## 11. Change Control

Changes to this matrix require updates to:

- system architecture document where platform rules change
- API contract where authorization-visible behavior changes
- roadmap where phase sequencing changes
- Laravel policies and tests
