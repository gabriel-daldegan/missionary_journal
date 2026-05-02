<div>
    <div class="mx-4">
        <div class="card max-w-3xl bg-base-100 shadow-xl mx-auto">
            <div class="card-body">
                @svg('info', 'w-16 h-16 mx-auto text-primary-500 stroke-primary-500')

                <div class="text-center">
                    <x-heading.h3 class="text-primary-900">
                        {{ __('Verify Your Phone Number To Continue') }}
                    </x-heading.h3>
                    <p>
                        {{ __('Before you can continue, we need to verify your phone number.') }}
                    </p>
                </div>

                @php
                    $validPhoneNumber = !empty($phone) && !$errors->has('phone');
                @endphp

                <div class="mt-8 mx-auto">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="form-control w-full" for="phone">
                                <div class="label">
                                    <span class="label-text">{{ __('Your Mobile Phone Number') }}</span>
                                </div>
                                <input type="text" class="input input-bordered input-md w-full" name="phone" required id="phone" wire:model="phone" @disabled($validPhoneNumber)>
                            </label>

                            @error('phone')
                                <span class="text-xs text-red-500" role="alert">
                                    {{ $message }}
                                </span>
                            @enderror
                            @if ($validPhoneNumber)
                                <a wire:click="editPhone" class="text-primary-500 hover:underline cursor-pointer text-xxs ms-2">
                                    {{ __('Edit Phone Number') }}
                                </a>
                            @endif
                        </div>

                        @if (!empty($phone) && !$errors->has('phone'))
                            <div>
                                <label class="form-control w-full" for="code">
                                    <div class="label">
                                        <span class="label-text">{{ __('Enter Verification Code') }}</span>
                                    </div>
                                    <input type="text" class="input input-bordered input-md w-full" name="code" required id="code" wire:model="code">
                                </label>

                                @error('code')
                                <span class="text-xs text-red-500" role="alert">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>

                            <x-button-link.primary class="flex flex-row items-center justify-center gap-3 min-w-64! disabled:opacity-40" elementType="button" wire:click="verifyCode" wire:loading.attr="disabled">
                                {{ __('Verify Phone') }}
                                <div wire:loading class="max-w-fit max-h-fit">
                                    <span class="loading loading-ring loading-xs"></span>
                                </div>
                            </x-button-link.primary>
                        @else
                            <x-button-link.primary class="flex flex-row items-center justify-center gap-3 min-w-64! disabled:opacity-40" elementType="button" wire:click="sendVerificationCode" wire:loading.attr="disabled">
                                {{ __('Send Verification Code') }}
                                <div wire:loading class="max-w-fit max-h-fit">
                                    <span class="loading loading-ring loading-xs"></span>
                                </div>
                            </x-button-link.primary>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
