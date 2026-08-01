<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\Ftp\FtpBrowser;
use App\Services\Import\ConfigurationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Tests\TestCase;

class FtpBrowserTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/dz-ftp-test-'.uniqid();
        mkdir($this->root.'/db', 0777, true);
        mkdir($this->root.'/env', 0777, true);
        mkdir($this->root.'/custom', 0777, true);
        mkdir($this->root.'/logs', 0777, true);
        file_put_contents($this->root.'/types.xml', '<types></types>');
        file_put_contents($this->root.'/db/globals.xml', '<globals></globals>');
        file_put_contents($this->root.'/env/wolf_territories.xml', '<territory-type></territory-type>');
        file_put_contents($this->root.'/custom/startovni-vybava.json', '{"items": []}');
        file_put_contents($this->root.'/logs/DayZServer_PS4_x64_2026-08-01_08-37-57.RPT', 'log a');
        touch($this->root.'/logs/DayZServer_PS4_x64_2026-08-01_08-37-57.RPT', strtotime('2026-08-01 08:37:57'));
        file_put_contents($this->root.'/logs/DayZServer_PS4_x64_2026-07-31_10-00-00.RPT', 'log b');
        touch($this->root.'/logs/DayZServer_PS4_x64_2026-07-31_10-00-00.RPT', strtotime('2026-07-31 10:00:00'));
        file_put_contents($this->root.'/logs/DayZServer_x64_2026-08-01.ADM', 'admin log');
        touch($this->root.'/logs/DayZServer_x64_2026-08-01.ADM', strtotime('2026-08-01 09:00:00'));
        file_put_contents($this->root.'/logs/not-a-log.txt', 'irrelevant');
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->root);
        parent::tearDown();
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            is_dir($path) ? $this->deleteTree($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function localFilesystem(): Filesystem
    {
        return new Filesystem(new LocalFilesystemAdapter($this->root));
    }

    public function test_listing_flags_known_files_and_folders(): void
    {
        $browser = app(FtpBrowser::class);
        $entries = collect($browser->listDirectoryOn($this->localFilesystem(), ''))->keyBy('name');

        $this->assertTrue($entries['types.xml']['known']);
        $this->assertSame('economy', $entries['types.xml']['category']);
        $this->assertTrue($entries['db']['known']);
        $this->assertTrue($entries['env']['known']);
        $this->assertTrue($entries['custom']['known'], 'The "custom" folder is a normal, known DayZ mission folder.');
    }

    public function test_gear_preset_in_custom_folder_is_recognised_regardless_of_filename(): void
    {
        $browser = app(FtpBrowser::class);
        $entries = collect($browser->listDirectoryOn($this->localFilesystem(), 'custom'))->keyBy('name');

        $this->assertArrayHasKey('startovni-vybava.json', $entries->all());
        $this->assertTrue($entries['startovni-vybava.json']['known']);
        $this->assertSame('gear', $entries['startovni-vybava.json']['category']);
    }

    public function test_json_outside_custom_folder_still_needs_the_spawn_gear_pattern(): void
    {
        $browser = app(FtpBrowser::class);

        $this->assertFalse($browser->classifyFile('startovni-vybava.json', '')['known']);
        $this->assertTrue($browser->classifyFile('my-spawn-gear-preset.json', '')['known']);
    }

    public function test_list_files_recursive_finds_files_in_every_subfolder(): void
    {
        $browser = app(FtpBrowser::class);
        $entries = collect($browser->listFilesRecursiveOn($this->localFilesystem(), ''))->keyBy('path');

        $this->assertArrayHasKey('types.xml', $entries->all());
        $this->assertArrayHasKey('db/globals.xml', $entries->all());
        $this->assertArrayHasKey('env/wolf_territories.xml', $entries->all());
        $this->assertArrayHasKey('custom/startovni-vybava.json', $entries->all());
        $this->assertSame('gear', $entries['custom/startovni-vybava.json']['category']);
        $this->assertTrue($entries->every(fn (array $entry): bool => $entry['type'] === 'file'), 'Only files, never directories, should be in the recursive listing.');
    }

    public function test_read_returns_remote_file_content(): void
    {
        $browser = app(FtpBrowser::class);
        $content = $browser->readOn($this->localFilesystem(), 'db/globals.xml');

        $this->assertSame('<globals></globals>', $content);
    }

    public function test_import_file_on_preserves_the_subfolder_in_original_filename(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'FTP import test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        $browser = app(FtpBrowser::class);

        $import = $browser->importFileOn($this->localFilesystem(), $project, 'db/globals.xml', $user, app(ConfigurationImporter::class));

        $this->assertSame('db/globals.xml', $import->original_filename);
        $this->assertSame(1, $project->revisions()->count());
        $this->assertStringContainsString('<globals></globals>', Storage::disk('dayz')->get($import->storage_path));
    }

    public function test_import_file_on_preserves_the_custom_folder(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'FTP custom import test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        $browser = app(FtpBrowser::class);

        $import = $browser->importFileOn($this->localFilesystem(), $project, 'custom/startovni-vybava.json', $user, app(ConfigurationImporter::class));

        $this->assertSame('custom/startovni-vybava.json', $import->original_filename);
    }

    public function test_filesystem_throws_a_friendly_error_without_connection_details(): void
    {
        $project = Project::query()->create([
            'user_id' => User::factory()->create()->id, 'name' => 'No FTP test', 'platform' => 'unknown', 'map' => 'chernarusplus',
        ]);

        $this->expectException(\RuntimeException::class);
        app(FtpBrowser::class)->filesystem($project);
    }

    public function test_list_log_files_on_filters_to_log_extensions_and_sorts_newest_first(): void
    {
        $browser = app(FtpBrowser::class);
        $files = $browser->listLogFilesOn($this->localFilesystem(), 'logs');

        $this->assertSame([
            'DayZServer_PS4_x64_2026-08-01_08-37-57.RPT',
            'DayZServer_PS4_x64_2026-07-31_10-00-00.RPT',
            'DayZServer_x64_2026-08-01.ADM',
        ], array_column($files, 'name'));
    }

    public function test_read_log_file_returns_its_content(): void
    {
        $browser = app(FtpBrowser::class);
        $content = $browser->readOn($this->localFilesystem(), 'logs/DayZServer_PS4_x64_2026-08-01_08-37-57.RPT');

        $this->assertSame('log a', $content);
    }

    public function test_filesystem_for_logs_throws_without_a_configured_log_path(): void
    {
        $project = Project::query()->create([
            'user_id' => User::factory()->create()->id, 'name' => 'No log path test', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
        ]);

        $this->expectException(\RuntimeException::class);
        app(FtpBrowser::class)->filesystemForLogs($project);
    }

    public function test_project_has_ftp_log_connection_only_with_both_ftp_and_log_path(): void
    {
        $withoutLogPath = new Project(['ftp_host' => 'h', 'ftp_username' => 'u', 'ftp_password' => 'p']);
        $this->assertFalse($withoutLogPath->hasFtpLogConnection());

        $withLogPath = new Project(['ftp_host' => 'h', 'ftp_username' => 'u', 'ftp_password' => 'p', 'ftp_log_path' => '0:/dayzps/config/']);
        $this->assertTrue($withLogPath->hasFtpLogConnection());
    }
}
