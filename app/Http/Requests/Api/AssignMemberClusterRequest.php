<?php

namespace App\Http\Requests\Api;

use App\Models\Cluster;
use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignMemberClusterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'cluster_id' => ['required', 'integer', 'exists:clusters,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Enforce that destination cluster belongs to the same cooperative as member.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $routeMember = $this->route('member');
                $member = $routeMember instanceof Member
                    ? $routeMember
                    : Member::query()->find((int) $routeMember);

                if ($member === null) {
                    return;
                }

                $cluster = Cluster::query()->find($this->integer('cluster_id'));

                if ($cluster === null) {
                    return;
                }

                if ((int) $cluster->cooperative_id !== (int) $member->cooperative_id) {
                    $validator->errors()->add(
                        'cluster_id',
                        'The selected cluster does not belong to the member cooperative.'
                    );
                }
            },
        ];
    }
}
