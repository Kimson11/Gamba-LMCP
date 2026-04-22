<?php

return [
    'errors' => [
        'unauthenticated' => 'Unauthenticated.',
        'permission_denied' => 'You are not authorized to perform this action.',
        'validation_failed' => 'Validation failed.',
        'idempotency_key_required' => 'Idempotency-Key header is required for write requests.',
        'idempotency_payload_mismatch' => 'Request conflicts with a previous submission for this idempotency key.',
        'member_profile_not_found' => 'Member profile not found for the authenticated user.',
        'notification_not_found' => 'Notification not found for the current user.',
    ],
];
