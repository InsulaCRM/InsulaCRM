<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use ZipArchive;
use Tests\TestCase;

class PluginSecurityTest extends TestCase
{
    public function test_plugin_upload_rejects_path_traversal_archive(): void
    {
        $this->actingAsAdmin();

        $zipPath = storage_path('app/test-malicious-plugin.zip');
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('../escape.txt', 'owned');
        $zip->addFromString('plugin/plugin.json', json_encode([
            'name' => 'Bad Plugin',
            'slug' => 'bad-plugin',
            'version' => '1.0.0',
        ]));
        $zip->close();

        $uploadedFile = new UploadedFile(
            $zipPath,
            'bad-plugin.zip',
            'application/zip',
            null,
            true
        );

        $response = $this->post(route('plugins.upload'), [
            'plugin' => $uploadedFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Invalid plugin archive structure.');
        $this->assertFileDoesNotExist(base_path('plugins/bad-plugin/plugin.json'));
        $this->assertFileDoesNotExist(storage_path('app/escape.txt'));

        @unlink($zipPath);
    }
}
