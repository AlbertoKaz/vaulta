<?php

function current_workspace()
{
    if (! auth()->check()) {
        return null;
    }

    return auth()->user()->currentWorkspace();
}
