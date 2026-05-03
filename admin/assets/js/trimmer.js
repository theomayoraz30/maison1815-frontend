/**
 * trimmer.js — VideoTrimmer
 *
 * Self-contained video clip trimmer for the Maison 1815 admin dashboard.
 * Renders its own UI inside a given container element.
 * Vanilla JS only, no external dependencies.
 *
 * Usage:
 *   import { VideoTrimmer } from './trimmer.js';
 *   const trimmer = new VideoTrimmer(videoEl, containerEl, { maxDuration: 15, onSave: fn });
 *   trimmer.loadVideo('/path/to/video.mp4');
 */

export class VideoTrimmer {
  /**
   * @param {HTMLVideoElement} videoElement   — the source <video> (may be an existing element
   *                                            or a fresh one; its src will be managed via loadVideo)
   * @param {HTMLElement}      containerElement — wrapper where the trimmer UI is injected
   * @param {object}           options
   * @param {number}           [options.maxDuration=15]    — max selectable clip length in seconds
   * @param {number}           [options.defaultDuration=10] — default clip length on load
   * @param {Function|null}    [options.onSave=null]       — called with { start, end } on save
   */
  constructor(videoElement, containerElement, options = {}) {
    this._video     = videoElement;
    this._container = containerElement;

    this._maxDuration     = typeof options.maxDuration     === 'number' ? options.maxDuration     : 15;
    this._defaultDuration = typeof options.defaultDuration === 'number' ? options.defaultDuration : 10;
    this._saveCallback    = typeof options.onSave          === 'function' ? options.onSave        : null;

    // State
    this.startTime     = 0;
    this.endTime       = this._defaultDuration;
    this.videoDuration = 0;

    this._dragging        = null; // 'start' | 'end' | null
    this._previewing      = false;
    this._pendingTimestamps = null; // { start, end } to apply after metadata loads
    this._metadataReady   = false;

    // Bound handlers kept as references for later removeEventListener
    this._onMouseMove  = this._handleDragMove.bind(this);
    this._onMouseUp    = this._handleDragEnd.bind(this);
    this._onTouchMove  = this._handleTouchMove.bind(this);
    this._onTouchEnd   = this._handleTouchEnd.bind(this);
    this._onTimeUpdate = this._handleTimeUpdate.bind(this);

    this._buildUI();
    this._bindVideoEvents();
  }

  // ─────────────────────────────────────────────
  // Public API
  // ─────────────────────────────────────────────

  /**
   * Load a video source into the trimmer.
   * Shows the wrapper and initialises handles once metadata is ready.
   * @param {string} src
   */
  loadVideo(src) {
    this._video.src = src;
    this._video.load();
    this._wrapper.style.display = 'block';
    this._metadataReady = false;
  }

  /**
   * Returns the current trim timestamps.
   * @returns {{ start: number, end: number }}
   */
  getTimestamps() {
    return { start: this.startTime, end: this.endTime };
  }

  /**
   * Pre-set handles — waits for loadedmetadata if video isn't ready yet.
   * @param {number} start
   * @param {number} end
   */
  setTimestamps(start, end) {
    if (!this._metadataReady) {
      this._pendingTimestamps = { start, end };
      return;
    }
    this._applyTimestamps(start, end);
  }

  /**
   * Register (or replace) the save callback.
   * @param {Function} callback  — receives { start, end }
   */
  onSave(callback) {
    this._saveCallback = callback;
  }

  /**
   * Remove all event listeners and wipe the rendered UI.
   */
  destroy() {
    document.removeEventListener('mousemove', this._onMouseMove);
    document.removeEventListener('mouseup',   this._onMouseUp);
    document.removeEventListener('touchmove', this._onTouchMove);
    document.removeEventListener('touchend',  this._onTouchEnd);

    this._video.removeEventListener('timeupdate',     this._onTimeUpdate);
    this._video.removeEventListener('loadedmetadata', this._onMetadata);

    // Stop playback if active
    if (!this._video.paused) this._video.pause();

    // Clear injected markup
    if (this._wrapper && this._wrapper.parentNode) {
      this._wrapper.parentNode.removeChild(this._wrapper);
    }
  }

