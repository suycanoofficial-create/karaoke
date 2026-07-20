/**
 * KTV LOUNGE - Live Scoring Engine
 * Pitch detection via Web Audio API autocorrelation
 */
const KTVScoring = (function () {

    const NOTE_NAMES = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    function freqToNote(freq) {
        if (!freq || freq <= 0) return null;
        var a = 440;
        var n = Math.round(12 * Math.log2(freq / a));
        var noteIndex = ((n % 12) + 12) % 12;
        var octave = Math.floor((n + 69) / 12) - 1;
        var noteName = NOTE_NAMES[noteIndex];
        var cents = 1200 * Math.log2(freq / (a * Math.pow(2, n / 12)));
        return { name: noteName + octave, index: n, cents: cents, freq: freq };
    }

    function autocorrelate(buffer, sampleRate) {
        var size = buffer.length;
        var maxSamples = Math.floor(size / 2);
        var bestOffset = -1;
        var bestCorrelation = 0;
        var rms = 0;
        for (var i = 0; i < size; i++) {
            rms += buffer[i] * buffer[i];
        }
        rms = Math.sqrt(rms / size);
        if (rms < 0.01) return null;

        for (var offset = 0; offset < maxSamples; offset++) {
            var correlation = 0;
            for (var i = 0; i < maxSamples; i++) {
                correlation += Math.abs((buffer[i]) - (buffer[i + offset]));
            }
            correlation = 1 - (correlation / maxSamples);
            if (correlation > bestCorrelation && correlation > 0.2) {
                bestCorrelation = correlation;
                bestOffset = offset;
            }
        }

        if (bestOffset === -1) return null;

        var freq = sampleRate / bestOffset;
        if (freq < 70 || freq > 1200) return null;

        return freq;
    }

    function Scorer(baseline) {
        this.baseline = baseline !== undefined ? baseline : 65;
        this.reset();
    }

    Scorer.prototype._map = function (raw) {
        if (raw >= 100) return 100;
        return Math.round(this.baseline + raw * (100 - this.baseline) / 100);
    };

    Scorer.prototype.reset = function () {
        this.totalScore = 0;
        this.samples = 0;
        this.activeSamples = 0;
        this.scoreHistory = [];
        this.maxScore = 0;
        this.currentScore = 0;
        this.smoothedScore = 0;
    };

    Scorer.prototype.addSample = function (pitch) {
        this.samples++;
        var rawScore = 0;
        if (pitch) {
            var note = freqToNote(pitch);
            if (note) {
                var cents = Math.abs(note.cents);
                var accuracy = cents < 100 ? Math.max(0, 1 - cents / 100) : 0;
                rawScore = Math.round(accuracy * 100);
                this.activeSamples++;
            }
        }
        this.scoreHistory.push(rawScore);
        if (this.scoreHistory.length > 100) this.scoreHistory.shift();
        var sum = 0;
        for (var i = 0; i < this.scoreHistory.length; i++) {
            sum += this.scoreHistory[i];
        }
        this.currentScore = Math.round(sum / this.scoreHistory.length);
        if (this.currentScore > this.maxScore) this.maxScore = this.currentScore;
        this.smoothedScore = this.smoothedScore * 0.7 + this.currentScore * 0.3;
    };

    Scorer.prototype.getAverage = function () {
        if (this.samples === 0) return this.baseline;
        return this._map(this.smoothedScore);
    };

    Scorer.prototype.getCurrent = function () {
        if (this.samples === 0) return this.baseline;
        return this._map(this.smoothedScore);
    };

    function PitchDetector() {
        this.audioContext = null;
        this.analyser = null;
        this.micStream = null;
        this.dataArray = null;
        this.isRunning = false;
        this.onPitch = null;
        this.rafId = null;
        this.sampleRate = 44100;
    }

    PitchDetector.prototype.start = async function () {
        if (this.isRunning) return;
        try {
            this.micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.sampleRate = this.audioContext.sampleRate;
            var source = this.audioContext.createMediaStreamSource(this.micStream);
            this.analyser = this.audioContext.createAnalyser();
            this.analyser.fftSize = 2048;
            this.analyser.smoothingTimeConstant = 0.8;
            source.connect(this.analyser);
            this.dataArray = new Float32Array(this.analyser.fftSize);
            this.isRunning = true;
            this._loop();
            return true;
        } catch (e) {
            return false;
        }
    };

    PitchDetector.prototype._loop = function () {
        if (!this.isRunning) return;
        this.analyser.getFloatTimeDomainData(this.dataArray);
        var pitch = autocorrelate(this.dataArray, this.sampleRate);
        if (this.onPitch) this.onPitch(pitch);
        this.rafId = requestAnimationFrame(this._loop.bind(this));
    };

    PitchDetector.prototype.stop = function () {
        this.isRunning = false;
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
        if (this.micStream) {
            this.micStream.getTracks().forEach(function (t) { t.stop(); });
            this.micStream = null;
        }
        if (this.audioContext) {
            this.audioContext.close();
            this.audioContext = null;
        }
        this.analyser = null;
        this.dataArray = null;
    };

    function Engine() {
        this.detector = new PitchDetector();
        this.scorer = new Scorer();
        this.isActive = false;
        this.lastPitch = null;
        this.lastNote = null;
        this.silenceFrames = 0;
        this.onScore = null;
        this._boundOnPitch = this._onPitch.bind(this);
    }

    Engine.prototype._onPitch = function (pitch) {
        this.lastPitch = pitch;
        if (pitch) {
            this.silenceFrames = 0;
            var note = freqToNote(pitch);
            if (note) {
                this.lastNote = note.name;
            }
        } else {
            this.silenceFrames++;
            if (this.silenceFrames > 10) {
                this.lastNote = null;
            }
        }
        this.scorer.addSample(pitch);
        if (this.onScore) {
            this.onScore(this.scorer.getCurrent());
        }
    };

    Engine.prototype.start = async function () {
        if (this.isActive) return true;
        var ok = await this.detector.start();
        if (!ok) return false;
        this.isActive = true;
        this.scorer.reset();
        this.detector.onPitch = this._boundOnPitch;
        return true;
    };

    Engine.prototype.stop = function () {
        if (!this.isActive) return 65;
        this.detector.stop();
        this.isActive = false;
        return this.scorer.getAverage();
    };

    Engine.prototype.getScore = function () {
        return this.scorer.getAverage();
    };

    Engine.prototype.isRunning = function () {
        return this.isActive;
    };

    Engine.prototype.setBaseline = function (val) {
        this.scorer.baseline = val;
    };

    return {
        Engine: Engine,
        freqToNote: freqToNote,
        NOTE_NAMES: NOTE_NAMES
    };

})();
