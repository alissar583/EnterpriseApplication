<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileHelper
{
    public static function upload($file, string $path): string
    {
        $fileName = time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($path, $fileName, 'public');
        return 'storage/' . $path;
    }

    public static function delete(string $path): void
    {
        Storage::disk('public')->delete($path);
    }

    public static function replace(UploadedFile $file, string $oldPath, $newPath): string
    {
        self::delete($oldPath);
        return self::upload($file, $newPath);
    }

}
