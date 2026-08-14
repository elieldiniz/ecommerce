<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div>
            <flux:heading size="lg">Tem certeza que deseja excluir sua conta?</flux:heading>

            <flux:subheading>
                Depois que sua conta for excluída, todos os seus recursos e dados serão apagados permanentemente. Informe sua senha para confirmar que deseja excluir sua conta.
            </flux:subheading>
        </div>

        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Senha</label>
            <input type="password" wire:model="password" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            @error('password')
                <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Cancelar</button>
            </flux:modal.close>

            <button type="submit" class="rounded-lg bg-[#8f2020] px-4 py-2.5 font-sans text-xs font-semibold text-white" data-test="confirm-delete-user-button">Excluir conta</button>
        </div>
    </form>
</flux:modal>
