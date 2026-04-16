# LMCP Flutter Widget Inventory

## 1. Purpose

This document maps the approved mobile LMCP experience to a reusable Flutter widget inventory. It is intended to help the mobile team build a coherent widget library instead of implementing each screen as a one-off layout.

It should be used alongside:

- [LMCP_UI_Handoff_Spec.md](/Users/apple/Desktop/Developments/LMCP/docs/LMCP_UI_Handoff_Spec.md)
- [LMCP_UI_API_Contract.md](/Users/apple/Desktop/Developments/LMCP/docs/LMCP_UI_API_Contract.md)

Authoritative status:

- This document is normative for Flutter widget inventory, widget responsibility, and screen-to-widget mapping.
- It derives from the UI handoff specification and the ratified API contract and must not redefine backend states, response shapes, or offline classes.

## 2. Scope

This inventory covers the approved Flutter-facing screens:

- Mobile landing page
- Member Home
- Log Milk
- Wallet
- Marketplace Browse
- Marketplace Finalization
- Marketplace Order Tracking
- Shared error states
- Shared empty states

## 3. Widget Design Rules

The Flutter implementation should follow these rules:

- Build reusable widgets before screen-specific composites
- Prefer stateless presentational widgets unless local UI state is needed
- Keep business state outside widgets where possible
- Map backend enums directly to strongly typed UI state models
- Use one shared status chip widget across the app
- Keep mobile layouts single-column or simple stacked structures
- Avoid bespoke one-screen visual patterns

## 4. Required Widget Layers

### Layer 1: Foundation

- Theme tokens
- Typography styles
- Color tokens
- Spacing constants
- Radius constants
- Icon mapping helpers

### Layer 2: Primitive widgets

- Buttons
- Inputs
- Chips
- Banners
- List rows
- Cards

### Layer 3: Composite widgets

- Quick action grid
- Summary card
- Receipt card
- Listing card
- Wallet balance card
- Activity list section

### Layer 4: Screen scaffolds

- Landing scaffold
- Member app scaffold
- Form scaffold
- Marketplace review scaffold

## 5. Foundation Tokens

Required Flutter token structure:

- `AppColors`
- `AppTextStyles`
- `AppSpacing`
- `AppRadii`
- `AppShadows`
- `AppBreakpoints`

Required usage:

- Expose through `ThemeData` and `ThemeExtension`
- Keep screen widgets free of hardcoded colors and spacing

## 6. Primitive Widget Inventory

### 6.1 `LmcpPrimaryButton`

Purpose:

- Main CTA for screen-level action

Required props:

- `label`
- `onPressed`
- `isLoading`
- `isEnabled`
- `icon`

States:

- Default
- Disabled
- Loading

Used in:

- Landing
- Log Milk
- Wallet
- Marketplace Finalization

### 6.2 `LmcpSecondaryButton`

Purpose:

- Secondary action where non-primary choice is needed

Required props:

- `label`
- `onPressed`
- `icon`

States:

- Default
- Disabled

Used in:

- Landing
- Log Milk
- Marketplace Finalization

### 6.3 `LmcpStatusChip`

Purpose:

- Render a backend-driven status consistently

Required props:

- `status`
- `labelOverride`
- `size`

Accepted status values:

- `draft`
- `queued`
- `synced`
- `pending_approval`
- `approved`
- `rejected`
- `failed_sync`
- `blocked`
- `finalized`

Behavior:

- Maps status to icon, color, and localized label
- Must never rely on color alone

Used in:

- Member Home
- Log Milk receipt
- Wallet transactions
- Marketplace listings
- Marketplace finalization

### 6.4 `LmcpInfoBanner`

Purpose:

- Inline informational guidance

Required props:

- `title`
- `message`
- `variant`
- `actionLabel`
- `onAction`

Variants:

- `info`
- `offline`
- `warning`
- `success`

Used in:

- Wallet
- Log Milk
- Finalization review

### 6.5 `LmcpTextInput`

Purpose:

- Standard text entry control

Required props:

- `label`
- `controller`
- `hintText`
- `errorText`
- `keyboardType`
- `textInputAction`
- `enabled`

