<?php

namespace App\Enums;

/**
 * Canonical role catalog for the entire LMCP platform.
 *
 * Every user has exactly one role. That role, combined with
 * their scope (country / cooperative / cluster), determines
 * what they are allowed to read and write.
 *
 * Role hierarchy (broadest → narrowest access):
 *   SystemAdmin → CountryAdmin → CoopAdmin → ClusterSupervisor
 *   → FieldRoles (Vet, Paravet, ExtensionWorker)
 *   → Member / Processor / Finance / Marketplace / Auditor
 *
 * Privileged roles (require MFA / trusted-device session):
 *   SystemAdmin, CountryAdmin, CoopAdmin, FinanceOfficer, Treasurer, MarketplaceManager
 */
enum UserRole: string
{
    // ─── System-level ────────────────────────────────────────────────────
    // Full access across all countries; used only for platform maintenance.
    case SystemAdmin = 'system_admin';

    // ─── Country-level ───────────────────────────────────────────────────
    // Reports, policy enforcement, and exception handling within one country.
    case CountryAdmin = 'country_admin';

    // ─── Cooperative-level ───────────────────────────────────────────────
    // Manages members, clusters, and approvals for a single cooperative.
    case CoopAdmin = 'coop_admin';

    // Supervisors review operational data for their assigned clusters only.
    case ClusterSupervisor = 'cluster_supervisor';

    // ─── Field roles ─────────────────────────────────────────────────────
    // Veterinary officer: health visits and reports within assigned clusters.
    case VetOfficer = 'vet_officer';

    // Paravet: assists vet, limited report access.
    case Paravet = 'paravet';

    // Extension worker: advisory visits within assigned clusters.
    case ExtensionWorker = 'extension_worker';

    // ─── Operational roles ───────────────────────────────────────────────
    // Farmer member: logs milk, views wallet, browses/offers on marketplace.
    case Member = 'member';

    // Verified vendor: can browse listings, submit offers, and — once
    // vendor-verified — finalize marketplace transactions.
    case Processor = 'processor';

    // ─── Finance roles (SoD-sensitive) ───────────────────────────────────
    // Initiates finance workflows; cannot approve their own submissions.
    case FinanceOfficer = 'finance_officer';

    // Approves sensitive finance actions; cannot approve their own initiations.
    case Treasurer = 'treasurer';

    // ─── Governance roles ────────────────────────────────────────────────
    // Moderates listings and vendor verifications within a cooperative.
    case MarketplaceManager = 'marketplace_manager';

    // Read-only access with an explicitly granted scope.
    case Auditor = 'auditor';

    // ─── Helpers ─────────────────────────────────────────────────────────

    /**
     * Returns the roles that require a privileged session
     * (MFA + trusted device) before performing sensitive actions.
     *
     * Example privileged roles:
     *   UserRole::SystemAdmin, UserRole::Treasurer, etc.
     *
     * @return list<self>
     */
    public static function privileged(): array
    {
        return [
            self::SystemAdmin,
            self::CountryAdmin,
            self::CoopAdmin,
            self::FinanceOfficer,
            self::Treasurer,
            self::MarketplaceManager,
        ];
    }

    /**
     * Whether this specific role requires MFA / trusted-device enforcement.
     *
     * Usage:
     *   if ($user->role->isPrivileged()) { // require MFA session }
     */
    public function isPrivileged(): bool
    {
        return in_array($this, self::privileged(), strict: true);
    }
}
