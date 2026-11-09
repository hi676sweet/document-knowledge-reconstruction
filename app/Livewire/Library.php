<?php

namespace App\Livewire;

use App\Models\Passage;
use App\Services\EvidenceSearch;
use App\Services\WorkspaceSession;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Library extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: 'library')]
    public string $mode = 'library';

    public function updatedQ(): void
    {
        $this->q = mb_substr($this->q, 0, 200);
        $this->resetPage();
    }

    public function ask(string $question): void
    {
        $this->q = mb_substr($question, 0, 200);
        $this->mode = 'ask';
    }

    #[Computed]
    public function documents()
    {
        return app(WorkspaceSession::class)->current()->documents()->withCount('passages')->latest('updated_at')->paginate(9);
    }

    #[Computed]
    public function results()
    {
        return app(EvidenceSearch::class)->find(app(WorkspaceSession::class)->current(), $this->q, $this->mode === 'ask' ? 4 : 12);
    }

    #[Computed]
    public function stats(): array
    {
        $workspace = app(WorkspaceSession::class)->current();

        return ['documents' => $workspace->documents()->count(), 'passages' => Passage::where('workspace_id', $workspace->id)->count()];
    }

    public function render(): View
    {
        return view('livewire.library')->layout('components.layouts.app');
    }
}
