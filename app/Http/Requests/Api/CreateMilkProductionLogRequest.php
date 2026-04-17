<?php

namespace App\Http\Requests\Api;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateMilkProductionLogRequest extends FormRequest
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
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'cluster_id' => ['required', 'integer', 'exists:clusters,id'],
            'quantity_liters' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'production_date' => ['required', 'date'],
            'source' => ['sometimes', 'string', 'max:50'],
        ];
    }

    /**
     * Enforce member-cluster consistency to keep replay writes trustworthy.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $member = Member::query()->find($this->integer('member_id'));

                if ($member === null) {
                    return;
                }

                if ((int) $member->cluster_id !== $this->integer('cluster_id')) {
                    $validator->errors()->add(
                        'cluster_id',
                        'The selected cluster does not match the member assignment.'
                    );
                }
            },
        ];
    }
}
