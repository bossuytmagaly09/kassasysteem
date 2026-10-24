# Kassasysteem

Evenementen kassasysteem gebouwd met Laravel 12, Livewire 3 en Tailwind CSS.

## Functionaliteiten

- **Event Management**: Beheer van evenementen, voorraad per eenheid (fles, blik, vat, portie), inkoopprijzen en verkoopprijzen per consumptie.
- **POS Kassa**: Snelle touch-vriendelijke interface voor het aanslaan van dranken en eten.
- **Drankkaarten & Vakjes**:
  - Verkoop van drankkaarten via Cash of QR-code.
  - Direct consumpties afrekenen als doorgestreepte vakjes op de aangekochte drankkaart.
  - Instelbare prijs per drankkaart en waarde per vakje per evenement.
- **NFC / RFID Scanner & Koppel Poef**:
  - Persistent gekoppelde Web Serial & USB HID scanner met aanwezigheidsdetectie en 15s cooldown.
  - Ledenpoefs synchroniseren en koppelen aan badges, bankkaarten of RFID-tags.
- **Statistieken & Rapporten**: Realtime omzet, voorraadstatus en transactieoverzicht.

## Installatie & Setup

```bash
composer install
npm install
npm run build
php artisan migrate
php artisan serve
```
