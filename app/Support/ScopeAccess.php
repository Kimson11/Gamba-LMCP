<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Cluster;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeAccess
{
    /**
     * Return the linked member profile for a member actor when available.
     */
    public function linkedMember(User $actor): ?Member
    {
        return Member::query()
            ->where('user_id', $actor->id)
            ->first();
    }

    /**
     * Return a normalized scope payload suitable for API responses.
     *
     * @return array{cooperative_ids:list<int>,cluster_ids:list<int>,member_id:int|null}
     */
    public function summary(User $actor): array
    {
        $linkedMember = $this->linkedMember($actor);

        return [
            'cooperative_ids' => $this->accessibleCooperativeIds($actor),
            'cluster_ids' => $this->accessibleClusterIds($actor),
            'member_id' => $linkedMember?->id,
        ];
    }

    /**
     * Filter a cooperative query to the actor's accessible scope.
     */
    public function applyCooperativeScope(User $actor, Builder $query, string $column = 'id'): Builder
    {
        if ($this->isUnrestricted($actor)) {
            return $query;
        }

        $cooperativeIds = $this->accessibleCooperativeIds($actor);

        if ($cooperativeIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $cooperativeIds);
    }

    /**
     * Filter a cluster query to the actor's accessible scope.
     */
    public function applyClusterScope(User $actor, Builder $query, string $clusterColumn = 'id', string $cooperativeColumn = 'cooperative_id'): Builder
    {
        if ($this->isUnrestricted($actor)) {
            return $query;
        }

        $clusterIds = $this->accessibleClusterIds($actor);

        if ($clusterIds !== []) {
            return $query->whereIn($clusterColumn, $clusterIds);
        }

        $cooperativeIds = $this->accessibleCooperativeIds($actor);

        if ($cooperativeIds !== []) {
            return $query->whereIn($cooperativeColumn, $cooperativeIds);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Determine whether the actor can access the given cooperative.
     */
    public function canAccessCooperative(User $actor, Cooperative|int $cooperative): bool
    {
        if ($this->isUnrestricted($actor)) {
            return true;
        }

        $cooperativeId = $cooperative instanceof Cooperative
            ? $cooperative->id
            : $cooperative;

        return in_array($cooperativeId, $this->accessibleCooperativeIds($actor), true);
    }

    /**
     * Determine whether the actor can access the given cluster.
     */
    public function canAccessCluster(User $actor, Cluster|int $cluster): bool
    {
        if ($this->isUnrestricted($actor)) {
            return true;
        }

        $resolvedCluster = $cluster instanceof Cluster
            ? $cluster
            : Cluster::query()->find($cluster);

        if ($resolvedCluster === null) {
            return false;
        }

        $clusterIds = $this->accessibleClusterIds($actor);

        if ($clusterIds !== []) {
            return in_array($resolvedCluster->id, $clusterIds, true);
        }

        return $this->canAccessCooperative($actor, $resolvedCluster->cooperative_id);
    }

    /**
     * Determine whether the actor can access the given member.
     */
    public function canAccessMember(User $actor, Member|int $member): bool
    {
        $resolvedMember = $member instanceof Member
            ? $member
            : Member::query()->find($member);

        if ($resolvedMember === null) {
            return false;
        }

        if ($this->isUnrestricted($actor)) {
            return true;
        }

        if ($actor->role === UserRole::Member) {
            return $this->linkedMember($actor)?->id === $resolvedMember->id;
        }

        if ($resolvedMember->cluster_id !== null && $this->canAccessCluster($actor, $resolvedMember->cluster_id)) {
            return true;
        }

        return $this->canAccessCooperative($actor, $resolvedMember->cooperative_id);
    }

    /**
     * Return cooperative IDs available to the actor.
     *
     * @return list<int>
     */
    public function accessibleCooperativeIds(User $actor): array
    {
        $cooperativeIds = $actor->scopes()
            ->where('scope_type', 'cooperative')
            ->pluck('scope_id')
            ->map(fn (mixed $scopeId): int => (int) $scopeId)
            ->all();

        if ($cooperativeIds !== []) {
            return array_values(array_unique($cooperativeIds));
        }

        $clusterIds = $this->accessibleClusterIds($actor);

        if ($clusterIds !== []) {
            return Cluster::query()
                ->whereIn('id', $clusterIds)
                ->pluck('cooperative_id')
                ->map(fn (mixed $cooperativeId): int => (int) $cooperativeId)
                ->unique()
                ->values()
                ->all();
        }

        $linkedMember = $this->linkedMember($actor);

        if ($linkedMember !== null) {
            return [(int) $linkedMember->cooperative_id];
        }

        return [];
    }

    /**
     * Return cluster IDs available to the actor.
     *
     * @return list<int>
     */
    public function accessibleClusterIds(User $actor): array
    {
        $clusterIds = $actor->scopes()
            ->where('scope_type', 'cluster')
            ->pluck('scope_id')
            ->map(fn (mixed $scopeId): int => (int) $scopeId)
            ->all();

        if ($clusterIds !== []) {
            return array_values(array_unique($clusterIds));
        }

        $linkedMember = $this->linkedMember($actor);

        if ($linkedMember?->cluster_id !== null) {
            return [(int) $linkedMember->cluster_id];
        }

        return [];
    }

    /**
     * Determine whether the actor should bypass explicit scope filters.
     */
    private function isUnrestricted(User $actor): bool
    {
        if ($actor->role === UserRole::SystemAdmin) {
            return true;
        }

        if ($actor->role === UserRole::Member) {
            return false;
        }

        return false;
    }
}
