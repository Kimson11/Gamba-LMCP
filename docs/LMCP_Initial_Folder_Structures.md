# LMCP Initial Folder Structures

## 1. Purpose

This document proposes initial folder structures for the LMCP Flutter mobile app and Laravel web/admin app based on the approved:

- UI handoff specification
- UI-to-API contract
- Flutter widget inventory
- Laravel admin component inventory

These structures are intended to be implementation-ready starting points, not rigid final architecture. They prioritize:

- shared tokens and reusable UI
- clear separation of feature modules
- low-friction onboarding for new contributors
- alignment between screens, components, and API contracts

## 2. Architecture Direction

Recommended structure choice for both stacks:

- Feature-first at the application layer
- Shared foundation and design system layers
- Clear split between domain data, UI, and integration boundaries

This is preferable to a purely layer-first structure because LMCP has distinct product areas with separate workflows:

- auth and onboarding
- member operations
- wallet and finance
- marketplace
- reporting
- admin governance

## 3. Flutter Initial Folder Structure

Recommended app root:

```text
flutter_app/
  lib/
    app/
      app.dart
      bootstrap.dart
      router/
        app_router.dart
        route_names.dart
      theme/
        app_theme.dart
        app_colors.dart
        app_text_styles.dart
        app_spacing.dart
        app_radii.dart
        app_shadows.dart
        app_icons.dart
      localization/
        l10n.dart
        strings/
          app_en.arb
          app_fr.arb
          app_ha.arb
    core/
      constants/
        env.dart
        storage_keys.dart
        api_headers.dart
      enums/
        ui_status.dart
        sync_status.dart
        role_type.dart
      errors/
        app_exception.dart
        api_exception.dart
        auth_exception.dart
        validation_exception.dart
      network/
        api_client.dart
        api_result.dart
        paginated_response.dart
        request_id_interceptor.dart
        idempotency_key_provider.dart
      storage/
        secure_storage_service.dart
        local_database.dart
        offline_queue_store.dart
      utils/
        date_formatter.dart
        currency_formatter.dart
        uuid_service.dart
        connectivity_service.dart
      widgets/
        states/
          app_empty_state.dart
          app_error_state.dart
          app_skeleton.dart
        feedback/
          status_chip.dart
          info_banner.dart
          offline_sync_banner.dart
          receipt_card.dart
        buttons/
          primary_button.dart
          secondary_button.dart
          destructive_button.dart
        inputs/
          text_input.dart
          numeric_input.dart
          search_input.dart
          inline_validation_text.dart
        layout/
          app_scaffold.dart
          member_app_scaffold.dart
          form_screen_scaffold.dart
          review_screen_scaffold.dart
        cards/
          standard_card.dart
          action_card.dart
          summary_card.dart
          detail_card.dart
        lists/
          list_row.dart
          paginated_list_view.dart
    features/
      auth/
        data/
          auth_api.dart
          auth_repository_impl.dart
          models/
            auth_user_dto.dart
            login_response_dto.dart
        domain/
          entities/
            auth_user.dart
          repositories/
            auth_repository.dart
          usecases/
            login_usecase.dart
            logout_usecase.dart
        presentation/
          controllers/
            login_controller.dart
          screens/
            login_screen.dart
          widgets/
            login_form.dart
            language_selector.dart
      landing/
        presentation/
          screens/
            landing_screen.dart
          widgets/
            landing_hero_block.dart
            trust_card_list.dart
            role_path_card.dart
            marketplace_teaser_card.dart
      member_home/
        data/
          member_home_api.dart
          member_home_repository_impl.dart
          models/
            member_home_dto.dart
            activity_item_dto.dart
        domain/
          entities/
            member_home_summary.dart
            activity_item.dart
          repositories/
            member_home_repository.dart
          usecases/
            get_member_home_usecase.dart
        presentation/
          controllers/
            member_home_controller.dart
            member_home_state.dart
          screens/
            member_home_screen.dart
          widgets/
            member_summary_card.dart
            quick_action_grid.dart
            quick_action_card.dart
            attention_item_card.dart
            recent_activity_section.dart
      milk_logs/
        data/
          milk_log_api.dart
          milk_log_repository_impl.dart
          models/
            milk_log_dto.dart
            create_milk_log_request.dart
        domain/
          entities/
            milk_log.dart
          repositories/
            milk_log_repository.dart
          usecases/
            create_milk_log_usecase.dart
            sync_milk_log_usecase.dart
        presentation/
          controllers/
            log_milk_controller.dart
            log_milk_state.dart
          screens/
            log_milk_screen.dart
          widgets/
            milk_log_form.dart
            milk_log_receipt_card.dart
      wallet/
        data/
          wallet_api.dart
          wallet_repository_impl.dart
          models/
            ledger_entry_dto.dart
            payout_request_dto.dart
        domain/
          entities/
            wallet_summary.dart
            ledger_entry.dart
          repositories/
            wallet_repository.dart
          usecases/
            get_wallet_usecase.dart
            create_payout_request_usecase.dart
        presentation/
          controllers/
            wallet_controller.dart
            wallet_state.dart
          screens/
            wallet_screen.dart
          widgets/
            wallet_balance_card.dart
            transaction_list_item.dart
            wallet_info_banner.dart
      marketplace/
        data/
          marketplace_api.dart
          marketplace_repository_impl.dart
          models/
            vendor_dto.dart
            listing_dto.dart
            offer_dto.dart
            order_dto.dart
            finalize_transaction_request.dart
            transaction_receipt_dto.dart
        domain/
          entities/
            marketplace_vendor.dart
            marketplace_listing.dart
            marketplace_offer.dart
            marketplace_order.dart
            marketplace_transaction.dart
          repositories/
            marketplace_repository.dart
          usecases/
            get_vendor_profile_usecase.dart
            get_listings_usecase.dart
            submit_offer_usecase.dart
            get_order_usecase.dart
            confirm_order_receipt_usecase.dart
            finalize_transaction_usecase.dart
        presentation/
          controllers/
            marketplace_browse_controller.dart
            marketplace_browse_state.dart
            marketplace_finalize_controller.dart
            marketplace_finalize_state.dart
            marketplace_order_controller.dart
            marketplace_order_state.dart
          screens/
            marketplace_browse_screen.dart
            marketplace_finalize_screen.dart
            marketplace_order_screen.dart
          widgets/
            listing_card.dart
            vendor_trust_badge_row.dart
            filter_chip_row.dart
            marketplace_review_card.dart
            order_timeline_card.dart
            fulfillment_summary_card.dart
            connectivity_requirement_panel.dart
      notifications/
        data/
          notifications_api.dart
          notifications_repository_impl.dart
        domain/
          entities/
            notification_item.dart
          repositories/
            notifications_repository.dart
        presentation/
          controllers/
            notifications_controller.dart
          screens/
            notifications_screen.dart
          widgets/
            notification_list_item.dart
      profile/
        presentation/
          screens/
            profile_screen.dart
            settings_screen.dart
      sync_center/
        data/
          sync_repository_impl.dart
        domain/
          entities/
            sync_queue_item.dart
          repositories/
            sync_repository.dart
        presentation/
          controllers/
            sync_center_controller.dart
            sync_center_state.dart
          screens/
            sync_center_screen.dart
          widgets/
            sync_queue_item_card.dart
    main.dart
  test/
    helpers/
      test_app.dart
      mock_data.dart
    unit/
    widget/
    integration/
```

