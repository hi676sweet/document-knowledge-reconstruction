<?php

namespace Tests\Feature;

use App\Services\EvidenceSearch;
use App\Services\WorkspaceSession;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DiskBootstrapTest extends TestCase
{
    public function test_a_disk_backed_workspace_bootstraps_and_searches_all_seed_sources(): void
    {
        $path = storage_path('framework/testing/fts-'.Str::uuid().'.sqlite');
        touch($path);
        $previous = DB::getDefaultConnection();
        config(['database.connections.disk_probe' => array_replace(config('database.connections.sqlite'), ['database' => $path])]);
        DB::setDefaultConnection('disk_probe');
        try {
            Artisan::call('migrate', ['--database' => 'disk_probe', '--force' => true]);
            $this->assertSame(2, (int) DB::selectOne('PRAGMA temp_store')->temp_store);
            session()->put('workspace_owner', (string) Str::uuid());
            $workspace = app(WorkspaceSession::class)->current();
            $this->assertSame(6, $workspace->documents()->count());
            $results = app(EvidenceSearch::class)->find($workspace, 'support response');
            $this->assertNotEmpty($results);
            $this->assertTrue($results->contains(fn ($result): bool => str_contains($result->body, 'one business day')));
        } finally {
            DB::purge('disk_probe');
            DB::setDefaultConnection($previous);
            unlink($path);
        }
    }
}
