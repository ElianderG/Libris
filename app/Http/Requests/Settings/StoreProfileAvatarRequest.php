<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProfileAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Escolha uma imagem.',
            'photo.image' => 'Envie uma imagem JPG, PNG ou WebP.',
            'photo.mimes' => 'Envie uma imagem JPG, PNG ou WebP.',
            'photo.max' => 'A imagem pode ter no máximo 5 MB.',
        ];
    }
}
