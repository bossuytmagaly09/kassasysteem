// NFC-lezer via Web Serial (Chrome/Edge). Gebruik:
//
//   import { NfcReader } from './nfc-reader.js';
//   const nfc = new NfcReader();
//   nfc.addEventListener('card',   e => console.log(e.detail.uid));
//   nfc.addEventListener('status', e => console.log(e.detail.state)); // connected | disconnected | ready | error
//   button.onclick = () => nfc.connect();   // eerste keer: gebruiker kiest de COM-poort
//   nfc.autoConnect();                      // daarna: automatisch bij het laden van de pagina

export class NfcReader extends EventTarget {
  constructor({ baudRate = 115200, filters = null, scanCooldown = 1200, holdingTimeout = 1200 } = {}) {
    super();
    this.baudRate = baudRate;
    this.filters = filters; // bv. [{ usbVendorId: 0x1a86 }] voor CH340 Nano klonen
    this.scanCooldown = scanCooldown; // Snelle 1.2s cooldown voor vloeiend scannen
    this.holdingTimeout = holdingTimeout; // 1.2s aanwezigheidsdetectie op scanner
    this._lastHandledUid = null;
    this._lastHandledTime = 0;
    this._lastSeenTime = 0;
    this._lastBeepTime = 0;
    this.port = null;
    this.writer = null;
    this.reader = null;
    this._wantConnected = true;
    this._onDisconnect = (e) => { if (e.target === this.port) this._lost(); };
    this._onConnect = (e) => { this.autoConnect(); };
    if ('serial' in navigator) {
      navigator.serial.addEventListener('disconnect', this._onDisconnect);
      navigator.serial.addEventListener('connect', this._onConnect);
    }
  }

  static get supported() { return 'serial' in navigator; }

  _emit(type, detail) { this.dispatchEvent(new CustomEvent(type, { detail })); }

  resetCooldown() {
    this._lastHandledUid = null;
    this._lastHandledTime = 0;
    this._lastSeenTime = 0;
  }

  // 2-weg communicatie naar Arduino Nano (stuurt commando's met \n af)
  async send(command) {
    if (!this.writer) return false;
    try {
      const text = String(command).endsWith('\n') ? String(command) : `${command}\n`;
      await this.writer.write(text);
      return true;
    } catch (err) {
      console.warn('Serial send error:', err);
      return false;
    }
  }

  // Moet vanuit een klik (user gesture) aangeroepen worden.
  async connect() {
    this._wantConnected = true;
    const port = await navigator.serial.requestPort(this.filters ? { filters: this.filters } : {});
    return this._open(port);
  }

  // Zonder klik: opent een eerder toegestane poort automatisch.
  async autoConnect() {
    if (!NfcReader.supported) return false;
    try {
      const ports = await navigator.serial.getPorts();
      if (!ports.length) return false;
      await this._open(ports[0]);
      return true;
    } catch (err) {
      console.warn('NFC autoConnect attempt:', err);
      return false;
    }
  }

  async disconnect() {
    this._wantConnected = false;
    try { await this.reader?.cancel(); } catch {}
    await this._close();
    this._emit('status', { state: 'disconnected' });
  }

  // 'O' = succes-piep, 'E' = fout-piep (maximaal 1 piep per 1,5 seconde)
  async beep(ok = true) {
    const now = Date.now();
    if (now - this._lastBeepTime < 1500) {
      return;
    }
    this._lastBeepTime = now;

    if (this.writer) {
      try {
        await this.writer.write(ok ? 'O' : 'E');
      } catch (err) {
        console.warn('Beep error:', err);
      }
    }
  }

  async _open(port) {
    if (this.port === port && this.reader) {
      this._emit('status', { state: 'ready' });
      return;
    }

    this.port = port;
    this._wantConnected = true;

    try {
      await port.open({ baudRate: this.baudRate });
    } catch (err) {
      // If already open, continue using it
      if (!err.message?.includes('already open')) {
        this._emit('status', { state: 'error', message: err.message });
        throw err;
      }
    }

    // Immediately mark as ready so user sees scanner is active
    this._emit('status', { state: 'ready' });

    try {
      const enc = new TextEncoderStream();
      enc.readable.pipeTo(port.writable).catch(() => {});
      this.writer = enc.writable.getWriter();
    } catch {}

    this._readLoop(port);
  }

  async _readLoop(port) {
    try {
      const dec = new TextDecoderStream();
      port.readable.pipeTo(dec.writable).catch(() => {});
      this.reader = dec.readable.getReader();
      let buf = '';
      while (true) {
        const { value, done } = await this.reader.read();
        if (done) break;
        buf += value;
        let i;
        while ((i = buf.indexOf('\n')) >= 0) {
          const line = buf.slice(0, i);
          buf = buf.slice(i + 1);
          this._handleLine(line);
        }
      }
    } catch (err) {
      if (this._wantConnected) {
        this._emit('status', { state: 'error', message: String(err) });
      }
    }
    await this._close();
    if (this._wantConnected) this._lost();
  }

