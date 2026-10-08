/**
 * DashboardNotifSound - Web Audio API Notification Synthesizer (Tugas 10B)
 *
 * Menggunakan Web Audio API (oscillator + gain envelope) murni tanpa file audio.
 * Volume maksimum gain 0.15.
 *
 * Level:
 * - 'urgent'  (Kedaluwarsa atau H-1 Bulan): dua nada turun (~880 Hz lalu ~660 Hz) diulang 2x, ~0.7 detik.
 * - 'warning' (H-3 Bulan): satu nada ~660 Hz, ~0.3 detik.
 * - 'info'    (H-6 Bulan): satu nada lembut ~523 Hz, ~0.2 detik.
 */
class DashboardNotifSound {
    constructor() {
        this.audioCtx = null;
        this.enabled = true;
        this.pendingPlayLevel = null;
        this.storageKey = 'faskes_dashboard_sound';

        // Baca preferensi dari localStorage (default true)
        try {
            const saved = localStorage.getItem(this.storageKey);
            if (saved !== null) {
                this.enabled = saved === '1' || saved === 'true';
            }
        } catch (e) {
            console.warn('Gagal membaca localStorage faskes_dashboard_sound:', e);
        }

        // Pasang listener user gesture pertama untuk unlock AudioContext & autoplay policy
        this.initUnlockListener();
    }

    getAudioContext() {
        if (!this.audioCtx) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                this.audioCtx = new AudioContextClass();
            }
        }
        return this.audioCtx;
    }

    isEnabled() {
        return this.enabled;
    }

    setEnabled(bool) {
        this.enabled = Boolean(bool);
        try {
            localStorage.setItem(this.storageKey, this.enabled ? '1' : '0');
        } catch (e) {
            console.warn('Gagal menyimpan localStorage faskes_dashboard_sound:', e);
        }
    }

    unlock() {
        const ctx = this.getAudioContext();
        if (!ctx) return Promise.resolve(false);

        if (ctx.state === 'suspended') {
            return ctx.resume().then(() => {
                if (this.pendingPlayLevel && this.enabled) {
                    const lvl = this.pendingPlayLevel;
                    this.pendingPlayLevel = null;
                    this.play(lvl);
                }
                return true;
            }).catch(err => {
                console.warn('Gagal unlock AudioContext:', err);
                return false;
            });
        }

        if (this.pendingPlayLevel && this.enabled) {
            const lvl = this.pendingPlayLevel;
            this.pendingPlayLevel = null;
            this.play(lvl);
        }

        return Promise.resolve(true);
    }

    initUnlockListener() {
        const unlockHandler = () => {
            this.unlock();
            ['click', 'keydown', 'touchstart'].forEach(evt => {
                window.removeEventListener(evt, unlockHandler, { capture: true });
            });
        };

        ['click', 'keydown', 'touchstart'].forEach(evt => {
            window.addEventListener(evt, unlockHandler, { capture: true, once: true });
        });
    }

    play(level = 'info') {
        if (!this.enabled) return;

        const ctx = this.getAudioContext();
        if (!ctx) return;

        // Jika audio context terblokir oleh autoplay policy browser
        if (ctx.state === 'suspended') {
            this.pendingPlayLevel = level;
            // Tampilkan custom event agar UI dapat memunculkan banner/toast "Aktifkan suara"
            window.dispatchEvent(new CustomEvent('dashboard-sound-blocked', { detail: { level } }));
            return;
        }

        const now = ctx.currentTime;
        const maxGain = 0.15; // Batas volume aman

        if (level === 'urgent') {
            // Urgent (Kedaluwarsa / H-1 Bulan): dua nada turun (880 Hz -> 660 Hz) diulang dua kali, ~0.7s
            this.playTone(880, now, 0.12, maxGain);
            this.playTone(660, now + 0.14, 0.15, maxGain);

            this.playTone(880, now + 0.35, 0.12, maxGain);
            this.playTone(660, now + 0.49, 0.20, maxGain);
        } else if (level === 'warning') {
            // Warning (H-3 Bulan): satu nada 660 Hz, ~0.3s
            this.playTone(660, now, 0.28, maxGain * 0.9);
        } else {
            // Info (H-6 Bulan): satu nada lembut 523 Hz (C5), ~0.2s
            this.playTone(523.25, now, 0.20, maxGain * 0.7);
        }
    }

    playTone(frequency, startTime, duration, peakGain) {
        const ctx = this.getAudioContext();
        if (!ctx) return;

        const osc = ctx.createOscillator();
        const gainNode = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(frequency, startTime);

        // Envelope: attack cepat, decay halus
        gainNode.gain.setValueAtTime(0.0001, startTime);
        gainNode.gain.exponentialRampToValueAtTime(peakGain, startTime + 0.02);
        gainNode.gain.exponentialRampToValueAtTime(0.0001, startTime + duration);

        osc.connect(gainNode);
        gainNode.connect(ctx.destination);

        osc.start(startTime);
        osc.stop(startTime + duration + 0.05);
    }
}

// Inisialisasi instance global singleton
window.dashboardNotifSound = new DashboardNotifSound();