## 4. Flutter Structure Notes

Why this shape:

- `app/` holds bootstrap, routing, theme, and localization
- `core/` holds cross-feature technical concerns and shared widgets
- `features/` groups by product area, which matches the handoff documents and screen inventory
- each feature separates `data`, `domain`, and `presentation` to keep API details out of widgets

Recommended first build order:

- `app/theme`
- `core/widgets/feedback/status_chip.dart`
- `core/widgets/states`
- `auth`
- `landing`
- `member_home`
- `milk_logs`
- `wallet`
- `marketplace`

Folders to keep intentionally thin at first:

- `domain/usecases` if the team prefers a lighter architecture
- `profile`
- `notifications`

## 5. Laravel Initial Folder Structure

Recommended app root:

```text
laravel_app/
  app/
    Actions/
      Auth/
      Finance/
      Marketplace/
      Reports/
    Domain/
      Auth/
        DataTransferObjects/
        Policies/
        Services/
      Cooperative/
        DataTransferObjects/
        Models/
        Policies/
        Queries/
        Services/
      Dairy/
        Actions/
        DataTransferObjects/
        Models/
        Queries/
        Services/
      Marketplace/
        Actions/
        DataTransferObjects/
        Models/
        Policies/
        Queries/
        Services/
        VendorManagement/
        Orders/
        Fulfillment/
      Finance/
        Actions/
        DataTransferObjects/
        Models/
        Policies/
        Queries/
        Services/
      Reporting/
        Actions/
        DataTransferObjects/
        Queries/
        Services/
      Shared/
        Enums/
        Exceptions/
        ValueObjects/
    Http/
      Controllers/
        Api/
          Auth/
            LoginController.php
            LogoutController.php
          Member/
            MemberHomeController.php
            MilkProductionController.php
            WalletController.php
            NotificationsController.php
          Marketplace/
            ListingsController.php
            OffersController.php
            OrdersController.php
            VendorsController.php
            TransactionsController.php
          Admin/
            DashboardController.php
            PayoutApprovalController.php
            ReportsController.php
        Web/
          Public/
            LandingPageController.php
          Admin/
            DashboardPageController.php
            PayoutApprovalPageController.php
            ReportsPageController.php
      Middleware/
      Requests/
        Auth/
          LoginRequest.php
        Api/
          Marketplace/
            CreateListingRequest.php
            SubmitOfferRequest.php
            ConfirmMarketplaceReceiptRequest.php
          CreateMilkProductionRequest.php
          CreateWithdrawalRequest.php
          FinalizeMarketplaceTransactionRequest.php
          ReportFiltersRequest.php
        Web/
          DashboardFiltersRequest.php
          PayoutQueueFiltersRequest.php
      Resources/
        Auth/
          AuthUserResource.php
        Member/
          MemberHomeResource.php
          MilkProductionResource.php
          WalletLedgerEntryResource.php
        Marketplace/
          ListingResource.php
          OfferResource.php
          OrderResource.php
          VendorResource.php
          TransactionReceiptResource.php
        Admin/
          DashboardSummaryResource.php
          PayoutRequestResource.php
          ReportSummaryResource.php
    Models/
      User.php
    Policies/
    Providers/
  bootstrap/
  config/
    lmcp.php
  database/
    factories/
    migrations/
    seeders/
  public/
  resources/
    views/
      components/
        ui/
          buttons/
            primary-button.blade.php
            secondary-button.blade.php
            destructive-button.blade.php
          chips/
            status-chip.blade.php
          alerts/
            inline-alert.blade.php
            error-state.blade.php
            empty-state.blade.php
          cards/
            section-panel.blade.php
            kpi-card.blade.php
            snapshot-card.blade.php
          forms/
            input.blade.php
            select.blade.php
            date-range-filter.blade.php
            table-filter-bar.blade.php
          tables/
            data-table.blade.php
            row-action-menu.blade.php
            pagination-summary.blade.php
          layout/
            page-header.blade.php
            section-header.blade.php
            detail-drawer.blade.php
      layouts/
        public-site-shell.blade.php
        admin-app-shell.blade.php
      public/
        landing.blade.php
      admin/
        dashboard/
          index.blade.php
          partials/
            kpi-strip.blade.php
            exceptions-panel.blade.php
            approval-queue-card.blade.php
            operations-snapshot.blade.php
            finance-snapshot.blade.php
            marketplace-snapshot.blade.php
        payouts/
          index.blade.php
          partials/
            queue-table.blade.php
            request-detail.blade.php
            audit-summary-panel.blade.php
            decision-action-group.blade.php
            rejection-reason-form.blade.php
        reports/
          index.blade.php
          partials/
            filters.blade.php
            summary-cards.blade.php
            preview-table.blade.php
            export-action-group.blade.php
      partials/
        navigation/
          public-nav.blade.php
          admin-sidebar.blade.php
          admin-topbar.blade.php
  routes/
    api.php
    web.php
  tests/
    Feature/
      Api/
        Auth/
        Member/
        Marketplace/
        Finance/
        Admin/
      Web/
        Admin/
        Public/
    Unit/
      Domain/
      Support/
```

