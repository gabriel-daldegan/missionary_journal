<?php

namespace App\Livewire\Filament;

class TwitterSettings extends OauthProviderSettings
{
    protected string $slug = 'twitter-oauth-2';

    public function render()
    {
        return view('livewire.filament.twitter-settings');
    }
}