  // ─────────────────────────────────────────────
  // UI construction
  // ─────────────────────────────────────────────

  _buildUI() {
    // Outer wrapper — hidden until loadVideo() is called
    this._wrapper = document.createElement('div');
    this._wrapper.className = 'trimmer-wrapper';
    Object.assign(this._wrapper.style, {
      background   : '#111',
      border       : '1px solid #2a2a2a',
      borderRadius : '4px',
      padding      : '16px',
      marginTop    : '16px',
      display      : 'none',
      fontFamily   : "'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif",
    });

    // ── Video preview ──
    if (!this._video) {
      this._video = document.createElement('video');
    }
    this._videoEl = this._video;
    Object.assign(this._videoEl.style, {
      width     : '100%',
      maxHeight : '360px',
      background: '#000',
      display   : 'block',
    });
    this._videoEl.className    = 'trim-video';
    this._videoEl.playsInline  = true;
    this._videoEl.controls     = false;
    this._wrapper.appendChild(this._videoEl);

    // ── Timeline ──
    this._timeline = document.createElement('div');
    this._timeline.className = 'trim-timeline';
    Object.assign(this._timeline.style, {
      position    : 'relative',
      height      : '48px',
      background  : '#1a1a1a',
      borderRadius: '3px',
      marginTop   : '12px',
      cursor      : 'pointer',
      userSelect  : 'none',
    });

    // Selected range highlight
    this._range = document.createElement('div');
    this._range.className = 'trim-range';
    Object.assign(this._range.style, {
      position    : 'absolute',
      top         : '0',
      bottom      : '0',
      background  : 'rgba(255,85,0,0.2)',
      borderTop   : '2px solid #FF5500',
      borderBottom: '2px solid #FF5500',
    });
    this._timeline.appendChild(this._range);

    // Playhead
    this._playhead = document.createElement('div');
    this._playhead.className = 'trim-playhead';
    Object.assign(this._playhead.style, {
      position      : 'absolute',
      top           : '0',
      bottom        : '0',
      width         : '2px',
      background    : 'rgba(255,255,255,0.5)',
      pointerEvents : 'none',
      left          : '0',
    });
    this._timeline.appendChild(this._playhead);

    // Start handle
    this._handleStart = this._createHandle('start');
    this._timeline.appendChild(this._handleStart);

    // End handle
    this._handleEnd = this._createHandle('end');
    this._timeline.appendChild(this._handleEnd);

    this._wrapper.appendChild(this._timeline);

    // ── Time display + button row ──
    const controlRow = document.createElement('div');
    Object.assign(controlRow.style, {
      display        : 'flex',
      justifyContent : 'space-between',
      alignItems     : 'center',
      marginTop      : '8px',
    });

    this._timeDisplay = document.createElement('span');
    this._timeDisplay.className = 'trim-time-display';
    Object.assign(this._timeDisplay.style, {
      fontSize  : '12px',
      color     : '#888',
      fontFamily: 'monospace',
    });
    this._timeDisplay.textContent = '00:00.000 → 00:10.000 (10.0s)';
    controlRow.appendChild(this._timeDisplay);

    // Button cluster
    const btnCluster = document.createElement('div');
    Object.assign(btnCluster.style, {
      display    : 'flex',
      gap        : '8px',
      alignItems : 'center',
    });

    this._stepBackBtn = this._createIconButton('◀', 'Step back 1 frame');
    this._previewBtn  = this._createPreviewButton();
    this._stepFwdBtn  = this._createIconButton('▶', 'Step forward 1 frame');

    btnCluster.appendChild(this._stepBackBtn);
    btnCluster.appendChild(this._previewBtn);
    btnCluster.appendChild(this._stepFwdBtn);
    controlRow.appendChild(btnCluster);
    this._wrapper.appendChild(controlRow);

    // ── Manual inputs row ──
    const inputRow = document.createElement('div');
    Object.assign(inputRow.style, {
      display  : 'flex',
      gap      : '12px',
      marginTop: '10px',
    });

    this._startInput = this._createNumberInput('Start (s)', 'trim-start-input');
    this._endInput   = this._createNumberInput('End (s)',   'trim-end-input');

    const saveWrapper = document.createElement('div');
    Object.assign(saveWrapper.style, { display: 'flex', alignItems: 'flex-end' });

    this._saveBtn = document.createElement('button');
    this._saveBtn.type        = 'button';
    this._saveBtn.className   = 'trim-save-btn';
    this._saveBtn.textContent = 'Save Clip';
    Object.assign(this._saveBtn.style, {
      background    : '#FF5500',
      border        : 'none',
      color         : '#fff',
      padding       : '8px 20px',
      borderRadius  : '3px',
      cursor        : 'pointer',
      fontSize      : '12px',
      fontWeight    : '600',
      letterSpacing : '0.05em',
      textTransform : 'uppercase',
      height        : '34px',
      fontFamily    : 'inherit',
    });
    saveWrapper.appendChild(this._saveBtn);

    inputRow.appendChild(this._startInput.label);
    inputRow.appendChild(this._endInput.label);
    inputRow.appendChild(saveWrapper);
    this._wrapper.appendChild(inputRow);

    this._container.appendChild(this._wrapper);

    // ── Bind UI events ──
    this._bindUIEvents();
  }

