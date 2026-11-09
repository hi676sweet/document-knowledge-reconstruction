# Sourcebook: workbook roadmap and source map

**Synthetic planning reference. No historical Git data or completed-work record is asserted.**

This document imports Sourcebook's planning allocation from `portfolio_daily_activity_simulation_2023_2026.xlsx`. The workbook labels its dates and slots as a simulation. This repository is a new November 2026 reconstruction; its Git author and committer dates retain their actual values.

The supplied resume reports LET'S DO YOUR PLAN employment from August 2023 through March 2026. The workbook's narrower project allocations are hypothetical planning choices. They do not establish an individual assignment or an actual development period.

## Workbook allocation

| Field | Simulation value |
| --- | --- |
| Allocation window | 2025-08-01 through 2026-03-31 |
| Planned weekdays | 173 |
| Slots per planned weekday | 6 |
| Synthetic planned slots | 1038 |
| Time zone | JST, UTC+09:00 |
| Workbook status | Synthetic plan |

Slot counts describe the workbook's model. They are not counts of commits created, contributions made, or completed engineering tasks.

## Monthly roadmap

The following months are simulation labels copied from the workbook. The topic list is indicative; it does not mark those topics as historically completed.

| Simulation month | Planned weekdays | Indicative workbook topics |
| --- | ---: | --- |
| 2025-08 | 21 | knowledge-library use cases; version retention; SQLite schema; workspace boundaries |
| 2025-09 | 22 | full-text index; workspace sessions; passage boundaries; source reference IDs |
| 2025-10 | 23 | TXT/Markdown uploads; document deletion; question view; version updates |
| 2025-11 | 20 | TXT/Markdown uploads; document deletion; question view; version updates |
| 2025-12 | 23 | query normalization; search aliases; empty result states; stemming rules |
| 2026-01 | 22 | stemming rules; citation links; stale version rejection; bounded resource quotas |
| 2026-02 | 20 | stale version rejection; query parameter safety; deletion cascade tests; Unicode chunking |
| 2026-03 | 22 | cross-workspace denial; real SQLite FTS bootstrap; deletion cascade tests; README limitations |

## Phase allocation

| Planned phase | Simulated weekdays |
| --- | ---: |
| Architecture & scope | 17 |
| Core data model | 26 |
| Primary workflows | 42 |
| Search & usability | 26 |
| Integrity & security | 24 |
| Regression testing | 21 |
| Polish & documentation | 17 |

## Feature-to-source map

Use these links to inspect how the current demo addresses the workbook's themes. Test links identify existing regression sources; this document does not certify every generic workbook objective or substitute for running the checks.

| Workbook topics | Current implementation | Existing regression sources |
| --- | --- | --- |
| Paste/TXT/Markdown import and safe file validation | [`app/Services/DocumentLibrary.php`](../app/Services/DocumentLibrary.php); [`app/Http/Controllers/DocumentController.php`](../app/Http/Controllers/DocumentController.php) | [`tests/Feature/UploadTest.php`](../tests/Feature/UploadTest.php) |
| Version retention, stale edits, deletion and atomic reindexing | [`app/Services/DocumentLibrary.php`](../app/Services/DocumentLibrary.php); [`app/Models/DocumentVersion.php`](../app/Models/DocumentVersion.php) | [`tests/Feature/DocumentLifecycleTest.php`](../tests/Feature/DocumentLifecycleTest.php) |
| FTS5 retrieval, query normalization and cited excerpts | [`app/Services/EvidenceSearch.php`](../app/Services/EvidenceSearch.php); [`app/Models/Passage.php`](../app/Models/Passage.php); [`database/migrations`](../database/migrations) | [`tests/Feature/LibraryTest.php`](../tests/Feature/LibraryTest.php); [`tests/Feature/DiskBootstrapTest.php`](../tests/Feature/DiskBootstrapTest.php) |
| Workspace boundaries, resource quotas and source ownership | [`app/Services/WorkspaceSession.php`](../app/Services/WorkspaceSession.php); [`app/Services/DocumentLibrary.php`](../app/Services/DocumentLibrary.php); [`app/Http/Controllers/DocumentController.php`](../app/Http/Controllers/DocumentController.php) | [`tests/Feature/DocumentLifecycleTest.php`](../tests/Feature/DocumentLifecycleTest.php); [`tests/Feature/LibraryTest.php`](../tests/Feature/LibraryTest.php) |
| Reading view, source links and visible search feedback | [`app/Livewire/Library.php`](../app/Livewire/Library.php); [`resources/views`](../resources/views); [`public/sourcebook.css`](../public/sourcebook.css) | [`tests/Feature/LibraryTest.php`](../tests/Feature/LibraryTest.php) |

## Review boundaries

Question mode returns unchanged source excerpts. It does not call a language model or perform semantic reasoning. Session access, document limits and English-oriented keyword retrieval are demo boundaries; no production identity or retention system is claimed.

The workbook includes broad polish, accessibility and review topics. There is no formal accessibility certification or performance result attached to this roadmap. Run the checks documented in the [README](../README.md) and inspect the linked cases before making new coverage claims.

## Source traceability

- Workbook: `portfolio_daily_activity_simulation_2023_2026.xlsx`.
- SHA-256: `2603bf41d899d5216e935309785acdd4b4748bccf722b1b5fd2e5161b621b9de`.
- Imported sheets: Overview, Daily Plan, Commit Schedule, Monthly Roadmap, and Repository Notes.
- Machine-readable summary: [simulation-source.json](simulation-source.json).
- Documentation prepared: November 10, 2026, Asia/Tokyo.

Only planning summaries and source references are imported. No scheduled commit messages are executed, no artificial commit sequence is generated, and the workbook does not control repository history.
