<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Passage;
use App\Models\Workspace;
use App\Services\DocumentLibrary;
use App\Services\EvidenceSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DocumentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $token = (string) Str::uuid();
        $this->withSession(['workspace_owner' => $token]);
        $this->workspace = Workspace::create(['owner_token' => $token, 'name' => 'Test workspace']);
    }

    public function test_document_creation_indexes_text_and_links_to_its_source(): void
    {
        $this->post('/documents', ['title' => 'Project handover', 'content' => 'The zephyr project handover has one owner and one review date.'])->assertRedirect();
        $document = Document::firstOrFail();
        $this->assertCount(1, app(EvidenceSearch::class)->find($this->workspace, 'zephyr'));
        $this->get('/documents/'.$document->id)->assertOk()->assertSee('Project handover')->assertSee('Version 1');
    }

    public function test_update_replaces_search_index_but_preserves_past_source_and_title(): void
    {
        $library = app(DocumentLibrary::class);
        $document = $library->save($this->workspace, 'Original title', 'The orchard deployment uses an older review approach.');
        $this->patch('/documents/'.$document->id, ['title' => 'New title', 'content' => 'The mountain deployment uses the current review approach.', 'version' => 1])->assertRedirect();
        $document->refresh();
        $this->assertSame(2, $document->version);
        $this->assertCount(0, app(EvidenceSearch::class)->find($this->workspace, 'orchard'));
        $this->assertCount(1, app(EvidenceSearch::class)->find($this->workspace, 'mountain'));
        $this->get('/documents/'.$document->id.'?version=1')->assertOk()->assertSee('Original title')->assertSee('orchard')->assertSee('preserved past version');
        $this->get('/documents/'.$document->id.'?version=3')->assertNotFound();
        $this->assertDatabaseCount('document_versions', 2);
    }

    public function test_stale_tab_cannot_overwrite_a_newer_version(): void
    {
        $document = app(DocumentLibrary::class)->save($this->workspace, 'Source title', 'An original document with enough text for the library.');
        app(DocumentLibrary::class)->save($this->workspace, 'Current title', 'The current document has a new and updated workflow.', $document, 1);
        $this->patch('/documents/'.$document->id, ['title' => 'Stale title', 'content' => 'A stale document must never overwrite the current workflow.', 'version' => 1])->assertSessionHasErrors('version');
        $this->assertSame('Current title', $document->fresh()->title);
        $this->assertDatabaseCount('document_versions', 2);
    }

    public function test_deletion_removes_versions_and_fts_entries_through_database_triggers(): void
    {
        $document = app(DocumentLibrary::class)->save($this->workspace, 'Delete source', 'A unique obelisk document can be deleted from this workspace.');
        $this->delete('/documents/'.$document->id)->assertRedirect('/');
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_versions', 0);
        $this->assertDatabaseCount('passages', 0);
        $this->assertCount(0, app(EvidenceSearch::class)->find($this->workspace, 'obelisk'));
    }

    public function test_failed_reindex_rolls_back_version_document_and_fts_together(): void
    {
        $library = app(DocumentLibrary::class);
        $document = $library->save($this->workspace, 'Original title', 'The old juniper passage remains indexed until a successful change.');
        Passage::creating(function (Passage $passage): void {
            if (str_contains($passage->body, 'sabotage')) {
                throw new RuntimeException('Simulated indexing failure');
            }
        });
        try {
            $library->save($this->workspace, 'New title', 'The sabotage passage causes a simulated indexing failure.', $document, 1);
            $this->fail('Expected indexing failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated indexing failure', $exception->getMessage());
        } finally {
            Passage::flushEventListeners();
        }
        $this->assertSame(1, $document->fresh()->version);
        $this->assertSame('Original title', $document->fresh()->title);
        $this->assertDatabaseCount('document_versions', 1);
        $this->assertCount(1, app(EvidenceSearch::class)->find($this->workspace, 'juniper'));
        $this->assertCount(0, app(EvidenceSearch::class)->find($this->workspace, 'sabotage'));
    }

    public function test_other_sessions_cannot_read_edit_update_or_delete_documents(): void
    {
        $foreign = Workspace::create(['owner_token' => (string) Str::uuid(), 'name' => 'Foreign workspace']);
        $document = app(DocumentLibrary::class)->save($foreign, 'Private source', 'This private document belongs to a different browser session.');
        $this->get('/documents/'.$document->id)->assertNotFound();
        $this->get('/documents/'.$document->id.'/edit')->assertNotFound();
        $this->patch('/documents/'.$document->id, ['title' => 'Stolen', 'content' => 'A different session must not be able to modify this document.', 'version' => 1])->assertNotFound();
        $this->delete('/documents/'.$document->id)->assertNotFound();
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'title' => 'Private source', 'version' => 1]);
    }
}
