<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentLibrary;
use App\Services\WorkspaceSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function create(): View
    {
        return view('documents.form', ['document' => null, 'version' => null]);
    }

    public function store(Request $request, WorkspaceSession $session, DocumentLibrary $library): RedirectResponse
    {
        [$title, $content] = $this->content($request);
        $document = $library->save($session->current(), $title, $content);

        return to_route('documents.show', $document)->with('success', 'Document added. Its source passages are ready to search.');
    }

    public function show(Request $request, Document $document, WorkspaceSession $session, DocumentLibrary $library): View
    {
        abort_unless($document->workspace_id === $session->current()->id, 404);
        $number = $request->integer('version', $document->version);
        $version = $document->versions()->where('number', $number)->firstOrFail();

        return view('documents.show', ['document' => $document, 'version' => $version, 'chunks' => $library->chunks($version->content), 'history' => $document->versions()->get()]);
    }

    public function edit(Document $document, WorkspaceSession $session): View
    {
        abort_unless($document->workspace_id === $session->current()->id, 404);

        return view('documents.form', ['document' => $document, 'version' => $document->versions()->firstOrFail()]);
    }

    public function update(Request $request, Document $document, WorkspaceSession $session, DocumentLibrary $library): RedirectResponse
    {
        $workspace = $session->current();
        abort_unless($document->workspace_id === $workspace->id, 404);
        $request->validate(['version' => ['required', 'integer', 'min:1']]);
        [$title, $content] = $this->content($request);
        $library->save($workspace, $title, $content, $document, $request->integer('version'));

        return to_route('documents.show', $document)->with('success', 'New version saved. Search now uses the latest source.');
    }

    public function destroy(Document $document, WorkspaceSession $session, DocumentLibrary $library): RedirectResponse
    {
        $library->delete($session->current(), $document);

        return to_route('library')->with('success', 'Document and all its versions were deleted. Its passages are no longer searchable.');
    }

    /** @return array{string,string} */
    private function content(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'content' => ['nullable', 'string', 'max:65536'],
            'file' => ['nullable', 'file', 'max:64', 'extensions:txt,md', 'mimetypes:text/plain,text/markdown,text/x-markdown,application/octet-stream'],
        ]);
        if ($request->hasFile('file')) {
            $upload = $request->file('file');
            $content = file_get_contents($upload->getRealPath());
            if (! mb_check_encoding($content, 'UTF-8') || str_contains($content, "\0") || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $content)) {
                throw ValidationException::withMessages(['file' => 'Upload a UTF-8 .txt or .md text file, up to 64 KB. Binary files are not supported.']);
            }
        } else {
            $content = $data['content'] ?? '';
        }

        return [$data['title'], $content];
    }
}