  /**
   * Create one of the two orange drag handles.
   * @param {'start'|'end'} which
   * @returns {HTMLElement}
   */
  _createHandle(which) {
    const handle = document.createElement('div');
    handle.className = `trim-handle trim-handle-${which}`;
    Object.assign(handle.style, {
      position     : 'absolute',
      top          : '0',
      bottom       : '0',
      width        : '12px',
      background   : '#FF5500',
      cursor       : 'ew-resize',
      borderRadius : which === 'start' ? '2px 0 0 2px' : '0 2px 2px 0',
      display      : 'flex',
      alignItems   : 'center',
      justifyContent: 'center',
      zIndex       : '2',
    });

    const grip = document.createElement('div');
    Object.assign(grip.style, {
      width       : '2px',
      height      : '16px',
      background  : 'rgba(255,255,255,0.6)',
      borderRadius: '1px',
      pointerEvents: 'none',
    });
    handle.appendChild(grip);
    return handle;
  }

  /**
   * Create a small square icon button (frame step).
   */
  _createIconButton(symbol, title) {
    const btn = document.createElement('button');
    btn.type      = 'button';
    btn.title     = title;
    btn.innerHTML = symbol;
    Object.assign(btn.style, {
      background  : '#1a1a1a',
      border      : '1px solid #2a2a2a',
      color       : '#fff',
      width       : '28px',
      height      : '28px',
      borderRadius: '3px',
      cursor      : 'pointer',
      fontSize    : '14px',
      fontFamily  : 'inherit',
      display     : 'flex',
      alignItems  : 'center',
      justifyContent: 'center',
      padding     : '0',
    });
    return btn;
  }

  /**
   * Create the orange PREVIEW / STOP toggle button.
   */
  _createPreviewButton() {
    const btn = document.createElement('button');
    btn.type        = 'button';
    btn.className   = 'trim-preview-btn';
    btn.textContent = '▶ PREVIEW';
    Object.assign(btn.style, {
      background   : '#FF5500',
      border       : 'none',
      color        : '#fff',
      padding      : '6px 14px',
      borderRadius : '3px',
      cursor       : 'pointer',
      fontSize     : '12px',
      fontWeight   : '600',
      letterSpacing: '0.05em',
      fontFamily   : 'inherit',
    });
    return btn;
  }

