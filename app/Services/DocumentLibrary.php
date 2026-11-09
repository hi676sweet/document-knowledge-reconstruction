<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Passage;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentLibrary
{
    public const MAX_DOCUMENTS = 25;

    public const MAX_BYTES = 65536;

    /** @return array<int,string> */
    public function chunks(string $content): array
    {
        $paragraphs = preg_split('/\n\s*\n/u', str_replace(["\r\n", "\r"], "\n", $content));
        $chunks = [];
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            for ($offset = 0; $offset < mb_strlen($paragraph); $offset += 800) {
                $chunks[] = mb_substr($paragraph, $offset, 800);
            }
        }

        return array_values(array_filter($chunks, fn (string $chunk): bool => $chunk !== ''));
    }

    public function save(Workspace $workspace, string $title, string $content, ?Document $document = null, ?int $expectedVersion = null): Document
    {
        $title = trim($title);
        $content = trim(str_replace(["\r\n", "\r"], "\n", $content));
        if ($title === '' || mb_strlen($title) > 120 || mb_strlen($content) < 20 || strlen($content) > self::MAX_BYTES || ! mb_check_encoding($content, 'UTF-8') || str_contains($content, "\0")) {
            throw ValidationException::withMessages(['content' => 'Use a title and 20 to 65,536 bytes of valid UTF-8 text.']);
        }

        return DB::transaction(function () use ($workspace, $title, $content, $document, $expectedVersion): Document {
            if ($document) {
                abort_unless($document->workspace_id === $workspace->id, 404);
                $current = Document::where('workspace_id', $workspace->id)->findOrFail($document->id);
                if ($expectedVersion !== $current->version) {
                    throw ValidationException::withMessages(['version' => 'This document changed in another tab. Reload it before saving.']);
                }
                $changed = Document::whereKey($current->id)->where('version', $expectedVersion)->update(['title' => $title, 'version' => $expectedVersion + 1, 'updated_at' => now()]);
                if ($changed !== 1) {
                    throw ValidationException::withMessages(['version' => 'This document changed. Reload it before saving.']);
                }
                $current->refresh();
                $current->passages()->delete();
            } else {
                if ($workspace->documents()->count() >= self::MAX_DOCUMENTS) {
                    throw ValidationException::withMessages(['content' => 'This demo workspace holds up to 25 documents. Delete one before adding another.']);
                }
                $current = $workspace->documents()->create(['title' => $title, 'version' => 1]);
            }
            $version = DocumentVersion::create([
                'document_id' => $current->id, 'number' => $current->version, 'title' => $title,
                'content' => $content, 'checksum' => hash('sha256', $content), 'created_at' => now(),
            ]);
            foreach ($this->chunks($content) as $position => $body) {
                Passage::create(['workspace_id' => $workspace->id, 'document_id' => $current->id, 'document_version_id' => $version->id, 'position' => $position + 1, 'title' => $title, 'body' => $body]);
            }

            return $current;
        }, 3);
    }

    public function delete(Workspace $workspace, Document $document): void
    {
        abort_unless($document->workspace_id === $workspace->id, 404);
        DB::transaction(fn () => $document->delete());
    }
}
