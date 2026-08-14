<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Não foi possível carregar os códigos de recuperação.');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<div
    class="rounded-xl border border-border py-6 space-y-6"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="px-6 space-y-2">
        <div class="flex items-center gap-3">
            <flux:icon.lock-closed variant="outline" class="size-4 text-muted"/>
            <h3 class="font-heading text-sm font-bold text-ink">Códigos de recuperação do 2FA</h3>
        </div>
        <p class="font-sans text-xs text-muted-light">
            Os códigos de recuperação permitem recuperar o acesso se você perder o dispositivo do 2FA. Guarde-os em um gerenciador de senhas seguro.
        </p>
    </div>

    <div class="px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <button
                type="button"
                x-show="!showRecoveryCodes"
                @click="showRecoveryCodes = true"
                aria-expanded="false"
                aria-controls="recovery-codes-section"
                class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white"
            >
                Ver códigos de recuperação
            </button>

            <button
                type="button"
                x-show="showRecoveryCodes"
                @click="showRecoveryCodes = false"
                aria-expanded="true"
                aria-controls="recovery-codes-section"
                class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white"
            >
                Ocultar códigos de recuperação
            </button>

            @if (filled($recoveryCodes))
                <button
                    type="button"
                    x-show="showRecoveryCodes"
                    wire:click="regenerateRecoveryCodes"
                    class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink"
                >
                    Gerar novos códigos
                </button>
            @endif
        </div>

        <div
            x-show="showRecoveryCodes"
            x-transition
            id="recovery-codes-section"
            class="relative overflow-hidden"
            x-bind:aria-hidden="!showRecoveryCodes"
        >
            <div class="mt-3 space-y-3">
                @error('recoveryCodes')
                    <p class="font-sans text-xs text-[#8f2020]">{{ $message }}</p>
                @enderror

                @if (filled($recoveryCodes))
                    <div
                        class="grid gap-1 rounded-lg bg-surface-alt p-4 font-mono text-sm text-ink"
                        role="list"
                        aria-label="Códigos de recuperação"
                    >
                        @foreach($recoveryCodes as $code)
                            <div
                                role="listitem"
                                class="select-text"
                                wire:loading.class="opacity-50 animate-pulse"
                            >
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                    <p class="font-sans text-xs text-muted-light">
                        Cada código de recuperação pode ser usado uma vez para acessar sua conta e será removido após o uso. Se precisar de mais, clique em "Gerar novos códigos".
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
