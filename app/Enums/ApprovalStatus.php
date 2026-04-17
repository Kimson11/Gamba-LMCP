<?php

namespace App\Enums;

/**
 * Canonical status values for approval-governed entities.
 *
 * These status values are intentionally aligned with the API contract
 * so both backend and mobile clients can render workflow states consistently.
 */
enum ApprovalStatus: string
{
    // The record has been submitted and is waiting for a privileged reviewer.
    case PendingApproval = 'pending_approval';

    // The request passed review and is now approved.
    case Approved = 'approved';

    // The request was reviewed and explicitly rejected.
    case Rejected = 'rejected';

    // A previously approved record was reversed (with reason).
    case Reversed = 'reversed';
}
