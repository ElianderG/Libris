<?php

namespace App\Support;

use Closure;
use Illuminate\Validation\ValidationException;

class ProductRules
{
    public const PRICE_MESSAGE = 'O preço precisa ser um inteiro maior que zero, em centavos.';

    public const COVER_MESSAGE = 'A capa precisa ser um arquivo dentro de covers.';

    private const MAX_CENTS = 4294967295;

    /**
     * Regras do cadastro de livro. O admin reutiliza este mesmo array.
     *
     * @return array<string, array<int, Closure|string>>
     */
    public static function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'content' => ['required', 'string'],
            'cover_path' => ['nullable', self::coverPathRule()],
            'price_cents' => ['required', self::priceRule()],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
        ];
    }

    public static function priceRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! self::isPositiveCents($value)) {
                $fail(self::PRICE_MESSAGE);
            }
        };
    }

    public static function coverPathRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! self::acceptsCoverPath($value)) {
                $fail(self::COVER_MESSAGE);
            }
        };
    }

    public static function isPositiveCents(mixed $value): bool
    {
        if (is_int($value)) {
            return $value >= 1 && $value <= self::MAX_CENTS;
        }

        if (! is_string($value) || preg_match('/^[1-9]\d{0,9}$/', $value) !== 1) {
            return false;
        }

        $cents = (int) $value;

        return $cents >= 1 && $cents <= self::MAX_CENTS;
    }

    public static function reaisToCents(string $value): ?int
    {
        $value = trim($value);

        if (preg_match('/^\d{1,7}([.,]\d{1,2})?$/', $value) !== 1) {
            return null;
        }

        $parts = explode('.', str_replace(',', '.', $value), 2);
        $cents = ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');

        if ($cents < 1 || $cents > self::MAX_CENTS) {
            return null;
        }

        return $cents;
    }

    public static function centsToReais(int $cents): string
    {
        $cents = abs($cents);

        return intdiv($cents, 100).','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function parseCents(mixed $value): int
    {
        if (! self::isPositiveCents($value)) {
            throw ValidationException::withMessages([
                'price_cents' => self::PRICE_MESSAGE,
            ]);
        }

        return (int) $value;
    }

    public static function acceptsCoverPath(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return is_string($value) && self::safeCoverPath($value) !== null;
    }

    public static function normalizeCoverPath(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $path = is_string($value) ? self::safeCoverPath($value) : null;

        if ($path === null) {
            throw ValidationException::withMessages([
                'cover_path' => self::COVER_MESSAGE,
            ]);
        }

        return $path;
    }

    public static function safeCoverPath(string $value): ?string
    {
        $path = str_replace('\\', '/', trim($value));

        if (preg_match('/^covers\/[A-Za-z0-9][A-Za-z0-9._-]*$/', $path) !== 1) {
            return null;
        }

        return $path;
    }
}
