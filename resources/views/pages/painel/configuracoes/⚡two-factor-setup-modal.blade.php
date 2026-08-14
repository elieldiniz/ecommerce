<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $setupComplete = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Não foi possível carregar os dados de configuração.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->setupComplete = true;

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showVerificationStep',
            'setupComplete',
        );

        $this->resetErrorBag();
    }

    /**
     * Get the current modal configuration state.
     */
    #[Computed]
    public function modalConfig(): array
    {
        if ($this->setupComplete) {
            return [
                'title' => 'Autenticação de dois fatores ativada',
                'description' => 'A autenticação de dois fatores está ativada. Escaneie o QR code ou informe a chave de configuração no seu aplicativo autenticador.',
                'buttonText' => 'Fechar',
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => 'Verificar código de autenticação',
                'description' => 'Informe o código de 6 dígitos do seu aplicativo autenticador.',
                'buttonText' => 'Continuar',
            ];
        }

        return [
            'title' => 'Ativar autenticação de dois fatores',
            'description' => 'Para concluir a ativação, escaneie o QR code ou informe a chave de configuração no seu aplicativo autenticador.',
            'buttonText' => 'Continuar',
        ];
    }
}; ?>

<flux:modal
    name="two-factor-setup-modal"
    class="max-w-md md:min-w-md"
    @close="closeModal"
>
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="w-auto rounded-full border border-border bg-white p-0.5 shadow-sm">
                    <div class="relative overflow-hidden rounded-full border border-border bg-surface-alt p-2.5">
                        <flux:icon.qr-code class="relative z-20 text-ink"/>
                    </div>
                </div>

                <div class="space-y-2 text-center">
                    <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
                    <p class="font-sans text-sm text-muted">{{ $this->modalConfig['description'] }}</p>
                </div>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div
                        class="flex flex-col items-center space-y-3 justify-center"
                        x-data
                        x-init="$nextTick(() => $el.querySelector('input')?.focus())"
                    >
                        <flux:otp
                            name="code"
                            wire:model="code"
                            length="6"
                            label="Código OTP"
                            label:sr-only
                            class="mx-auto"
                        />
                    </div>

                    <div class="flex items-center space-x-3">
                        <button type="button" wire:click="resetVerification" class="flex-1 rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">
                            Voltar
                        </button>

                        <button
                            type="button"
                            wire:click="confirmTwoFactor"
                            x-bind:disabled="$wire.code.length < 6"
                            class="flex-1 rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white disabled:opacity-50"
                        >
                            Confirmar
                        </button>
                    </div>
                </div>
            @else
                @error('setupData')
                    <p class="font-sans text-xs text-[#8f2020]">{{ $message }}</p>
                @enderror

                <div class="flex justify-center">
                    <div class="relative aspect-square w-64 overflow-hidden rounded-lg border border-border">
                        @empty($qrCodeSvg)
                            <div class="absolute inset-0 flex animate-pulse items-center justify-center bg-surface-alt">
                                <flux:icon.loading/>
                            </div>
                        @else
                            <div x-data class="flex items-center justify-center h-full p-4">
                                <div class="rounded bg-white p-3">
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>
                        @endempty
                    </div>
                </div>

                <div>
                    <button
                        type="button"
                        :disabled="$errors->has('setupData')"
                        wire:click="showVerificationIfNecessary"
                        class="w-full rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white disabled:opacity-50"
                    >
                        {{ $this->modalConfig['buttonText'] }}
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="relative flex items-center justify-center w-full">
                        <div class="absolute inset-0 top-1/2 h-px w-full bg-border"></div>
                        <span class="relative bg-white px-2 font-sans text-sm text-muted">
                            ou informe o código manualmente
                        </span>
                    </div>

                    <div
                        class="flex items-center space-x-2"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Could not copy to clipboard');
                                }
                            }
                        }"
                    >
                        <div class="flex items-stretch w-full rounded-xl border border-border">
                            @empty($manualSetupKey)
                                <div class="flex w-full items-center justify-center bg-surface-alt p-3">
                                    <flux:icon.loading variant="mini"/>
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="w-full bg-transparent p-3 text-ink outline-none"
                                />

                                <button
                                    @click="copy()"
                                    class="cursor-pointer border-l border-border px-3 transition-colors"
                                >
                                    <flux:icon.document-duplicate x-show="!copied" variant="outline"></flux:icon>
                                    <flux:icon.check
                                        x-show="copied"
                                        variant="solid"
                                        class="text-[#1e5c34]"
                                    ></flux:icon>
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif
        </div>
</flux:modal>
