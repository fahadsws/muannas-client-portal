<?php

namespace Tests\Feature;

use App\Mail\PortalNotice;
use App\Models\Account;
use App\Models\Agency;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceModuleTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $client;

    private Account $acct;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        $this->agency = Agency::create(['name' => 'Demo Agency', 'brand_color' => '#111111', 'max_workspaces' => 50, 'max_members' => 20]);
        Role::ensureDefaults($this->agency);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@t.test', 'password' => 'secret123', 'role' => 'owner', 'agency_id' => $this->agency->id]);
        $this->acct = Account::create(['agency_id' => $this->agency->id, 'name' => 'Noor', 'token' => Str::random(24), 'share_enabled' => false, 'billing_address' => '1 Palm Rd']);
        $this->client = User::create(['name' => 'Noor Owner', 'email' => 'noor@t.test', 'password' => 'secret123', 'role' => 'guest']);
        $this->acct->people()->attach($this->client->id, ['perm' => 'comment']);
    }

    private function payload(array $extra = []): array
    {
        return $extra + [
            'items' => [
                ['description' => 'Reels', 'qty' => 3, 'unit_price' => 100, 'taxes' => [['name' => 'VAT', 'rate' => 5]]],
                ['description' => 'Strategy', 'qty' => 1, 'unit_price' => 50.5, 'taxes' => [['name' => 'VAT', 'rate' => 5]]],
                ['description' => '', 'qty' => 1, 'unit_price' => ''],
            ],
            'discount_type' => 'percent', 'discount_value' => 10,
            'issued_on' => today()->toDateString(), 'due_on' => today()->addDays(14)->toDateString(), 'title' => 'October', 'action' => 'draft',
        ];
    }

    public function test_numbers_increase_with_prefix_and_padding(): void
    {
        $this->actingAs($this->admin)->post('/portal/invoice-settings', [
            'prefix' => 'MA-', 'padding' => 3, 'next_number' => 7, 'due_days' => 10, 'label_qty' => 'Hours', 'label_rate' => '',
            'business_name' => 'Muannas', 'terms' => 'Pay in 10 days', 'notes' => 'Thanks',
        ])->assertRedirect();
        foreach (range(1, 3) as $_) {
            $this->post('/portal/c/'.$this->acct->id.'/invoices', $this->payload())->assertRedirect();
        }
        $this->assertSame(['MA-007', 'MA-008', 'MA-009'], Invoice::orderBy('id')->pluck('number')->all());
        $this->assertSame(10, $this->agency->refresh()->invoiceSettings()['next_number']);
        $this->admin->unsetRelation('agency');
        $this->get('/portal/invoice-settings')->assertOk()->assertSee('MA-010');
    }

    public function test_totals_are_calculated_from_line_items(): void
    {
        $this->actingAs($this->admin)->post('/portal/c/'.$this->acct->id.'/invoices', $this->payload())->assertRedirect();
        $i = $this->acct->invoices()->first();
        $this->assertCount(2, $i->items);   // the blank row is dropped
        $this->assertEquals(350.5, $i->subtotal);
        $this->assertEquals(35.05, $i->discount);
        $this->assertEquals(15.77, $i->tax_amount);
        $this->assertEquals(331.22, $i->amount);
        $this->assertTrue($i->draft);
        $this->assertSame('INV-0001', $i->number);
    }

    public function test_drafts_are_hidden_from_the_client_until_sent(): void
    {
        $this->actingAs($this->admin)->post('/portal/c/'.$this->acct->id.'/invoices', $this->payload())->assertRedirect();
        $i = $this->acct->invoices()->first();
        $this->flushSession();
        $this->actingAs($this->client)->get('/portal/c/'.$this->acct->id.'/invoices')->assertDontSee('INV-0001');
        $this->actingAs($this->client)->get('/portal/invoices/'.$i->id)->assertNotFound();

        $this->actingAs($this->admin)->post('/portal/invoices/'.$i->id.'/send', ['to' => ['noor@t.test'], 'message' => 'Hello'])->assertRedirect();
        Mail::assertQueued(PortalNotice::class, fn ($m) => $m->hasTo('noor@t.test') && $m->invoiceId === $i->id && count($m->attachments()) === 1);
        $this->assertFalse($i->fresh()->draft);
        $this->actingAs($this->client)->get('/portal/invoices/'.$i->id)->assertOk()->assertSee('INV-0001')->assertSee('Reels')->assertDontSee('Mark paid')->assertDontSee('/edit');
    }

    public function test_edit_and_delete_only_while_unpaid(): void
    {
        $this->actingAs($this->admin)->post('/portal/c/'.$this->acct->id.'/invoices', $this->payload(['action' => 'send']))->assertRedirect();
        $i = $this->acct->invoices()->first();
        $this->get('/portal/invoices/'.$i->id)->assertOk()->assertSee('/portal/invoices/'.$i->id.'/edit', false)->assertSee('Print');
        $this->get('/portal/invoices/'.$i->id.'/edit')->assertOk();
        $this->put('/portal/invoices/'.$i->id, $this->payload(['items' => [['description' => 'Only one', 'qty' => 2, 'unit_price' => 10]], 'discount_value' => 0]))->assertRedirect('/portal/invoices/'.$i->id);
        $this->assertEquals(20, $i->fresh()->amount);

        $this->post('/portal/invoices/'.$i->id.'/paid', ['paid_on' => today()->toDateString(), 'payment_method' => 'bank', 'payment_ref' => 'TX1'])->assertRedirect();
        $this->get('/portal/invoices/'.$i->id)->assertOk()->assertDontSee('/portal/invoices/'.$i->id.'/edit', false)->assertSee('Mark unpaid')->assertSee('TX1');
        $this->get('/portal/invoices/'.$i->id.'/edit')->assertForbidden();
        $this->put('/portal/invoices/'.$i->id, $this->payload())->assertForbidden();
        $this->delete('/portal/invoices/'.$i->id)->assertForbidden();

        $this->post('/portal/invoices/'.$i->id.'/unpaid')->assertRedirect();
        $this->delete('/portal/invoices/'.$i->id)->assertRedirect('/portal/c/'.$this->acct->id.'/invoices');
        $this->assertSame(0, Invoice::count());
    }

    public function test_pdf_is_generated_and_numbers_are_unique_per_agency(): void
    {
        $this->actingAs($this->admin)->post('/portal/c/'.$this->acct->id.'/invoices', $this->payload(['action' => 'send']))->assertRedirect();
        $i = $this->acct->invoices()->first();
        $res = $this->get('/portal/invoices/'.$i->id.'/pdf');
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->post('/portal/c/'.$this->acct->id.'/invoices', $this->payload(['number' => 'INV-0001']))->assertSessionHasErrors('number');

        // another agency may reuse the same number
        $other = Agency::create(['name' => 'Other', 'brand_color' => '#222222', 'max_workspaces' => 5, 'max_members' => 5]);
        $oa = Account::create(['agency_id' => $other->id, 'name' => 'X', 'token' => Str::random(24), 'share_enabled' => false]);
        $oa->invoices()->create(['number' => 'INV-0001', 'amount' => 1, 'currency' => 'AED', 'issued_on' => today(), 'due_on' => today()]);
        $this->assertSame(2, Invoice::where('number', 'INV-0001')->count());
    }

    public function test_multiple_taxes_line_discounts_project_and_client_currency(): void
    {
        $this->actingAs($this->admin);
        $project = $this->acct->projects()->create(['name' => 'Launch', 'status' => 'active']);
        $this->post('/portal/invoice-taxes', ['name' => 'VAT', 'rate' => 5, 'is_default' => 1])->assertRedirect();
        $this->post('/portal/invoice-taxes', ['name' => 'Service', 'rate' => 10], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('name', 'Service');
        $this->assertCount(2, $this->agency->invoiceTaxes);

        $this->post('/portal/c/'.$this->acct->id.'/invoices', [
            'items' => [['description' => 'Design', 'qty' => 2, 'unit_price' => 500, 'discount_type' => 'percent', 'discount_value' => 10, 'taxes' => [['name' => 'VAT', 'rate' => 5], ['name' => 'Service', 'rate' => 10]]]],
            'discount_type' => 'fixed', 'discount_value' => 0, 'project_id' => $project->id, 'issued_on' => today()->toDateString(), 'due_on' => today()->addDays(5)->toDateString(), 'action' => 'draft',
        ])->assertRedirect();
        $i = Invoice::first();
        $this->assertSame('AED', $i->currency);
        $this->assertSame($project->id, $i->project_id);
        $this->assertEquals(900, $i->subtotal);          // 1000 less 10% line discount
        $this->assertEquals(135, $i->tax_amount);        // 5% + 10% of 900
        $this->assertEquals(1035, $i->amount);
        $this->assertSame(['VAT', 'Service'], collect($i->tax_breakdown)->pluck('name')->all());
        $this->get('/portal/invoices/'.$i->id)->assertOk()->assertSee('Service 10%')->assertSee('Launch');
        $this->get('/portal/invoices/'.$i->id.'/pdf')->assertOk();

        // editing a tax never changes an issued invoice
        $this->put('/portal/invoice-taxes/'.$this->agency->invoiceTaxes()->first()->id, ['name' => 'VAT', 'rate' => 20])->assertRedirect();
        $this->assertEquals(1035, $i->fresh()->amount);
        $this->delete('/portal/invoice-taxes/'.$this->agency->invoiceTaxes()->first()->id)->assertRedirect();
        $this->assertCount(1, $this->agency->invoiceTaxes()->get());
        $this->get('/portal/c/'.$this->acct->id.'/invoices/new')->assertOk()->assertSee('INV-0002')->assertSee('Service');
    }

    public function test_client_currency_is_chosen_on_creation_and_locked_after_invoices(): void
    {
        $this->actingAs($this->admin)->post('/portal/clients', ['name' => 'Euro Co', 'currency' => 'EUR', 'client_name' => 'E', 'client_email' => 'e@t.test'])->assertRedirect();
        $a = Account::where('name', 'Euro Co')->first();
        $this->assertSame('EUR', $a->currency);
        $this->post('/portal/clients', ['name' => 'Bad Co', 'currency' => 'XXX', 'client_name' => 'B', 'client_email' => 'b@t.test'])->assertSessionHasErrors('currency');
        $this->post('/portal/c/'.$a->id.'/invoices', ['items' => [['description' => 'x', 'qty' => 1, 'unit_price' => 10]], 'discount_type' => 'fixed', 'issued_on' => today()->toDateString(), 'due_on' => today()->toDateString(), 'action' => 'draft'])->assertRedirect();
        $this->assertSame('EUR', Invoice::where('account_id', $a->id)->first()->currency);
        $this->put('/portal/clients/'.$a->id, ['name' => 'Euro Co', 'currency' => 'USD'])->assertRedirect();
        $this->assertSame('EUR', $a->fresh()->currency);
    }

    public function test_requires_a_line_item_and_billing_permission(): void
    {
        $this->actingAs($this->admin)->post('/portal/c/'.$this->acct->id.'/invoices', $this->payload(['items' => [['description' => '', 'qty' => 1, 'unit_price' => '']]]))->assertSessionHasErrors('items');
        $editor = Role::where('agency_id', $this->agency->id)->where('name', 'Editor')->first();
        $team = User::create(['name' => 'Sara', 'email' => 's@t.test', 'password' => 'secret123', 'role' => 'member', 'agency_id' => $this->agency->id, 'role_id' => $editor->id]);
        $this->actingAs($team)->get('/portal/invoice-settings')->assertForbidden();
        $this->actingAs($this->client)->get('/portal/invoice-settings')->assertForbidden();
        $this->actingAs($this->client)->get('/portal/c/'.$this->acct->id.'/invoices/new')->assertForbidden();
    }
}