## 6. Laravel Structure Notes

Why this shape:

- `Domain/` keeps business logic grouped by product area instead of scattering it across controllers
- `Http/Controllers/Api` and `Http/Controllers/Web` separate API behavior from page rendering
- `Http/Requests` holds validation close to the request boundary
- `Http/Resources` centralizes API response shaping for the UI contract
- `resources/views/components` becomes the reusable admin UI system
- `resources/views/admin/*/partials` keeps page assembly readable without overloading top-level templates

Recommended first build order:

- `resources/views/layouts`
- `resources/views/components/ui/chips/status-chip.blade.php`
- `resources/views/components/ui/tables/data-table.blade.php`
- `Http/Resources/*`
- `Http/Requests/*`
- `Domain/Finance`
- `Domain/Marketplace`
- `Http/Controllers/Web/Admin`
- `resources/views/admin/dashboard`

Recommended policy alignment:

- keep high-risk permissions in `Domain/*/Policies`
- expose final permission checks through Laravel policies and gates
- make Blade components permission-aware but not policy-owning

## 7. Shared Naming Alignment

To reduce cross-stack drift, use consistent names where possible.

Examples:

- Flutter `status_chip.dart` <-> Laravel `status-chip.blade.php`
- Flutter `receipt_card.dart` <-> Laravel report or transaction receipt partial naming
- Flutter `wallet_balance_card.dart` <-> Laravel `kpi-card.blade.php` or finance-specific card partial
- Flutter `listing_card.dart` <-> Laravel marketplace row or listing card partial

