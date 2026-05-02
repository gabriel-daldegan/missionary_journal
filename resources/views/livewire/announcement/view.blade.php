<div class="text-sm" x-data="{ isVisible: true }">
    @if ($announcement !== null)
        <div class="bg-secondary-300 text-secondary-900 text-center announcement px-4" x-show="isVisible">
            <div class="mx-auto py-3 flex gap-2 md:gap-8 items-center justify-center">
                <span class="line-clamp-3 md:line-clamp-2" >
                    {!! str($announcement->content)->sanitizeHtml() !!}
                </span>
                @if ($announcement->is_dismissible)
                <a wire:click="dismiss({{ $announcement->id }})" class="cursor-pointer text-primary-900 hover:scale-103 hover:text-primary-900 transition announcement-close-button" aria-label="{{ __('Close') }}" @click="isVisible = !isVisible">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </a>
                @endif
            </div>
        </div>
    @endif
</div>
