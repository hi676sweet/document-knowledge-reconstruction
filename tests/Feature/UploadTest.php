<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Workspace;
use App\Services\EvidenceSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class UploadTest extends TestCase
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

    public function test_utf8_markdown_upload_is_indexed_as_text_without_executing_html(): void
    {
        $content = "# Useful notes\n\n<script>alert('unsafe')</script> A fictional notebook contains kiwi guidance.";
        $this->post('/documents', ['title' => 'Uploaded markdown', 'file' => UploadedFile::fake()->createWithContent('notes.md', $content)])->assertRedirect();
        $document = Document::firstOrFail();
        $this->assertCount(1, app(EvidenceSearch::class)->find($this->workspace, 'kiwi'));
        $this->get('/documents/'.$document->id)->assertOk()->assertDontSee("<script>alert('unsafe')</script>", false)->assertSee('&lt;script&gt;', false);
    }

    public function test_wrong_extensions_binary_and_oversized_files_are_rejected(): void
    {
        $files = [
            UploadedFile::fake()->createWithContent('notes.php', '<?php echo "not executed";'),
            UploadedFile::fake()->createWithContent('binary.txt', "binary\0payload".str_repeat('z', 40)),
            UploadedFile::fake()->createWithContent('too-big.txt', str_repeat('a', 65537)),
        ];
        foreach ($files as $file) {
            $this->post('/documents', ['title' => 'Invalid source', 'file' => $file])->assertSessionHasErrors('file');
        }
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('passages', 0);
    }

    public function test_short_or_invalid_text_does_not_create_partial_documents(): void
    {
        $this->post('/documents', ['title' => 'Too short', 'content' => 'tiny'])->assertSessionHasErrors('content');
        $this->post('/documents', ['title' => 'Empty source', 'content' => ''])->assertSessionHasErrors('content');
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_versions', 0);
    }
}
