<?php

namespace Tests\Feature\Pos;

use App\Livewire\Pos\KoppelPoef;
use App\Models\Card;
use App\Models\Poef;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KoppelPoefTest extends TestCase
{
    use RefreshDatabase;

    private Poef $poef1;

    private Poef $poef2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->poef1 = Poef::create([
            'external_id' => 101,
            'name' => 'Charlotte Van Houtte',
            'discount_percentage' => 0,
        ]);

        $this->poef2 = Poef::create([
            'external_id' => 102,
            'name' => 'Mathieu Verhaeghe',
            'discount_percentage' => 10,
        ]);
    }

    public function test_koppel_poef_modal_can_be_opened_and_closed(): void
    {
        Livewire::test(KoppelPoef::class)
            ->assertSet('isOpen', false)
            ->dispatch('open-koppel-poef')
            ->assertSet('isOpen', true)
            ->assertSee('Koppel Poef')
            ->assertSee('Charlotte Van Houtte')
            ->assertSee('Mathieu Verhaeghe')
            ->call('close')
            ->assertSet('isOpen', false);
    }

    public function test_card_can_be_linked_to_a_poef(): void
    {
        Livewire::test(KoppelPoef::class)
            ->dispatch('open-koppel-poef')
            ->call('selectPoef', $this->poef1->id)
            ->set('cardUid', '04A1B2C3D4')
            ->set('cardType', 'badge')
            ->set('label', 'Blauwe sleutelhanger')
            ->call('linkCard')
            ->assertSee('is gekoppeld aan Charlotte Van Houtte')
            ->assertSet('cardUid', '');

        $this->assertDatabaseHas('cards', [
            'poef_id' => $this->poef1->id,
            'uid' => '04A1B2C3D4',
            'card_type' => 'badge',
            'label' => 'Blauwe sleutelhanger',
            'status' => 'active',
        ]);
    }

    public function test_linking_card_validates_poef_and_uid(): void
    {
        Livewire::test(KoppelPoef::class)
            ->dispatch('open-koppel-poef')
            ->call('linkCard')
            ->assertSee('Selecteer eerst een lid (poef)')
            ->call('selectPoef', $this->poef1->id)
            ->set('cardUid', '')
            ->call('linkCard')
            ->assertSee('Scan een kaart/badge of typ het serienummer (UID) in');
    }

    public function test_card_scanned_event_fills_uid_field_when_modal_is_open(): void
    {
        Livewire::test(KoppelPoef::class)
            ->dispatch('open-koppel-poef')
            ->dispatch('card-scanned', cardUid: 'SCANNED-UID-999')
            ->assertSet('cardUid', 'SCANNED-UID-999')
            ->assertDispatched('nfc-beep');
    }

    public function test_card_scanned_event_ignored_when_modal_is_closed(): void
    {
        Livewire::test(KoppelPoef::class)
            ->dispatch('card-scanned', cardUid: 'SCANNED-UID-999')
            ->assertSet('cardUid', '');
    }

    public function test_multiple_cards_can_be_linked_to_same_poef(): void
    {
        Livewire::test(KoppelPoef::class)
            ->dispatch('open-koppel-poef')
            ->call('selectPoef', $this->poef1->id)
            ->set('cardUid', 'BADGE-1')
            ->set('cardType', 'badge')
            ->call('linkCard')
            ->call('selectPoef', $this->poef1->id)
            ->set('cardUid', 'BANKKAART-1')
            ->set('cardType', 'bankkaart')
            ->call('linkCard');

        $this->assertDatabaseHas('cards', ['poef_id' => $this->poef1->id, 'uid' => 'BADGE-1']);
        $this->assertDatabaseHas('cards', ['poef_id' => $this->poef1->id, 'uid' => 'BANKKAART-1']);
    }

    public function test_card_already_linked_to_another_poef_cannot_be_overwritten(): void
    {
        // First link card to poef1
        Card::create([
            'poef_id' => $this->poef1->id,
            'uid' => 'SECURE-BADGE-007',
            'card_type' => 'badge',
            'status' => 'active',
        ]);

        // Attempt to link same card to poef2
        Livewire::test(KoppelPoef::class)
            ->dispatch('open-koppel-poef')
            ->call('selectPoef', $this->poef2->id)
            ->set('cardUid', 'SECURE-BADGE-007')
            ->set('cardType', 'nfc')
            ->call('linkCard')
            ->assertSee('Beveiliging: Deze')
            ->assertSee('Charlotte Van Houtte')
            ->assertSee('Overschrijven naar een ander lid is niet toegestaan')
            ->assertDispatched('nfc-beep', success: false);

        // Verify it was NOT overwritten in database
        $this->assertDatabaseHas('cards', [
            'uid' => 'SECURE-BADGE-007',
            'poef_id' => $this->poef1->id,
        ]);
        $this->assertDatabaseMissing('cards', [
            'uid' => 'SECURE-BADGE-007',
            'poef_id' => $this->poef2->id,
        ]);
    }

    public function test_card_can_be_unlinked(): void
    {
        $card = Card::create([
            'poef_id' => $this->poef1->id,
            'uid' => 'CARD-TO-DELETE',
            'card_type' => 'kaart',
            'status' => 'active',
        ]);

        Livewire::test(KoppelPoef::class)
            ->dispatch('open-koppel-poef')
            ->call('unlinkCard', $card->id)
            ->assertSee('Koppeling voor UID CARD-TO-DELETE verwijderd');

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }
}
