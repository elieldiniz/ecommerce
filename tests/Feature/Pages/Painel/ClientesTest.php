<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\HolderType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/clientes/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_livewire_component(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.clientes')
            ->assertOk();
    }

    public function test_block_filtros_renders_four_filters_and_search(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.clientes')
            ->assertSee('Tipo de pessoa')
            ->assertSee('UF')
            ->assertSee('Período de cadastro')
            ->assertSee('Com certificado vencendo')
            ->assertSee('Buscar');
    }

    public function test_table_renders_six_columns(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.clientes')
            ->assertSeeInOrder(['Nome ou razão social', 'Documento', 'Tipo', 'E-mail', 'Pedidos', 'Última compra']);
    }

    public function test_table_lists_customers_from_database(): void
    {
        $this->actingAs(User::factory()->create());

        $pf = HolderType::factory()->create(['slug' => 'pf', 'name' => 'Pessoa Física']);
        $pj = HolderType::factory()->create(['slug' => 'pj', 'name' => 'Pessoa Jurídica']);

        Customer::factory()->create(['legal_name' => 'Maria Aparecida Souza', 'holder_type_id' => $pf->id]);
        Customer::factory()->create(['legal_name' => 'Comércio Digital Lock Ltda', 'holder_type_id' => $pj->id]);
        Customer::factory()->create(['legal_name' => 'Roberto Lima Castro', 'holder_type_id' => $pf->id]);

        Livewire::test('pages::painel.clientes')
            ->assertSee('Maria Aparecida Souza')
            ->assertSee('Comércio Digital Lock Ltda')
            ->assertSee('Roberto Lima Castro');
    }

    public function test_filter_by_holder_type(): void
    {
        $this->actingAs(User::factory()->create());

        $pf = HolderType::factory()->create(['slug' => 'pf', 'name' => 'Pessoa Física']);
        $pj = HolderType::factory()->create(['slug' => 'pj', 'name' => 'Pessoa Jurídica']);

        $customerPF = Customer::factory()->create(['holder_type_id' => $pf->id]);
        $customerPJ = Customer::factory()->create(['holder_type_id' => $pj->id]);

        Livewire::test('pages::painel.clientes')
            ->set('tipoPessoa', 'pf')
            ->assertSee($customerPF->legal_name)
            ->assertDontSee($customerPJ->legal_name);

        Livewire::test('pages::painel.clientes')
            ->set('tipoPessoa', 'pj')
            ->assertSee($customerPJ->legal_name)
            ->assertDontSee($customerPF->legal_name);
    }

    public function test_filter_by_uf(): void
    {
        $this->actingAs(User::factory()->create());

        $pf = HolderType::factory()->create(['slug' => 'pf', 'name' => 'Pessoa Física']);

        $customerSP = Customer::factory()->create(['holder_type_id' => $pf->id]);
        CustomerAddress::factory()->create(['customer_id' => $customerSP->id, 'state' => 'SP', 'is_primary' => true]);

        $customerRJ = Customer::factory()->create(['holder_type_id' => $pf->id]);
        CustomerAddress::factory()->create(['customer_id' => $customerRJ->id, 'state' => 'RJ', 'is_primary' => true]);

        Livewire::test('pages::painel.clientes')
            ->set('uf', 'SP')
            ->assertSee($customerSP->legal_name)
            ->assertDontSee($customerRJ->legal_name);
    }

    public function test_search_by_name(): void
    {
        $this->actingAs(User::factory()->create());

        $pf = HolderType::factory()->create(['slug' => 'pf', 'name' => 'Pessoa Física']);

        Customer::factory()->create(['legal_name' => 'Maria Aparecida Souza', 'holder_type_id' => $pf->id]);
        Customer::factory()->create(['legal_name' => 'Roberto Lima Castro', 'holder_type_id' => $pf->id]);

        Livewire::test('pages::painel.clientes')
            ->set('busca', 'Maria')
            ->assertSee('Maria Aparecida Souza')
            ->assertDontSee('Roberto Lima Castro');
    }

    public function test_empty_list_shows_message(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.clientes')
            ->set('busca', 'nonexistent')
            ->assertSee('Nenhum cliente encontrado');
    }

    public function test_select_customer_highlights_row(): void
    {
        $this->actingAs(User::factory()->create());

        $pf = HolderType::factory()->create(['slug' => 'pf', 'name' => 'Pessoa Física']);
        $customer = Customer::factory()->create(['holder_type_id' => $pf->id]);

        Livewire::test('pages::painel.clientes')
            ->call('selectCustomer', $customer->id)
            ->assertSet('selectedCustomerId', $customer->id);
    }

    public function test_deselect_customer_on_second_click(): void
    {
        $this->actingAs(User::factory()->create());

        $pf = HolderType::factory()->create(['slug' => 'pf', 'name' => 'Pessoa Física']);
        $customer = Customer::factory()->create(['holder_type_id' => $pf->id]);

        Livewire::test('pages::painel.clientes')
            ->call('selectCustomer', $customer->id)
            ->assertSet('selectedCustomerId', $customer->id)
            ->call('selectCustomer', $customer->id)
            ->assertSet('selectedCustomerId', null);
    }

    public function test_detail_sections_hidden_when_no_customer_selected(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.clientes')
            ->assertDontSee('Ficha do cliente · dados')
            ->assertDontSee('Histórico de pedidos')
            ->assertDontSee('Titulares vinculados');
    }
}