Used in:

- Log Milk optional notes
- Search fields where local Flutter search exists

### 6.6 `LmcpNumericInput`

Purpose:

- Numeric-first field for quantities and amounts

Required props:

- `label`
- `controller`
- `hintText`
- `errorText`
- `suffixText`
- `enabled`

Behavior:

- Numeric keyboard only
- Supports decimal-safe input if quantity or amount requires it

Used in:

- Log Milk
- Payout request flow when added

### 6.7 `LmcpListRow`

Purpose:

- Reusable row layout for lightweight record lists

Required props:

- `title`
- `subtitle`
- `trailing`
- `leadingIcon`
- `onTap`

Used in:

- Recent activity
- Notifications
- Wallet transactions

### 6.8 `LmcpEmptyStatePanel`

Purpose:

- Shared empty state rendering

Required props:

- `title`
- `message`
- `primaryActionLabel`
- `onPrimaryAction`
- `icon`

Used in:

- Member Home
- Wallet
- Marketplace Browse

### 6.9 `LmcpErrorStatePanel`

Purpose:

- Shared recoverable error UI

Required props:

- `title`
- `message`
- `primaryActionLabel`
- `onPrimaryAction`
- `secondaryActionLabel`
- `onSecondaryAction`
- `referenceId`

Variants:

- Offline
- Permission denied
- Failed sync
- Session expired
- Generic error

Used in:

- All mobile feature screens

### 6.10 `LmcpSkeletonBlock`

Purpose:

- Loading placeholder

Required props:

- `height`
- `width`
- `borderRadius`

Used in:

- Home loading state
- Wallet loading state
- Marketplace list loading state

## 7. Composite Widget Inventory

### 7.1 `LandingHeroBlock`

Purpose:

- Present primary landing value proposition and CTAs

Required props:

- `headline`
- `body`
- `primaryAction`
- `secondaryAction`

Used in:

- Mobile landing page

### 7.2 `TrustCardList`

Purpose:

- Render trust indicators as stacked or compact cards

Required props:

- `items`

Each item:

- `title`
- `description`
- `icon`

Used in:

- Mobile landing page

### 7.3 `RolePathCard`

Purpose:

- Direct users into a role-based path

Required props:

- `title`
- `description`
- `ctaLabel`
- `onTap`
- `icon`

Used in:

- Mobile landing page

### 7.4 `MemberSummaryCard`

Purpose:

- Render top-of-home summary information

Required props:

- `title`
- `cooperativeName`
- `lastMilkLogText`
- `walletLabel`
- `pendingCountText`

Used in:

- Member Home

### 7.5 `QuickActionCard`

Purpose:

- Render a tappable action tile with icon and label

Required props:

- `label`
- `icon`
- `onTap`
- `isEnabled`

Used in:

- Member Home

### 7.6 `QuickActionGrid`

Purpose:

- Group quick action cards into a predictable mobile layout

Required props:

- `actions`

Used in:

- Member Home

### 7.7 `AttentionItemCard`

Purpose:

- Highlight an item needing user attention

Required props:

- `title`
- `message`
- `status`
- `onTap`

Used in:

- Member Home

### 7.8 `RecentActivitySection`

Purpose:

- Present recent records or events

Required props:

- `title`
- `items`
- `onItemTap`

Each item:

- `id`
- `title`
- `timestamp`
- `status`
- `type`

Used in:

- Member Home

### 7.9 `OfflineSyncBanner`

Purpose:

- Communicate device sync state

Required props:

- `status`
- `queuedCount`
- `lastSyncAt`
- `onTapDetails`

Supported states:

- `online`
- `offline`
- `syncing`
- `failed`

Used in:

- Member Home
- Log Milk

### 7.10 `ReceiptCard`

Purpose:

- Durable confirmation block for successful or queued actions

Required props:

- `title`
- `status`
- `reference`
- `timestamp`
- `summaryRows`

Used in:

- Log Milk
- Marketplace Finalization

### 7.11 `WalletBalanceCard`

Purpose:

- Display internal balance and wallet explanation

Required props:

- `balanceLabel`
- `balanceValue`
- `supportingText`
- `primaryActionLabel`
- `onPrimaryAction`

Used in:

- Wallet

### 7.12 `TransactionListItem`

Purpose:

- Render a single ledger transaction row

Required props:

- `reference`
- `entryType`
- `amount`
- `timestamp`
- `status`
- `onTap`

Used in:

- Wallet

### 7.13 `ListingCard`

Purpose:

- Render marketplace listing summary with vendor trust and fulfillment context

Required props:

- `title`
- `sellerName`
- `quantityText`
- `qualitySummary`
- `fulfillmentSummary`
- `status`
- `trustFlags`
- `onTap`

Used in:

- Marketplace Browse

### 7.14 `FilterChipRow`

Purpose:

- Lightweight filter summary and quick toggles

Required props:

- `chips`
- `onChipTap`

Used in:

- Marketplace Browse

### 7.15 `MarketplaceReviewCard`

Purpose:

- Present listing and offer review content before finalization

Required props:

- `listingTitle`
- `sellerName`
- `buyerName`
- `quantityText`
- `qualityText`
- `settlementText`
- `nextStepText`

Used in:

- Marketplace Finalization

### 7.16 `ConnectivityRequirementPanel`

Purpose:

- Explain when an action requires live network access

Required props:

- `title`
- `message`
- `isBlocking`

Used in:

- Marketplace Finalization

### 7.17 `VendorTrustBadgeRow`

Purpose:

- Render vendor verification and marketplace trust signals consistently

Required props:

- `vendorName`
- `verificationStatus`
- `trustFlags`

Used in:

- Marketplace Browse
- Listing detail
- Marketplace Finalization

### 7.18 `OrderTimelineCard`

Purpose:

- Present marketplace order and fulfillment status separately from settlement status

Required props:

- `orderStatus`
- `settlementStatus`
- `timelineItems`

Used in:

- Order tracking
- Marketplace Finalization follow-up

### 7.19 `FulfillmentSummaryCard`

Purpose:

- Summarize pickup, delivery, quality, and receipt context for a marketplace order

Required props:

- `fulfillmentMethod`
- `locationSummary`
- `deliveryTerms`
- `qualitySummary`

Used in:

- Listing detail
- Order tracking

## 8. Screen Scaffold Inventory

### 8.1 `LandingScreenScaffold`

Purpose:

- Shared scaffold for mobile pre-auth pages

Contents:

- Safe area
- Scroll view
- Top bar
- Section spacing

Used in:

- Mobile landing page

### 8.2 `MemberAppScaffold`

Purpose:

- Shared scaffold for signed-in member flows

Contents:

- App bar
- Page body
- Bottom navigation
- Optional sync banner slot

Used in:

- Member Home
- Wallet
- Marketplace Browse

### 8.3 `FormScreenScaffold`

Purpose:

- Standard scaffold for task-focused mobile forms

Contents:

- Top app bar
- Scrollable form content
- Sticky bottom action bar

Used in:

- Log Milk

### 8.4 `ReviewScreenScaffold`

Purpose:

- Standard scaffold for review-and-confirm flows

Contents:

- App bar
- Scrollable review content
- Sticky bottom confirmation bar

Used in:

- Marketplace Finalization

## 9. Screen-to-Widget Mapping

### 9.1 Mobile Landing Page

Required composition:

- `LandingScreenScaffold`
- `LandingHeroBlock`
- `LmcpPrimaryButton`
- `LmcpSecondaryButton`
- `TrustCardList`
- `RolePathCard`
- `LmcpInfoBanner` if low-bandwidth or limited availability notice is needed

### 9.2 Member Home

Required composition:

- `MemberAppScaffold`
- `OfflineSyncBanner`
- `MemberSummaryCard`
- `QuickActionGrid`
- `AttentionItemCard`
- `RecentActivitySection`
- `LmcpEmptyStatePanel`
- `LmcpErrorStatePanel`

### 9.3 Log Milk

Required composition:

- `FormScreenScaffold`
- `LmcpNumericInput`
- `LmcpTextInput`
- `LmcpInfoBanner`
- `LmcpPrimaryButton`
- `LmcpSecondaryButton`
- `ReceiptCard`
- `LmcpErrorStatePanel`

### 9.4 Wallet

Required composition:

- `MemberAppScaffold`
- `WalletBalanceCard`
- `LmcpInfoBanner`
- `TransactionListItem`
- `LmcpEmptyStatePanel`
- `LmcpErrorStatePanel`

### 9.5 Marketplace Browse

Required composition:

- `MemberAppScaffold`
- `LmcpTextInput` or dedicated search field variant
- `FilterChipRow`
- `ListingCard`
- `VendorTrustBadgeRow`
- `LmcpSkeletonBlock`
- `LmcpEmptyStatePanel`
- `LmcpErrorStatePanel`

### 9.6 Marketplace Finalization

Required composition:

- `ReviewScreenScaffold`
- `LmcpStatusChip`
- `MarketplaceReviewCard`
- `OrderTimelineCard`
- `ConnectivityRequirementPanel`
- `LmcpInfoBanner`
- `LmcpPrimaryButton`
- `LmcpSecondaryButton`
- `ReceiptCard`
- `LmcpErrorStatePanel`

### 9.7 Marketplace Order Tracking

Required composition:

- `ReviewScreenScaffold`
- `LmcpStatusChip`
- `VendorTrustBadgeRow`
- `OrderTimelineCard`
- `FulfillmentSummaryCard`
- `LmcpPrimaryButton`
- `LmcpSecondaryButton`
- `LmcpInfoBanner`
- `LmcpErrorStatePanel`

## 10. State Ownership Guidance

Required ownership model:

- Remote business state lives in feature controller, view model, notifier, bloc, or equivalent state holder
- Widgets receive display-ready values and callbacks
- Form field controllers can live in screen state or form controller
- Shared widgets should not call repositories or APIs directly

Required per-screen state models:

- `MemberHomeUiState`
- `LogMilkUiState`
- `WalletUiState`
- `MarketplaceBrowseUiState`
- `MarketplaceFinalizeUiState`
- `MarketplaceOrderUiState`

Each state model should account for:

- `loading`
- `data`
- `empty`
- `error`
- feature-specific statuses such as `queued`, `failed_sync`, `pending_approval`, or `finalized`

## 11. Localization Guidance

Flutter widgets must be safe for translated copy expansion.

Requirements:

- No hardcoded English strings inside shared widgets
- Labels sourced from localization keys
- Status chip labels resolved through localization map
- Layouts must tolerate longer French labels and Hausa variants

## 12. Accessibility and Device Guidance

Flutter widget requirements:

- Minimum comfortable tap targets
- Text scaling should not break key task flows
- Focus and semantics should be added to actionable widgets
- Icons should not carry meaning alone
- Loading and error states should be screen-reader safe

Device guidance:

- Avoid heavy image use
- Prefer lightweight shadows and stable layouts
- Keep scrolling predictable

## 13. Locked Mobile Dependencies

The following implementation dependencies are treated as locked and must be adopted rather than re-designed:

- state management approach chosen in roadmap Phase 0
- offline storage and sync architecture owned by the shared mobile platform layer
- notification list endpoint and model from the ratified API contract
- marketplace browse search behavior driven by server query params and approved filter model
- payout request inclusion determined by phase scope, not widget-level reinterpretation

## 14. Stage 2 Exit Criteria

This widget inventory is ready to use when:

- Design signs off on widget naming and intended reuse
- Engineering confirms state ownership approach
- API contract confirms final enum values
- Tokens are ready to map into Flutter theme extensions

## 15. Source-Driven Role and Architecture Expansion

This section extends the widget inventory to cover additional mobile scope defined in the source architecture and design documents.

### 15.1 Additional Mobile Role Coverage

The source documents define mobile flows for more than members.

Additional role-specific feature modules must be included for:

- veterinary officer
- paravet
- extension worker
- processor user
- marketplace order participants where mobile order tracking is in scope
- cooperative admin lightweight mobile review flows where justified

Required additional feature folders:

- `features/vet_dashboard/`
- `features/paravet_dashboard/`
- `features/extension_dashboard/`
- `features/processor_dashboard/`
- `features/approvals/` for mobile review-only or deferred review tasks where phase scope includes mobile approval visibility
- `features/services/` for health visits, advisory logs, and service assignments

### 15.2 Additional Screen Scaffolds

Add these scaffold patterns:

- `RoleDashboardScaffold`
- `AssignedScopeScaffold`
- `SyncAwareFormScaffold`
- `ReadOnlySummaryScaffold`

Used for:

- vet dashboard
- extension and paravet assignments
- processor intake and summary views

### 15.3 Additional Primitive Widgets

Add these shared widgets:

#### `LmcpTimelineTracker`

Purpose:

- render approval or transaction progress

Used in:

- payout status
- marketplace finalization
- case progression

#### `LmcpNotificationListItem`

Purpose:

- reusable notification row with delivery-aware metadata

Used in:

- inbox
- alert center

#### `LmcpPermissionStatePanel`

Purpose:

- explicit blocked state for permission mismatch

Used in:

- all role-gated screens

#### `LmcpConflictResolutionCard`

Purpose:

- show server-authoritative conflict resolution result or choice state

Used in:

- sync center
- edit collision flows

#### `LmcpLastSyncIndicator`

Purpose:

- compact display for last successful sync timestamp

Used in:

- app bar
- sync center

### 15.4 Additional Composite Widgets

Add these composites to match source flows:

#### `AssignedClusterList`

Used in:

- vet dashboard
- paravet dashboard
- extension worker dashboard

#### `VisitReportForm`

Used in:

- vet
- paravet
- extension worker

#### `HealthRecordCard`

Used in:

- animal care and visit log screens

#### `FeedConfirmationCard`

Used in:

- member feed confirmation

#### `ProcessorIntakeSummaryCard`

Used in:

- processor dashboard
- quality input summaries

#### `MarketplaceOrderCard`

Used in:

- my offers
- order tracking
- processor dashboard

#### `CaseSummaryCard`

Used in:

- disputes
- holds
- regulatory issue summaries if mobile access is allowed

### 15.5 Offline-Class Widget Behavior Rules

The source architecture defines mandatory offline classes that should shape widget behavior.

Class A widgets:

- must support queued state
- must be restart-safe
- must show local save confirmation

Class B widgets:

- may store draft locally
- must not show committed success state

Class C widgets:

- must disable primary commit action while offline
- must show a blocked explanation panel

This rule affects:

- log milk
- service reports
- listing create
- offer submit
- payout request confirmation
- finalization
- approvals

### 15.6 Additional Mobile State Models

Add these UI state objects:

- `SyncCenterUiState`
- `NotificationsUiState`
- `VetDashboardUiState`
- `ParavetDashboardUiState`
- `ExtensionDashboardUiState`
- `ProcessorDashboardUiState`
- `CaseListUiState`

### 15.7 Source-Driven Data Services

The source architecture explicitly calls for:

- encrypted local SQLite storage
- sync queue
- role-based routing
- server-authoritative conflict handling

Required additions under `core/` or `services/`:

- `sync_reconciliation_service.dart`
- `conflict_resolution_mapper.dart`
- `role_router_service.dart`
- `notification_delivery_mapper.dart`
- `approval_event_mapper.dart`

### 15.8 Accessibility and Low-Literacy Reinforcement

The source docs explicitly require:

- icon-first navigation
- short labels
- numeric-heavy data entry
- no deep nesting beyond 3 levels

Additional mobile widget rules:

- all dashboard cards should cap visible text density
- numeric values should be visually stronger than explanatory text
- member-facing forms should avoid more than a few visible fields at once
- processor and field-role dashboards should keep primary actions on the first viewport

### 15.9 Widget Inventory Completion Criteria Updated

The Flutter widget inventory is not complete until it supports:

- all mobile roles defined in source architecture
- sync center and conflict states
- notifications and delivery-aware inbox patterns
- approval and timeline patterns
- field-service capture widgets
- processor quantity and quality entry widgets