  /**
   * Create a labelled number input, returning both label wrapper and the input itself.
   * @param {string} labelText
   * @param {string} className
   * @returns {{ label: HTMLElement, input: HTMLInputElement }}
   */
  _createNumberInput(labelText, className) {
    const labelEl = document.createElement('label');
    Object.assign(labelEl.style, {
      flex         : '1',
      fontSize     : '12px',
      color        : '#888',
      textTransform: 'uppercase',
      letterSpacing: '0.05em',
      fontFamily   : 'inherit',
    });
    labelEl.textContent = labelText;

    const input = document.createElement('input');
    input.type      = 'number';
    input.className = className;
    input.step      = '0.001';
    input.min       = '0';
    Object.assign(input.style, {
      display     : 'block',
      width       : '100%',
      marginTop   : '4px',
      background  : '#111',
      border      : '1px solid #2a2a2a',
      color       : '#fff',
      padding     : '6px 10px',
      borderRadius: '3px',
      fontSize    : '13px',
      fontFamily  : 'inherit',
      boxSizing   : 'border-box',
    });
    labelEl.appendChild(input);
    return { label: labelEl, input };
  }

  // ─────────────────────────────────────────────
  // Event binding
  // ─────────────────────────────────────────────

  _bindVideoEvents() {
    this._onMetadata = () => {
      this._metadataReady  = true;
      this.videoDuration   = this._videoEl.duration;

      // Apply any pending setTimestamps call
      if (this._pendingTimestamps) {
        const { start, end } = this._pendingTimestamps;
        this._pendingTimestamps = null;
        this._applyTimestamps(start, end);
      } else {
        // Default: [0, min(defaultDuration, duration)]
        this._applyTimestamps(0, Math.min(this._defaultDuration, this.videoDuration));
      }
    };

    this._videoEl.addEventListener('loadedmetadata', this._onMetadata);
    this._videoEl.addEventListener('timeupdate',     this._onTimeUpdate);
  }

  _bindUIEvents() {
    // ── Handle drag — mouse ──
    this._handleStart.addEventListener('mousedown', (e) => {
      e.preventDefault();
      this._dragging = 'start';
      document.addEventListener('mousemove', this._onMouseMove);
      document.addEventListener('mouseup',   this._onMouseUp);
    });

    this._handleEnd.addEventListener('mousedown', (e) => {
      e.preventDefault();
      this._dragging = 'end';
      document.addEventListener('mousemove', this._onMouseMove);
      document.addEventListener('mouseup',   this._onMouseUp);
    });

    // ── Handle drag — touch ──
    this._handleStart.addEventListener('touchstart', (e) => {
      e.preventDefault();
      this._dragging = 'start';
      document.addEventListener('touchmove', this._onTouchMove, { passive: false });
      document.addEventListener('touchend',  this._onTouchEnd);
    }, { passive: false });

    this._handleEnd.addEventListener('touchstart', (e) => {
      e.preventDefault();
      this._dragging = 'end';
      document.addEventListener('touchmove', this._onTouchMove, { passive: false });
      document.addEventListener('touchend',  this._onTouchEnd);
    }, { passive: false });

    // ── Click on timeline to seek ──
    this._timeline.addEventListener('click', (e) => {
      if (!this.videoDuration) return;
      // Don't seek when a handle was just dragged
      if (this._dragging) return;
      const pct  = this._clientXToPercent(e.clientX);
      const time = this._percentToTime(pct);
      this._videoEl.currentTime = Math.max(0, Math.min(this.videoDuration, time));
    });

    // ── Frame step ──
    this._stepBackBtn.addEventListener('click', () => {
      this._videoEl.currentTime = Math.max(0, this._videoEl.currentTime - (1 / 30));
    });

    this._stepFwdBtn.addEventListener('click', () => {
      if (!this.videoDuration) return;
      this._videoEl.currentTime = Math.min(this.videoDuration, this._videoEl.currentTime + (1 / 30));
    });

    // ── Preview toggle ──
    this._previewBtn.addEventListener('click', () => {
      if (!this._previewing) {
        this._startPreview();
      } else {
        this._stopPreview();
      }
    });

    // Stop preview automatically when video ends or is paused externally
    this._videoEl.addEventListener('pause', () => {
      if (this._previewing) this._stopPreview();
    });

    this._videoEl.addEventListener('ended', () => {
      if (this._previewing) this._stopPreview();
    });

    // ── Manual inputs ──
    this._startInput.input.addEventListener('change', () => {
      if (!this.videoDuration) return;
      let val = parseFloat(this._startInput.input.value);
      if (isNaN(val)) { val = 0; }
      val = Math.max(0, Math.min(val, this.videoDuration - 0.1));
      this.startTime = val;
      // Re-clamp end
      const maxEnd = Math.min(this.startTime + this._maxDuration, this.videoDuration);
      if (this.endTime <= this.startTime + 0.1 || this.endTime > maxEnd) {
        this.endTime = Math.min(this.startTime + this._defaultDuration, maxEnd);
      }
      this._updateUI();
    });

    this._endInput.input.addEventListener('change', () => {
      if (!this.videoDuration) return;
      let val = parseFloat(this._endInput.input.value);
      if (isNaN(val)) { val = this.startTime + this._defaultDuration; }
      const maxEnd = Math.min(this.startTime + this._maxDuration, this.videoDuration);
      val = Math.max(this.startTime + 0.1, Math.min(val, maxEnd));
      this.endTime = val;
      this._updateUI();
    });

    // ── Save ──
    this._saveBtn.addEventListener('click', () => {
      this._executeSave();
    });
  }

