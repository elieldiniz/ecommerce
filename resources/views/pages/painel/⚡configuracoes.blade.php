<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'configuracoes', 'title' => 'Configurações'])] class extends Component {
    use PasswordValidationRules, ProfileValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    public string $name = '';

    public string $email = '';

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;

        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Senha atualizada.'));
    }

    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Perfil atualizado.'));
    }
}; ?>

<div>
    <section class="rounded-xl border border-border bg-white p-5">
        <h2 class="mb-1 font-heading text-lg font-bold text-ink">Atualizar senha</h2>
        <p class="mb-4 font-sans text-xs text-muted-light">Garanta que sua conta está usando uma senha longa e aleatória para se manter segura.</p>

        <form wire:submit="updatePassword" class="max-w-lg space-y-4">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Senha atual</label>
                <input type="password" wire:model="current_password" autocomplete="current-password" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('current_password')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nova senha</label>
                <input type="password" wire:model="password" autocomplete="new-password" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('password')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Confirmar senha</label>
                <input type="password" wire:model="password_confirmation" autocomplete="new-password" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>

            <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white" data-test="update-password-button">Salvar</button>
        </form>
    </section>

    @if ($canManageTwoFactor)
        <section class="mt-8 rounded-xl border border-border bg-white p-5" wire:cloak>
            <h2 class="mb-1 font-heading text-lg font-bold text-ink">Autenticação de dois fatores</h2>
            <p class="mb-4 font-sans text-xs text-muted-light">Gerencie as configurações de autenticação de dois fatores da sua conta.</p>

            @if ($twoFactorEnabled)
                <div class="max-w-lg space-y-4">
                    <p class="font-sans text-sm text-ink">Você será solicitado a informar um PIN seguro e aleatório durante o login, que pode ser obtido no aplicativo compatível com TOTP no seu celular.</p>

                    <button type="button" wire:click="disable" class="rounded-lg border border-[#8f2020] px-4 py-2.5 font-sans text-xs font-semibold text-[#8f2020]">Desativar 2FA</button>

                    <livewire:pages::painel.configuracoes.two-factor.recovery-codes :$requiresConfirmation />
                </div>
            @else
                <div class="max-w-lg space-y-4">
                    <p class="font-sans text-sm text-muted">Quando você ativa a autenticação de dois fatores, será solicitado um PIN seguro durante o login. Esse PIN pode ser obtido em um aplicativo compatível com TOTP no seu celular.</p>

                    <flux:modal.trigger name="two-factor-setup-modal">
                        <button type="button" wire:click="$dispatch('start-two-factor-setup')" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white">Ativar 2FA</button>
                    </flux:modal.trigger>

                    <livewire:pages::painel.configuracoes.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                </div>
            @endif
        </section>
    @endif

    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-1 font-heading text-lg font-bold text-ink">Perfil</h2>
        <p class="mb-4 font-sans text-xs text-muted-light">Atualize seu nome e endereço de e-mail.</p>

        <form wire:submit="updateProfileInformation" class="max-w-lg space-y-4">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nome</label>
                <input type="text" wire:model="name" autocomplete="name" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('name')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
                <input type="email" wire:model="email" autocomplete="email" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('email')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white" data-test="update-profile-button">Salvar</button>
        </form>
    </section>

    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-1 font-heading text-lg font-bold text-ink">Excluir conta</h2>
        <p class="mb-4 font-sans text-xs text-muted-light">Exclua sua conta e todos os seus recursos.</p>

        <flux:modal.trigger name="confirm-user-deletion">
            <button type="button" class="rounded-lg border border-[#8f2020] px-4 py-2.5 font-sans text-xs font-semibold text-[#8f2020]" data-test="delete-user-button">Excluir conta</button>
        </flux:modal.trigger>

        <livewire:pages::painel.configuracoes.delete-account-modal />
    </section>
</div>
