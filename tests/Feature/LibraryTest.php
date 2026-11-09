<?php

namespace Tests\Feature;

use App\Livewire\Library;
use App\Models\Workspace;
use App\Services\DocumentLibrary;
use App\Services\EvidenceSearch;
use App\Services\WorkspaceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LibraryTest extends TestCase
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

    public function test_a_fresh_session_receives_its_own_six_document_library(): void
    {
        $this->withSession(['workspace_owner' => (string) Str::uuid()])->get('/')->assertOk()->assertSee('A home for what you know.')->assertSee('Customer support playbook');
        $this->assertDatabaseCount('documents', 6);
        $this->assertDatabaseCount('passages', 18);
    }

    public function test_punctuation_and_fts_operators_never_become_user_query_syntax(): void
    {
        app(DocumentLibrary::class)->save($this->workspace, 'Release notes', 'A release requires review before the rollout can begin.');
        foreach (['" OR * NEAR(', "release'); DROP TABLE documents; --", 'title:release AND {body}', '\\ "+ *'] as $query) {
            app(EvidenceSearch::class)->find($this->workspace, $query);
        }
        $this->assertDatabaseCount('documents', 1);
        $this->assertCount(0, app(EvidenceSearch::class)->find($this->workspace, '" * : ( )'));
    }

    public function test_search_and_highlight_are_scoped_and_escape_source_html(): void
    {
        $text = '<script>alert("oops")</script> Fictional support has a one day response target.';
        app(DocumentLibrary::class)->save($this->workspace, 'Support source', $text);
        $foreign = Workspace::create(['owner_token' => (string) Str::uuid(), 'name' => 'Another workspace']);
        app(DocumentLibrary::class)->save($foreign, 'Private source', 'Support mentions a secret foreign unicorn process.');
        $results = app(EvidenceSearch::class)->find($this->workspace, 'support');
        $this->assertCount(1, $results);
        $this->assertSame($text, $results->first()->body);
        $html = (string) app(EvidenceSearch::class)->highlight($text, 'support');
        $this->assertStringContainsString('<mark>support</mark>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_question_mode_returns_real_versioned_source_excerpts_and_honest_no_result_state(): void
    {
        $document = app(DocumentLibrary::class)->save($this->workspace, 'Support policy', 'Standard support requests receive a reply within one business day.');
        Livewire::test(Library::class)->call('ask', 'What is the support response time?')->assertSet('mode', 'ask')->assertSee('unchanged excerpts')->assertSee('one business day')->assertSee('version=1', false)->assertSee('passage-1', false);
        Livewire::test(Library::class)->set('mode', 'ask')->set('q', 'zebrafishxylophone')->assertSee('No matching source yet.')->assertDontSee('one business day');
        $this->get('/documents/'.$document->id.'?version=1&q=support')->assertOk()->assertSee('id="passage-1"', false)->assertSee('Standard');
    }

    public function test_builtin_question_prioritizes_the_support_source_among_all_six_demo_documents(): void
    {
        foreach (app(WorkspaceSession::class)->examples() as $title => $content) {
            app(DocumentLibrary::class)->save($this->workspace, $title, $content);
        }
        $results = app(EvidenceSearch::class)->find($this->workspace, 'What is the support response time?', 4);
        $this->assertCount(4, $results);
        $this->assertSame('Customer support playbook', $results->first()->title);
        $this->assertStringContainsString('within one business day', $results->first()->body);
        Livewire::test(Library::class)->call('ask', 'What is the support response time?')->assertSee('one business day');
    }

    public function test_chunks_preserve_valid_unicode_and_are_exact_source_substrings(): void
    {
        $content = str_repeat('情報と知識。', 220)."\n\nA separate source paragraph with exact wording.";
        $document = app(DocumentLibrary::class)->save($this->workspace, 'Unicode document', $content);
        $this->assertGreaterThan(2, $document->passages()->count());
        foreach ($document->passages as $passage) {
            $this->assertTrue(mb_check_encoding($passage->body, 'UTF-8'));
            $this->assertLessThanOrEqual(800, mb_strlen($passage->body));
            $this->assertStringContainsString($passage->body, $content);
        }
    }

    public function test_workspace_document_limit_is_enforced_without_partial_rows(): void
    {
        for ($index = 0; $index < DocumentLibrary::MAX_DOCUMENTS; $index++) {
            app(DocumentLibrary::class)->save($this->workspace, 'Document '.$index, 'A fictional readable source with enough useful text.');
        }
        try {
            app(DocumentLibrary::class)->save($this->workspace, 'Too many documents', 'Another readable fictional source with enough useful text.');
            $this->fail('Document cap should reject');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('content', $exception->errors());
        }
        $this->assertDatabaseCount('documents', 25);
        $this->assertDatabaseCount('document_versions', 25);
    }
}