  // ─────────────────────────────────────────────
  // Drag logic
  // ─────────────────────────────────────────────

  _handleDragMove(e) {
    if (!this._dragging || !this.videoDuration) return;
    const pct  = this._clientXToPercent(e.clientX);
    const time = this._percentToTime(pct);
    this._applyDrag(time);
  }

  _handleDragEnd() {
    this._dragging = null;
    document.removeEventListener('mousemove', this._onMouseMove);
    document.removeEventListener('mouseup',   this._onMouseUp);
  }

  _handleTouchMove(e) {
    if (!this._dragging || !this.videoDuration) return;
    e.preventDefault();
    const touch = e.touches[0];
    const pct   = this._clientXToPercent(touch.clientX);
    const time  = this._percentToTime(pct);
    this._applyDrag(time);
  }

  _handleTouchEnd() {
    this._dragging = null;
    document.removeEventListener('touchmove', this._onTouchMove);
    document.removeEventListener('touchend',  this._onTouchEnd);
  }

  /**
   * Apply a dragged time value to whichever handle is active.
   * @param {number} time — raw time from cursor position
   */
  _applyDrag(time) {
    if (this._dragging === 'start') {
      // Clamp: [0, endTime - 0.1]
      this.startTime = Math.max(0, Math.min(time, this.endTime - 0.1));
    } else if (this._dragging === 'end') {
      // Clamp: [startTime + 0.1, min(startTime + maxDuration, duration)]
      const ceiling  = Math.min(this.startTime + this._maxDuration, this.videoDuration);
      this.endTime   = Math.max(this.startTime + 0.1, Math.min(time, ceiling));
    }
    this._updateUI();
  }

  // ─────────────────────────────────────────────
  // Video event handlers
  // ─────────────────────────────────────────────

  _handleTimeUpdate() {
    if (!this.videoDuration) return;

    // Move playhead
    const pct = (this._videoEl.currentTime / this.videoDuration) * 100;
    this._playhead.style.left = `${pct}%`;

    // Loop clip during preview
    if (this._previewing && this._videoEl.currentTime >= this.endTime) {
      this._videoEl.currentTime = this.startTime;
    }
  }

  // ─────────────────────────────────────────────
  // Preview
  // ─────────────────────────────────────────────

  _startPreview() {
    if (!this.videoDuration) return;
    this._previewing              = true;
    this._previewBtn.textContent  = '⏹ STOP';
    this._videoEl.currentTime     = this.startTime;
    this._videoEl.play().catch(() => {
      // Autoplay may be blocked; reset state gracefully
      this._previewing             = false;
      this._previewBtn.textContent = '▶ PREVIEW';
    });
  }

  _stopPreview() {
    this._previewing             = false;
    this._previewBtn.textContent = '▶ PREVIEW';
    if (!this._videoEl.paused) this._videoEl.pause();
  }

  // ─────────────────────────────────────────────
  // Save
  // ─────────────────────────────────────────────

