<?php

namespace App\Http\Requests\Website;

use FilamentCraft\Blueprints\StarterFamily;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateWebsiteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'family' => ['required', Rule::enum(StarterFamily::class)],
        ];
    }
}
