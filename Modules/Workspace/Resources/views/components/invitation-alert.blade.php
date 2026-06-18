@props([
    'invitation',
    'action',
])

<div data-test="workspace-invitation-alert">
    <div class="flex gap-3 rounded-lg border border-[#013763]/20 bg-[#013763]/5 px-4 py-3 text-sm text-[#013763]">
        <flux:icon name="information-circle" class="mt-0.5 size-4 shrink-0 text-[#013763]" />

        <div>
            {{ __(':action to join the ":workspace" workspace.', ['action' => $action, 'workspace' => $invitation['workspaceName']]) }}
        </div>
    </div>
</div>
