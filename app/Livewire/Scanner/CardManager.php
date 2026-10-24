<?php

namespace App\Livewire\Scanner;

use Livewire\Attributes\On;
use Livewire\Component;

class CardManager extends Component
{
    public $scannedCode = '';

    public $lastScannedUid = null;

    public $message = '';

    public $isSuccess = true;

    #[On('card-scanned')]
    public function processScan($uid)
    {
        $this->scannedCode = $uid;
        $this->message = '';
        if (empty($this->scannedCode)) {
            return;
        }

        // Just display what was scanned for testing purposes
        $this->lastScannedUid = $this->scannedCode;

        $this->scannedCode = '';
        $this->message = 'Kaart succesvol uitgelezen!';
        $this->isSuccess = true;

        $this->dispatch('nfc-beep', success: true);
    }

    public function resetScanner()
    {
        $this->lastScannedUid = null;
        $this->message = '';
    }

    public function render()
    {
        return view('livewire.scanner.card-manager')->layout('layouts.pos');
    }
}
