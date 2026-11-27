<?php

namespace App\Http\Requests\Admin;

use App\Support\ProductRules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class BookRequest extends FormRequest
{
    public const PRICE_MESSAGE = 'Informe o preço em reais, maior que zero. Exemplo: 37,90.';

    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'author' => trim((string) $this->input('author')),
            'description' => trim((string) $this->input('description')),
            'price' => trim((string) $this->input('price')),
            'category_id' => $this->input('category_id') === '' ? null : $this->input('category_id'),
            'is_featured' => $this->boolean('is_featured'),
            'is_published' => $this->boolean('is_published'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'content' => ['required', 'string', 'max:200000', $this->filledText()],
            'price' => ['required', 'string', 'max:12', $this->priceRule()],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
            'cover' => ['nullable', File::image()->extensions(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024)],
        ];
    }

    public function priceCents(): int
    {
        $cents = ProductRules::reaisToCents((string) $this->input('price'));

        if ($cents === null) {
            throw ValidationException::withMessages([
                'price' => self::PRICE_MESSAGE,
            ]);
        }

        return $cents;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Escolha uma categoria.',
            'category_id.exists' => 'Escolha uma categoria.',
            'title.required' => 'Informe o título.',
            'author.required' => 'Informe o autor.',
            'description.required' => 'Informe a descrição.',
            'content.required' => 'Escreva o conteúdo do livro.',
            'price.required' => self::PRICE_MESSAGE,
            'cover.image' => 'A capa precisa ser uma imagem JPG, PNG ou WebP.',
            'cover.extensions' => 'A capa precisa ser uma imagem JPG, PNG ou WebP.',
            'cover.max' => 'A capa pode ter no máximo 2 MB.',
        ];
    }

    private function priceRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ProductRules::reaisToCents($value) === null) {
                $fail(self::PRICE_MESSAGE);
            }
        };
    }

    private function filledText(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || trim($value) === '') {
                $fail('Escreva o conteúdo do livro.');
            }
        };
    }
}
