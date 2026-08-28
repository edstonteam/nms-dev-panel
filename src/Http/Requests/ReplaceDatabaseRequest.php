<?php

namespace Egarrido\NmsDevPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ReplaceDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('nms-dev-panel.enabled')
            && app()->environment(config('nms-dev-panel.environments', ['local']));
    }

    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'in:REPLACE'],
            'dump' => [
                'required',
                'file',
                'max:'.config('nms-dev-panel.database_dump.max_kilobytes'),
                function ($attribute, $value, $fail): void {
                    if (!$value instanceof UploadedFile || !preg_match('/\.sql(?:\.gz)?$/i', $value->getClientOriginalName())) {
                        $fail('Only .sql and .sql.gz database dumps are supported.');
                    }
                },
            ],
        ];
    }
}
