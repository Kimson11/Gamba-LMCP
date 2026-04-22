<?php

namespace App\Livewire\Admin;

use App\Models\ApprovalEvent;
use App\Models\MemberAssignment;
use App\Models\User;
use App\Support\ScopeAccess;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalHistory extends Component
{
    use WithPagination;

    public string $statusFilter = 'resolved';

    public string $search = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 401);

        $scopeAccess = app(ScopeAccess::class);

        $baseQuery = MemberAssignment::query()
            ->with(['member', 'fromCluster', 'toCluster', 'approvedBy', 'rejectedBy'])
            ->latest('id');

        $scopeAccess->applyCooperativeScope($actor, $baseQuery, 'cooperative_id');

        match ($this->statusFilter) {
            'pending' => $baseQuery->where('approval_status', 'pending_approval'),
            'approved' => $baseQuery->where('approval_status', 'approved'),
            'rejected' => $baseQuery->where('approval_status', 'rejected'),
            'all' => null,
            default => $baseQuery->whereIn('approval_status', ['approved', 'rejected']),
        };

        if ($this->search !== '') {
            $search = '%'.$this->search.'%';

            $baseQuery->whereHas('member', function ($memberQuery) use ($search): void {
                $memberQuery
                    ->where('member_number', 'like', $search)
                    ->orWhere('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search);
            });
        }

        $assignments = $baseQuery->paginate(10);

        $timelines = ApprovalEvent::query()
            ->with('actor')
            ->where('entity_type', 'member_assignment')
            ->whereIn('entity_id', $assignments->getCollection()->pluck('id')->all())
            ->orderBy('id')
            ->get()
            ->groupBy('entity_id');

        return view('livewire.admin.approval-history', [
            'assignments' => $assignments,
            'timelines' => $timelines,
        ]);
    }
}
