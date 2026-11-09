<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkspaceSession
{
    public function current(): Workspace
    {
        if (! session()->has('workspace_owner')) {
            session()->put('workspace_owner', (string) Str::uuid());
        }

        return DB::transaction(function (): Workspace {
            $workspace = Workspace::firstOrCreate(['owner_token' => session('workspace_owner')], ['name' => 'Northstar workspace']);
            if ($workspace->wasRecentlyCreated) {
                foreach ($this->examples() as $title => $content) {
                    app(DocumentLibrary::class)->save($workspace, $title, $content);
                }
            }

            return $workspace;
        });
    }

    /** @return array<string,string> */
    public function examples(): array
    {
        return [
            'The Northstar team handbook' => "Northstar is a fictional product studio. We work in small teams and write decisions down so everyone can understand the context. Our shared working hours are 10:00 to 14:00 UTC.\n\nEvery project starts with a one-page brief. The brief names the owner, the problem, the intended outcome and the next review date. Link the brief from the project channel.\n\nFor onboarding, a buddy helps each new teammate set up their tools and read the team handbook. The first week ends with a short feedback conversation. Ask questions early and leave useful notes for the next person.",
            'Shipping a thoughtful release' => "Before a release, the release owner reviews the change list, checks the automated tests and confirms the rollback plan. Every change must have a reviewer and a clear acceptance check.\n\nThe release checklist includes database backup verification, migration review, smoke tests and a support handover. Start with a small rollout and monitor the error rate before widening it.\n\nIf the error rate rises, pause the rollout and use the documented rollback plan. Record what happened in the release log, including the owner and the time of the decision.",
            'Customer support playbook' => "The support team responds to new standard support requests within one business day. Urgent access incidents are triaged within one hour during shared working hours. These are fictional demo service targets.\n\nA helpful support reply acknowledges the question, shares the next concrete step and explains when the customer will hear back. Link the exact source when referencing a product rule.\n\nEscalate repeat issues to the product owner with a short description, the impact and steps to reproduce. Remove personal information from the example before sharing it with the team.",
            'Design principles, made practical' => "Our first design principle is clarity. Use familiar words, visible labels and a clear next action. A person should understand the purpose of a screen before they interact with it.\n\nAccessibility is part of the design process. Every input has a label, keyboard focus stays visible and error messages explain how to recover. Test the primary task at a narrow mobile width.\n\nStart with a small coherent set of components. Reuse the spacing scale and type styles, and document a new pattern only when an existing pattern cannot express the task clearly.",
            'Looking after shared information' => "Shared information has an owner and a review date. Store only the information the team needs, and use fictional examples in demonstrations. Do not paste credentials, payment details or private customer records into a demo workspace.\n\nReview access when a teammate changes projects. Grant the least access needed for the task and remove unused invitations. Keep access changes in the team's audit record.\n\nA document owner reviews important instructions once each quarter. Outdated guidance is corrected through a new version, with a short note explaining what changed.",
            'Incident response field notes' => "An incident starts when a service disruption needs coordinated attention. The first responder creates an incident channel, names an incident lead and writes a brief statement of current impact.\n\nThe incident lead assigns investigation, communication and recovery roles. Update the status note every thirty minutes while the incident is active, even if the investigation has not found a cause.\n\nAfter recovery, run a short review focused on the timeline and contributing conditions. Create a small number of actionable follow-ups with named owners. Keep the review factual and useful.",
        ];
    }
}
