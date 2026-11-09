<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class EvidenceSearch
{
    /** @return array<int,string> */
    public function terms(string $query): array
    {
        if (! mb_check_encoding($query, 'UTF-8')) {
            return [];
        }
        preg_match_all('/[\p{L}\p{N}]{2,48}/u', mb_substr($query, 0, 200), $matches);
        $stop = ['a', 'an', 'the', 'and', 'or', 'is', 'am', 'are', 'was', 'were', 'be', 'been', 'being', 'how', 'what', 'when', 'where', 'which', 'who', 'why', 'does', 'do', 'did', 'with', 'for', 'from', 'our', 'can', 'could', 'would', 'should', 'you', 'your', 'we', 'us', 'it', 'its', 'this', 'that', 'these', 'those', 'about', 'have', 'has', 'had', 'into', 'to', 'of', 'in', 'on', 'at', 'by', 'as', 'during', 'happens'];

        return array_slice(array_values(array_unique(array_filter(array_map('mb_strtolower', $matches[0]), fn (string $term): bool => ! in_array($term, $stop, true)))), 0, 12);
    }

    public function find(Workspace $workspace, string $query, int $limit = 12): Collection
    {
        $terms = $this->terms($query);
        if ($terms === []) {
            return collect();
        }
        $groups = array_map(function (string $term): string {
            $quoted = array_map(fn (string $word): string => '"'.str_replace('"', '""', $word).'"', $this->aliases($term));

            return '('.implode(' OR ', $quoted).')';
        }, $terms);
        // Prefer complete concept coverage, then the first two concepts, before
        // broad single-term matches. This keeps generic words from displacing
        // useful evidence while retaining an explicit keyword-search fallback.
        $queries = [implode(' AND ', $groups)];
        if (count($groups) > 2) {
            $queries[] = implode(' AND ', array_slice($groups, 0, 2));
        }
        $queries[] = implode(' OR ', $groups);
        $results = collect();
        $limit = min(12, max(1, $limit));
        foreach (array_unique($queries) as $match) {
            $rows = DB::select('SELECT p.*, d.version AS current_version, v.number AS version_number, bm25(passage_search, 3.0, 1.0) AS rank FROM passage_search JOIN passages p ON p.id = passage_search.rowid JOIN documents d ON d.id = p.document_id JOIN document_versions v ON v.id = p.document_version_id WHERE passage_search MATCH ? AND p.workspace_id = ? ORDER BY rank, p.id LIMIT ?', [$match, $workspace->id, 12]);
            $results = $results->concat($rows)->unique('id')->values();
            if ($results->count() >= $limit) {
                break;
            }
        }

        return $results->take($limit)->values();
    }

    /** @return array<int,string> */
    private function aliases(string $term): array
    {
        return match ($term) {
            'response', 'respond' => ['response', 'respond'],
            'prepare', 'preparation' => ['prepare', 'preparation', 'checklist'],
            default => [$term],
        };
    }

    public function highlight(string $text, string $query): HtmlString
    {
        $terms = array_values(array_unique(array_merge(...array_map(fn (string $term): array => $this->aliases($term), $this->terms($query)))));
        if ($terms === []) {
            return new HtmlString(e($text));
        }
        $pattern = '/('.implode('|', array_map(fn (string $term): string => preg_quote($term, '/'), $terms)).')/iu';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $html = '';
        foreach ($parts as $index => $part) {
            $html .= $index % 2 === 1 ? '<mark>'.e($part).'</mark>' : e($part);
        }

        return new HtmlString($html);
    }
}
