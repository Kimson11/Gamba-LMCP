<?php

namespace App\Http\Requests\Api;

use App\Models\Cluster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateMemberRequest extends FormRequest
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
            'cooperative_id' => ['required', 'integer', 'exists:cooperatives,id'],
            'cluster_id' => ['nullable', 'integer', 'exists:clusters,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'member_number' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * Enforce cooperative-cluster consistency after base validation passes.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $clusterId = $this->integer('cluster_id');

                if ($clusterId === 0) {
                    return;
                }

                $cluster = Cluster::query()->find($clusterId);

                if ($cluster === null) {
                    return;
                }

                if ((int) $cluster->cooperative_id !== $this->integer('cooperative_id')) {
                    $validator->errors()->add(
                        'cluster_id',
                        'The selected cluster does not belong to the selected cooperative.'
                    );
                }
            },
        ];
    }
}
