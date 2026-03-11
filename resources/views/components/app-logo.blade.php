@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="LYVA Studio" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-[0.9rem] border border-cyan-400/20 bg-[linear-gradient(180deg,rgba(18,34,68,0.96),rgba(9,17,36,0.82))] text-white shadow-[0_12px_24px_rgba(0,0,0,0.18)]">
            <x-app-logo-icon class="size-4 fill-current text-cyan-200" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="LYVA Studio" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
