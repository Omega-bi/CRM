<?php

use App\Models\WorkspaceInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Workspaces')] class extends Component {
    public WorkspaceInvitation $invitation;

    public function mount(WorkspaceInvitation $invitation): void
    {
        $this->invitation = $invitation;

        $this->acceptInvitation();
    }

    public function acceptInvitation(): void
    {
        $user = Auth::user();

        $this->validateInvitation($user, $this->invitation);

        DB::transaction(function () use ($user) {
            $workspace = $this->invitation->workspace;

            $membership = $workspace->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $this->invitation->role]
            );

            $this->invitation->update(['accepted_at' => now()]);

            $user->switchWorkspace($workspace);
        });

        session()->flash('workspace-invitation-accepted', true);

        $this->redirectRoute('dashboard', navigate: true);
    }

    private function validateInvitation(User $user, WorkspaceInvitation $invitation): void
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'invitation' => [__('This invitation has already been accepted.')],
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'invitation' => [__('This invitation has expired.')],
            ]);
        }

        if (Str::lower($invitation->email) !== Str::lower($user->email)) {
            throw ValidationException::withMessages([
                'invitation' => [__('This invitation was sent to a different email address.')],
            ]);
        }
    }
}; ?>

<div></div>