  _executeSave() {
    const payload = { start: this.startTime, end: this.endTime };

    // Update hidden form inputs if they exist in the document
    const hiddenStart = document.getElementById('clip_start_input');
    const hiddenEnd   = document.getElementById('clip_end_input');
    if (hiddenStart) hiddenStart.value = this.startTime;
    if (hiddenEnd)   hiddenEnd.value   = this.endTime;

    // Fire callback
    if (typeof this._saveCallback === 'function') {
      this._saveCallback(payload);
    }

    // Visual feedback
    const original           = this._saveBtn.textContent;
    this._saveBtn.textContent = 'Saved ✓';
    this._saveBtn.style.background = '#cc4400';
    setTimeout(() => {
      this._saveBtn.textContent      = original;
      this._saveBtn.style.background = '#FF5500';
    }, 1500);
  }

  // ─────────────────────────────────────────────
  // Timestamp application
  // ─────────────────────────────────────────────

  /**
   * Set startTime / endTime and refresh the entire UI.
   * Called both from setTimestamps() (once metadata is ready) and on metadata load.
   * @param {number} start
   * @param {number} end
   */
  _applyTimestamps(start, end) {
    const dur      = this.videoDuration;
    const clampedS = Math.max(0, Math.min(start, dur - 0.1));
    const ceiling  = Math.min(clampedS + this._maxDuration, dur);
    const clampedE = Math.max(clampedS + 0.1, Math.min(end, ceiling));

    this.startTime = clampedS;
    this.endTime   = clampedE;
    this._updateUI();
  }

  // ─────────────────────────────────────────────
  // UI update (single source of truth)
  // ─────────────────────────────────────────────

  /**
   * Recompute and repaint every visual element from the current startTime / endTime.
   */
  _updateUI() {
    if (!this.videoDuration) return;

    const timelineW = this._timeline.getBoundingClientRect().width;
    const handleW   = 12; // px, matches CSS

    // Percentage positions
    const startPct = this._timeToPercent(this.startTime);
    const endPct   = this._timeToPercent(this.endTime);

    // ── Handles ──
    // Position the left edge of the start handle, right edge of end handle
    this._handleStart.style.left  = `calc(${startPct}% - ${handleW}px)`;
    this._handleEnd.style.left    = `${endPct}%`;

    // ── Range highlight ──
    this._range.style.left  = `${startPct}%`;
    this._range.style.width = `${endPct - startPct}%`;

    // ── Numeric inputs ──
    this._startInput.input.value = this.startTime.toFixed(3);
    this._endInput.input.value   = this.endTime.toFixed(3);
    this._endInput.input.max     = String(this.videoDuration);

    // ── Time display ──
    const duration = this.endTime - this.startTime;
    this._timeDisplay.textContent =
      `${this._formatTime(this.startTime)} → ${this._formatTime(this.endTime)} (${duration.toFixed(1)}s)`;
  }

  // ─────────────────────────────────────────────
  // Coordinate helpers
  // ─────────────────────────────────────────────

  /**
   * Convert a client X pixel to a percentage within the timeline.
   * @param {number} clientX
   * @returns {number} 0–100
   */
  _clientXToPercent(clientX) {
    const rect = this._timeline.getBoundingClientRect();
    const raw  = (clientX - rect.left) / rect.width;
    return Math.max(0, Math.min(1, raw)) * 100;
  }

  /**
   * @param {number} t — seconds
   * @returns {number} 0–100
   */
  _timeToPercent(t) {
    return (t / this.videoDuration) * 100;
  }

  /**
   * @param {number} p — 0–100
   * @returns {number} seconds
   */
  _percentToTime(p) {
    return (p / 100) * this.videoDuration;
  }

  // ─────────────────────────────────────────────
  // Formatting
  // ─────────────────────────────────────────────

  /**
   * Format seconds as MM:SS.mmm
   * @param {number} seconds
   * @returns {string}
   */
  _formatTime(seconds) {
    const m  = Math.floor(seconds / 60);
    const s  = Math.floor(seconds % 60);
    const ms = Math.round((seconds % 1) * 1000);
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}.${String(ms).padStart(3, '0')}`;
  }
}
