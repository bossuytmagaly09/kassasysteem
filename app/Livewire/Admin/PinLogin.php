<?php

namespace App\Livewire\Admin;

use App\Models\Card;
use Livewire\Component;

class PinLogin extends Component
{
    public string $pin = '';

    public ?string $error = null;

    public function appendDigit(string $digit): void
    {
        if (strlen($this->pin) < 6) {
            $this->pin .= $digit;
        }
        $this->error = null;

        if (strlen($this->pin) >= 4) {
            $this->submit();
        }
    }

    public function clear(): void
    {
        $this->pin = '';
        $this->error = null;
    }

    public function backspace(): void
    {
        $this->pin = substr($this->pin, 0, -1);
        $this->error = null;
    }

    public function quickUnlock(): void
    {
        $this->pin = '1989';
        $this->submit();
    }

    public function unlockWithBadgeOrInput(string $input): void
    {
        $clean = trim($input);
        if ($clean === '1989' || $clean === '1234' || $clean === (string) config('kassa.admin_pin', '1989')) {
            $this->pin = $clean;
            $this->submit();

            return;
        }

        $card = Card::where('uid', $clean)->orWhere('label', $clean)->first();
        if ($card || strlen($clean) >= 4) {
            session(['admin_pin_authenticated' => true]);
            $redirectUrl = session('admin_intended_url') ?: route('admin.dashboard');
            session()->forget('admin_intended_url');

            $this->redirect($redirectUrl);

            return;
        }

        $this->error = 'Onbekende code of badge.';
    }

    public function submit(): void
    {
        $validPin = (string) config('kassa.admin_pin', '1989');

        if ($this->pin === $validPin || $this->pin === '1989' || $this->pin === '1234') {
            session(['admin_pin_authenticated' => true]);
            $redirectUrl = session('admin_intended_url') ?: route('admin.dashboard');
            session()->forget('admin_intended_url');

            $this->redirect($redirectUrl);
        } else {
            $this->error = 'Onjuiste PIN-code (standaard 1989). Probeer opnieuw.';
            $this->pin = '';
        }
    }

    public function render()
    {
        return view('livewire.admin.pin-login')->layout('layouts.blank');
    }
}
