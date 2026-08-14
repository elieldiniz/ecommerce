@props(['mock' => false])

<flux:dropdown position="bottom" align="start">
    <button
        type="button"
        class="group flex w-full items-center rounded-lg p-1 hover:bg-zinc-800/5 dark:hover:bg-white/10 [ui-dropdown>&]:w-full"
        data-test="sidebar-menu-button"
    >
        <flux:avatar
            :name="auth()->user()->name"
            :initials="auth()->user()->initials()"
            size="sm"
        />

        <div class="in-data-flux-sidebar-collapsed-desktop:hidden ms-2 grid flex-1 text-start text-sm leading-tight">
            <span class="truncate font-medium text-zinc-800 dark:text-white">{{ auth()->user()->name }}</span>
            <span class="truncate text-xs text-zinc-500 dark:text-white/70">{{ auth()->user()->role?->name }}</span>
        </div>

        <div class="in-data-flux-sidebar-collapsed-desktop:hidden ms-auto flex size-8 shrink-0 items-center justify-center">
            <flux:icon
                icon="chevrons-up-down"
                variant="micro"
                class="text-zinc-400 group-hover:text-zinc-800 dark:text-white/80 dark:group-hover:text-white"
            />
        </div>
    </button>

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>
        <flux:menu.separator />
        <flux:menu.radio.group>
            @if ($mock)
                <flux:menu.item :href="route('painel.configuracoes')" icon="cog">
                    Configurações
                </flux:menu.item>
                <flux:menu.item
                    as="button"
                    type="button"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                >
                    Sair
                </flux:menu.item>
            @else
                <flux:menu.item :href="route('painel.configuracoes')" icon="cog" wire:navigate>
                    Configurações
                </flux:menu.item>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item
                        as="button"
                        type="submit"
                        icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer"
                        data-test="logout-button"
                    >
                        Sair
                    </flux:menu.item>
                </form>
            @endif
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
