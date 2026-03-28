<?php

namespace App\Services\Settings;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;

class LanguageFileService
{
    public function list(): array
    {
        $files = File::glob(base_path('lang/*.json'));
        $languages = [];

        foreach ($files as $file) {
            $code = pathinfo($file, PATHINFO_FILENAME);
            $content = json_decode(File::get($file), true);
            $languages[] = [
                'code' => $code,
                'file' => basename($file),
                'count' => is_array($content) ? count($content) : 0,
            ];
        }

        usort($languages, function (array $a, array $b) {
            if ($a['code'] === 'en') {
                return -1;
            }

            if ($b['code'] === 'en') {
                return 1;
            }

            return strcmp($a['code'], $b['code']);
        });

        return $languages;
    }

    public function get(string $code): array
    {
        $this->assertValidCode($code);

        $langPath = base_path('lang');
        $enFile = $langPath . '/en.json';
        $targetFile = $langPath . '/' . $code . '.json';

        if (! File::exists($enFile)) {
            throw new RuntimeException('English base file not found.');
        }

        $enStrings = json_decode(File::get($enFile), true) ?: [];
        $targetStrings = $code !== 'en' && File::exists($targetFile)
            ? (json_decode(File::get($targetFile), true) ?: [])
            : [];

        $translations = [];
        foreach ($enStrings as $key => $value) {
            $translations[] = [
                'key' => $key,
                'en' => $value,
                'translation' => $targetStrings[$key] ?? '',
            ];
        }

        $translated = count(array_filter($targetStrings, fn ($value) => $value !== '' && $value !== null));

        return [
            'code' => $code,
            'translations' => $translations,
            'total' => count($enStrings),
            'translated' => $translated,
        ];
    }

    public function save(string $code, array $translations): void
    {
        $this->assertValidCode($code);

        if ($code === 'en') {
            throw new InvalidArgumentException('Cannot edit the base English file.');
        }

        $data = [];
        foreach ($translations as $key => $value) {
            if ($value !== null && $value !== '') {
                $data[$key] = $value;
            }
        }

        File::put(base_path("lang/{$code}.json"), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function upload(UploadedFile $file): void
    {
        $filename = $file->getClientOriginalName();

        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
            throw new InvalidArgumentException('File must have a .json extension.');
        }

        $this->assertValidCode(pathinfo($filename, PATHINFO_FILENAME));

        $content = file_get_contents($file->getRealPath());
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('File contains invalid JSON: ' . json_last_error_msg());
        }

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('JSON file must contain an object of key-value pairs.');
        }

        File::put(base_path("lang/{$filename}"), json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function assertValidCode(string $code): void
    {
        if (! preg_match('/^[a-zA-Z_]{2,10}$/', $code)) {
            throw new InvalidArgumentException('Invalid language code.');
        }
    }
}
