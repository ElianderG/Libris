<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserAvatar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class AvatarImage
{
    public static function store(UploadedFile $file, User $user): UserAvatar
    {
        $jpeg = self::squareJpeg($file);
        $path = 'avatars/'.$user->id.'/'.Str::uuid()->toString().'.jpg';

        Storage::disk('avatars')->put($path, $jpeg);

        return $user->avatars()->create([
            'path' => $path,
        ]);
    }

    public static function squareJpeg(UploadedFile $file): string
    {
        if (function_exists('imagecreatefromstring')) {
            return self::squareJpegWithGd($file);
        }

        return self::squareJpegWithFfmpeg($file);
    }

    private static function squareJpegWithGd(UploadedFile $file): string
    {
        $image = \imagecreatefromstring($file->getContent());

        if ($image === false) {
            throw new RuntimeException('A imagem não pôde ser processada.');
        }

        $width = \imagesx($image);
        $height = \imagesy($image);
        $side = min($width, $height);
        $target = min($side, 512);
        $srcX = intdiv($width - $side, 2);
        $srcY = intdiv($height - $side, 2);
        $square = \imagecreatetruecolor($target, $target);

        if ($square === false) {
            throw new RuntimeException('A imagem não pôde ser processada.');
        }

        \imagecopyresampled($square, $image, 0, 0, $srcX, $srcY, $target, $target, $side, $side);

        $temporary = tempnam(sys_get_temp_dir(), 'avatar');

        if ($temporary === false || ! \imagejpeg($square, $temporary, 85)) {
            if (is_string($temporary)) {
                @unlink($temporary);
            }

            throw new RuntimeException('A imagem não pôde ser processada.');
        }

        $jpeg = file_get_contents($temporary);
        unlink($temporary);

        if ($jpeg === false || $jpeg === '') {
            throw new RuntimeException('A imagem não pôde ser processada.');
        }

        return $jpeg;
    }

    private static function squareJpegWithFfmpeg(UploadedFile $file): string
    {
        $input = sys_get_temp_dir().'/avatar-'.Str::uuid()->toString();
        $output = sys_get_temp_dir().'/avatar-'.Str::uuid()->toString().'.jpg';

        if (file_put_contents($input, $file->getContent()) === false) {
            throw new RuntimeException('A imagem não pôde ser processada.');
        }

        try {
            $process = new Process([
                'ffmpeg',
                '-y',
                '-hide_banner',
                '-loglevel',
                'error',
                '-i',
                $input,
                '-vf',
                'crop=min(iw\,ih):min(iw\,ih),scale=w=min(512\,iw):h=-1',
                '-frames:v',
                '1',
                '-update',
                '1',
                '-q:v',
                '3',
                $output,
            ]);
            $process->setTimeout(15);
            $process->mustRun();

            $jpeg = is_file($output) ? file_get_contents($output) : false;

            if ($jpeg === false || ! str_starts_with($jpeg, "\xFF\xD8")) {
                throw new RuntimeException('A imagem não pôde ser processada.');
            }

            return $jpeg;
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new RuntimeException('A imagem não pôde ser processada.');
        } finally {
            @unlink($input);
            @unlink($output);
        }
    }
}
