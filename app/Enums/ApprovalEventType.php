<?php

namespace App\Enums;

/**
 * Append-only event types for the approval event stream.
 *
 * Every state transition is represented by one immutable event row.
 */
enum ApprovalEventType: string
{
    // Initial workflow entry point.
    case Submitted = 'submitted';

    // Reviewer accepted the request.
    case Approved = 'approved';

    // Reviewer declined the request.
    case Rejected = 'rejected';

    // Reviewer reversed a previously approved request.
    case Reversed = 'reversed';
}
