<?php

namespace App\Livewire\Admin;

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

        if (strlen($this->pin) === strlen((string) config('kassa.admin_pin', '1234'))) {
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

    public function submit(): void
    {
        $validPin = (string) config('kassa.admin_pin', '1234');

        if ($this->pin === $validPin) {
            session(['admin_pin_authenticated' => true]);
            $redirectUrl = session('admin_intended_url') ?: route('admin.dashboard');
            session()->forget('admin_intended_url');

            $this->redirect($redirectUrl);
        } else {
            $this->error = 'Onjuiste PIN-code. Probeer opnieuw.';
            $this->pin = '';
        }
    }

    public function render()
    {
        return view('livewire.admin.pin-login')->layout('layouts.guest');
    }
}