  _handleCard(uid, type = 'nfc') {
    const clean = String(uid || '').replace(/[\r\n\t]/g, '').trim();
    if (!clean || clean.length < 3) return;

    const now = Date.now();

    // 1. Zelfde kaart controle
    if (this._lastHandledUid === clean) {
      const timeSinceLastSeen = now - this._lastSeenTime;
      this._lastSeenTime = now;

      // Zolang kaart vastgehouden wordt op de lezer (< 1.2s gap)
      if (timeSinceLastSeen < this.holdingTimeout) {
        return;
      }

      // Korte cooldown voor dezelfde kaart
      if (now - this._lastHandledTime < this.scanCooldown) {
        return;
      }
    }

    // 2. Snelle ruis-debounce (< 300ms)
    if (now - this._lastHandledTime < 300) {
      return;
    }

    this._lastHandledUid = clean;
    this._lastHandledTime = now;
    this._lastSeenTime = now;

    this._emit('card', { uid: clean, type });
  }

  _handleLine(line) {
    if (!line) return;
    const raw = String(line).replace(/[\r\n\t]/g, '').trim();
    if (!raw) return;

    // 1. JSON parsing (bijv. {"event":"card","uid":"04A1B2C3"})
    if (raw.startsWith('{') && raw.endsWith('}')) {
      try {
        const msg = JSON.parse(raw);
        if (msg.event === 'card' && msg.uid) {
          this._handleCard(msg.uid, msg.type ?? 'nfc');
          return;
        }
        if (msg.uid) {
          this._handleCard(msg.uid, msg.type ?? 'nfc');
          return;
        }
        if (msg.event === 'ready') {
          this._emit('status', { state: 'ready', version: msg.version });
          return;
        }
        if (msg.event === 'error') {
          this._emit('status', { state: 'error', message: msg.msg });
          return;
        }
      } catch {}
    }

    // 2. Arduino status/log meldingen (geen kaartscans)
    const upper = raw.toUpperCase();
    if (upper === 'READY' || upper === 'PONG' || upper === 'SCANNER READY' || upper.includes('MFRC522 INITIALIZED')) {
      this._emit('status', { state: 'ready' });
      return;
    }
    if (upper === 'OK' || upper === 'ERR' || upper === 'ERROR' || upper === 'ACK') {
      return;
    }

    // 3. Verwijder veelvoorkomende Arduino prefixes ("Card UID: 04 A1 B2 C3", "UID: 04:A1:B2:C3", "NFC: ...")
    let cleaned = raw.replace(/^(?:card\s*uid|uid|card|tag|nfc|id)\s*[:=\-]\s*/i, '').trim();

    // 4. Hex reeksen met spaties of dubbelepunt: "04 A1 B2 C3" of "04:A1:B2:C3" -> "04A1B2C3"
    if (/^[0-9A-Fa-f]{2}(?:[\s:\-][0-9A-Fa-f]{2}){2,15}$/.test(cleaned)) {
      const normalizedHex = cleaned.replace(/[\s:\-]/g, '').toUpperCase();
      this._handleCard(normalizedHex, 'nfc');
      return;
    }

    // 5. Zuivere alfanumerieke UID (bijv. 04A1B2C3 of getallenreeks)
    if (/^[A-Za-z0-9_\-]{4,64}$/.test(cleaned)) {
      this._handleCard(cleaned, 'raw');
      return;
    }

    // 6. Ingesloten hex patroon opsporen in een string
    const hexMatch = cleaned.match(/([0-9A-Fa-f]{2}(?:[\s:\-][0-9A-Fa-f]{2}){3,9})/);
    if (hexMatch) {
      const extracted = hexMatch[1].replace(/[\s:\-]/g, '').toUpperCase();
      this._handleCard(extracted, 'nfc');
    }
  }

  async _close() {
    try { await this.writer?.close(); } catch {}
    try { await this.reader?.cancel(); } catch {}
    try { await this.port?.close(); } catch {}
    this.writer = this.reader = null;
  }

  // USB eruit getrokken: blijf proberen opnieuw te verbinden
  _lost() {
    this._emit('status', { state: 'disconnected' });
    this._close();
    if (!this._wantConnected) return;
    const retry = async () => {
      if (!this._wantConnected) return;
      try {
        await this.autoConnect();
      } catch {
        setTimeout(retry, 2000);
      }
    };
    setTimeout(retry, 2000);
  }
}
