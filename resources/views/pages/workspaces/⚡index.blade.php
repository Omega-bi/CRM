<?php

use App\Data\UserWorkspace;
use App\Models\Workspace;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Workspace\Actions\CreateWorkspace;
use Modules\Workspace\Rules\WorkspaceName;

new #[Title('Workspaces')] class extends Component {
    public string $name = '';

    public function createWorkspace(CreateWorkspace $createWorkspace): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', new WorkspaceName],
        ]);

        $workspace = $createWorkspace->handle(Auth::user(), $validated['name']);

        $this->dispatch('close-modal', name: 'create-workspace');

        $this->reset('name');

        Flux::toast(variant: 'success', text: __('Workspace created.'));

        $this->redirectRoute('workspaces.edit', ['workspace' => $workspace->slug], navigate: true);
    }

    public function leaveWorkspace(int $workspaceId): void
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $user = Auth::user();

        Gate::authorize('leave', $workspace);

        $fallbackWorkspace = $user->isCurrentWorkspace($workspace)
            ? $user->fallbackWorkspace($workspace)
            : null;

        $workspace->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($fallbackWorkspace) {
            $user->switchWorkspace($fallbackWorkspace);
        }

        $this->dispatch('close-modal', name: "leave-workspace-{$workspaceId}");

        Flux::toast(variant: 'success', text: __('You left the workspace ":name"', ['name' => $workspace->name]));

        $this->redirectRoute('workspaces.index', navigate: true);
    }

    /**
     * @return Collection<int, UserWorkspace>
     */
    #[Computed]
    public function workspaces(): Collection
    {
        return Auth::user()->toUserWorkspaces(includeCurrent: true);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Workspaces') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Workspaces')" :subheading="__('Manage your workspaces and memberships')">
        <div class="flex items-center justify-end">
            <flux:modal.trigger name="create-workspace">
                <flux:button variant="primary" icon="plus" x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-workspace')" data-test="workspaces-new-workspace-button">
                    {{ __('Create workspace') }}
                </flux:button>
            </flux:modal.trigger>
        </div>

        <div class="mt-6 space-y-3">
            @forelse ($this->workspaces as $workspace)
                <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" data-test="workspace-row">
                    <div class="flex items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $workspace->name }}</span>
                                @if ($workspace->isPersonal)
                                    <flux:badge color="zinc">{{ __('Personal') }}</flux:badge>
                                @endif
                            </div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $workspace->roleLabel }}</flux:text>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        @if (! $workspace->isPersonal && $workspace->role !== 'owner')
                            <flux:modal.trigger :name="'leave-workspace-'.$workspace->id">
                                    <flux:tooltip :content="__('Leave workspace')">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="arrow-right-start-on-rectangle"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'leave-workspace-{{ $workspace->id }}')"
                                        data-test="workspace-leave-button"
                                    />
                                </flux:tooltip>
                            </flux:modal.trigger>
                        @endif

                        <flux:tooltip :content="$workspace->role === 'member' ? __('View workspace') : __('Edit workspace')">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                :icon="$workspace->role === 'member' ? 'eye' : 'pencil'"
                                :href="route('workspaces.edit', $workspace->slug)"
                                wire:navigate
                                :data-test="$workspace->role === 'member' ? 'workspace-view-button' : 'workspace-edit-button'"
                            />
                        </flux:tooltip>
                    </div>
                </div>

                @if (! $workspace->isPersonal && $workspace->role !== 'owner')
                    <flux:modal :name="'leave-workspace-'.$workspace->id" focusable class="max-w-lg">
                        <form wire:submit="leaveWorkspace({{ $workspace->id }})" class="space-y-6">
                            <div>
                                <flux:heading size="lg">{{ __('Leave workspace') }}</flux:heading>
                                <flux:subheading>
                                    {{ __('Are you sure you want to leave :name?', ['name' => $workspace->name]) }}
                                </flux:subheading>
                            </div>

                            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                                <flux:modal.close>
                                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>

                                <flux:button variant="danger" type="submit" data-test="leave-workspace-confirm">
                                    {{ __('Leave workspace') }}
                                </flux:button>
                            </div>
                        </form>
                    </flux:modal>
                @endif
            @empty
                <flux:text class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                    {{ __('You don\'t belong to any workspaces yet.') }}
                </flux:text>
            @endforelse
        </div>
    </x-pages::settings.layout>

    <flux:modal name="create-workspace" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form wire:submit="createWorkspace" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create workspace') }}</flux:heading>
                <flux:subheading>{{ __('Give your workspace a name to get started.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Workspace name')" type="text" required autofocus data-test="create-workspace-name" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="create-workspace-submit">
                    {{ __('Create workspace') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
