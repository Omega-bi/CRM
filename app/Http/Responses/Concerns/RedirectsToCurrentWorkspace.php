<?php

namespace App\Http\Responses\Concerns;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

trait RedirectsToCurrentWorkspace
{
    protected function redirectPathForCurrentWorkspace(Request $request, string $redirect): string
    {
        $workspace = $this->currentWorkspace($request);

        if (! $workspace) {
            return route('dashboard', absolute: false);
        }

        URL::defaults(['current_workspace' => $workspace->slug]);

        return "/{$workspace->slug}{$redirect}";
    }

    protected function currentWorkspace(Request $request): ?Workspace
    {
        $user = $request->user();

        abort_if(! $user, 403);

        return $user->currentWorkspace ?? $user->personalWorkspace();
    }
}
