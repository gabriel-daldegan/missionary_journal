<fieldset class="fieldset">
    <legend class="fieldset-legend font-medium">{{ __('Email Address') }}</legend>
    <input type="email" class="input w-full" name="email" required id="email" wire:model.blur="email" value="{{ old('email') }}" />
</fieldset>

@error('email')
<span class="text-xs text-red-500" role="alert">
    {{ $message }}
</span>
@enderror


@if(!empty($email))
    <fieldset class="fieldset">
        <legend class="fieldset-legend font-medium">{{ __('Password') }}</legend>
        <input type="password" class="input w-full" name="password" required id="password" wire:model="password" />
    </fieldset>

    @error('password')
    <span class="text-xs text-red-500 ms-1" role="alert">
        {{ $message }}
    </span>
    @enderror
@endif

@if ($userExists)
    <div class="my-2 ms-1 text-xs text-neutral-400">{{ __('You are already registered, enter your password.') }}</div>
@elseif(!empty($email))
    <div class="my-2 ms-1 text-xs text-neutral-400">{{ __('Enter a password for your new account.') }}</div>
@endif

@if($userExists)
    @if (Route::has('password.request'))
        <div class="text-end">
            <a class="text-primary-500 text-xs" href="{{ route('password.request') }}">
                {{ __('Forgot Your Password?') }}
            </a>
        </div>
    @endif
@endif


@if(!$userExists || empty($email))

    <fieldset class="fieldset">
        <legend class="fieldset-legend font-medium">{{ __('Your Name') }}</legend>
        <input type="text" class="input w-full" name="name" required id="name" wire:model="name" value="{{ old('name') }}" />
    </fieldset>

    @error('name')
    <span class="text-xs text-red-500" role="alert">
        {{ $message }}
    </span>
    @enderror
@endif

@include('livewire.auth.partials.recaptcha')
