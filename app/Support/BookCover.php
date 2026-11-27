<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookCover
{
    public static function store(UploadedFile $file): string
    {
        $extension = strtolower($file->extension());

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw ValidationException::withMessages([
                'cover' => 'A capa precisa ser uma imagem JPG, PNG ou WebP.',
            ]);
        }

        $name = Str::lower((string) Str::uuid()).'.'.$extension;
        File::ensureDirectoryExists(public_path('covers'));
        $file->move(public_path('covers'), $name);

        return 'covers/'.$name;
    }

    public static function deleteIfUnused(?string $path): void
    {
        $safe = is_string($path) ? ProductRules::safeCoverPath($path) : null;

        if ($safe === null || Product::query()->where('cover_path', $safe)->exists()) {
            return;
        }

        $full = public_path($safe);

        if (is_file($full)) {
            unlink($full);
        }
    }
}