Recommended shared domain labels:

- `member_home`
- `milk_logs`
- `wallet`
- `marketplace`
- `reports`
- `payout_approval`
- `notifications`
- `sync_center`

## 8. API Contract Alignment

Both structures derive from the ratified API contract document and must not redefine it. The API contract remains the implementation source of truth for:

- request and response shapes
- enum values
- pagination
- validation errors
- authorization errors
- idempotency handling

Recommended ownership:

- Laravel `Http/Resources` should produce the stable response shapes
- Flutter feature `data/models` should mirror those resources closely
- enum definitions should be documented once and mapped in both stacks
- every implementation milestone should reference a specific contract revision so folder evolution does not outrun API decisions

## 9. Suggested Repo Layout Options

If Flutter and Laravel live in a single mono-repo:

```text
LMCP/
  docs/
  flutter_app/
  laravel_app/
```

If they live in separate repos:

- keep the `docs/` artifacts in a shared planning repo, or
- duplicate only the implementation-relevant documents into each repo under `docs/architecture`

## 10. Recommended Next Files

If implementation starts from these structures, the most useful next artifacts are:

- Flutter `app_theme.dart` token scaffolding
- Flutter `ui_status.dart` enum
- Flutter `status_chip.dart` widget
- Laravel `status-chip.blade.php`
- Laravel `data-table.blade.php`
- Laravel `MemberHomeResource.php`, `DashboardSummaryResource.php`, and `TransactionReceiptResource.php`

## 11. Cautions

Avoid these early mistakes:

- putting all Flutter screens under a single `screens/` folder with no feature grouping
- putting all Laravel business logic directly into controllers
- creating separate status chip implementations per feature
- mixing UI labels with raw backend enum values inside templates
- designing dashboard widgets before the shared table and chip components exist

## 12. Recommendation

Start with the shared UI primitives and contract-shaping classes first, then build feature screens on top. For LMCP, that will reduce rework significantly because the same status, receipt, approval, and error patterns recur across member, marketplace, finance, and admin flows. Do not create feature modules against undocumented endpoints or draft enum sets.

## 13. Source-Driven Structure Expansion

