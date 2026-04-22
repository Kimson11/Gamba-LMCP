<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalEvent;
use App\Models\MemberAssignment;
use App\Support\ScopeAccess;
use Illuminate\Contracts\View\View;

class ApprovalHistoryController extends Controller
{
    public function __construct(
        private readonly ScopeAccess $scopeAccess,
    ) {}

    public function __invoke(): View
    {
        return view('admin.assignments.history');
    }

    public function show(MemberAssignment $memberAssignment): View
    {
        $actor = request()->user();

        abort_unless($actor !== null, 401);

        if (! $this->scopeAccess->canAccessCooperative($actor, (int) $memberAssignment->cooperative_id)) {
            abort(403);
        }

        $memberAssignment->load(['member', 'fromCluster', 'toCluster', 'approvedBy', 'rejectedBy']);

        $timeline = ApprovalEvent::query()
            ->with('actor')
            ->where('entity_type', 'member_assignment')
            ->where('entity_id', $memberAssignment->id)
            ->orderBy('id')
            ->get();

        return view('admin.assignments.show', [
            'assignment' => $memberAssignment,
            'timeline' => $timeline,
        ]);
    }
}