The initial structure draft focused on the first approved UI slices. This addendum extends both structures to better reflect the full architecture and design source documents.

### 13.1 Flutter Structure Additions

The source architecture requires additional role flows, sync infrastructure, and service modules.

Recommended additional Flutter feature areas:

```text
flutter_app/
  lib/
    features/
      feed/
        data/
        domain/
        presentation/
      services/
        data/
        domain/
        presentation/
      vet_dashboard/
        data/
        domain/
        presentation/
      paravet_dashboard/
        data/
        domain/
        presentation/
      extension_dashboard/
        data/
        domain/
        presentation/
      processor_dashboard/
        data/
        domain/
        presentation/
      approvals/
        data/
        domain/
        presentation/
      disputes/
        data/
        domain/
        presentation/
      onboarding/
        data/
        domain/
        presentation/
```

Recommended additional `core/` and `app/` areas:

```text
flutter_app/
  lib/
    core/
      audit/
        audit_event_mapper.dart
      sync/
        sync_batch_runner.dart
        sync_reconciliation_service.dart
        sync_conflict_mapper.dart
      notifications/
        notification_mapper.dart
      permissions/
        role_router_service.dart
        capability_guard.dart
      database/
        migrations/
        dao/
    app/
      role_router/
        role_router.dart
```

Reasoning:

- the source architecture includes vet, paravet, extension, processor, and sync-center responsibilities
- offline classification and reconciliation deserve their own infrastructure area rather than being hidden inside a single feature

### 13.2 Laravel Structure Additions

The original structure should be extended to cover architecture-mandated domains and operational infrastructure.

Recommended additions:

```text
laravel_app/
  app/
    Domain/
      Feed/
      Personnel/
      Security/
      Audit/
      Configuration/
      Notifications/
      Cases/
      Fraud/
      Payments/
      IoT/
    Jobs/
      Notifications/
      Reports/
      Aggregations/
      Reconciliation/
      Sync/
    Observers/
    Console/
      Commands/
        Reconciliation/
        Monitoring/
    Support/
      Idempotency/
      Correlation/
      Pagination/
      Localization/
```

Recommended additional request and resource folders:

```text
laravel_app/
  app/
    Http/
      Requests/
        Api/
          Feed/
          Marketplace/
          Finance/
          Cases/
          Configuration/
        Web/
          Admin/
            Members/
            Operations/
            Finance/
            Reports/
            Configuration/
            Audit/
      Resources/
        Feed/
        Cases/
        Configuration/
        Notifications/
        Audit/
        Fraud/
```

Recommended additional views:

```text
laravel_app/
  resources/
    views/
      admin/
        members/
        clusters/
        milk-operations/
        feed-operations/
        services/
        marketplace/
        finance/
        cases/
        configuration/
        audit/
        fraud-alerts/
```

### 13.3 Database and Infrastructure-Aware Structure

The source architecture includes more than standard CRUD models. Folder structure should anticipate:

- append-only approval events
- double-entry ledger
- idempotency key handling
- notification delivery tracking
- payment provider events
- fraud alerts
- configuration change events

Recommended migration grouping strategy:

```text
laravel_app/
  database/
    migrations/
      01_identity_and_scope/
      02_cooperative_structure/
      03_dairy_and_feed/
      04_personnel_and_services/
      05_marketplace/
      06_finance_ledger/
      07_platform_primitives/
      08_cases_and_fraud/
      09_i18n_and_config/
```

### 13.4 Folder Strategy Rules Updated

Use these rules when expanding from the starter structure:

- create a first-class module for every architecture domain, not only every UI screen
- separate platform primitives from business domains
- keep sync, audit, idempotency, and notifications reusable across domains
- do not bury ledger logic inside generic finance services
- do not bury approval-event handling inside individual controllers

### 13.5 Recommended Revised Build Order

Folder creation should proceed in this order:

1. shared app and core foundations
2. auth, identity, and scoping
3. idempotency, audit, and notification primitives
4. dairy and feed domains
5. sync infrastructure
6. finance ledger and approval events
7. marketplace
8. admin governance, cases, and fraud
9. reports, exports, and configuration
