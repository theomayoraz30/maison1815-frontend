/**
 * UploadManager — XHR-based video and image upload handler with progress UI.
 *
 * Usage:
 *   import { UploadManager } from './upload.js';
 *   const manager = new UploadManager(
 *     '/admin/upload/video.php',
 *     '/admin/upload/image.php',
 *     csrfToken
 *   );
 *   manager.initVideoUpload(inputEl, containerEl, (res) => console.log(res));
 *   manager.initImageUpload(inputEl, previewEl, (res) => console.log(res), 'photos');
 */

export class UploadManager {
    /**
     * @param {string} videoEndpoint  - URL for video.php
     * @param {string} imageEndpoint  - URL for image.php
     * @param {string} csrfToken      - CSRF token value for all requests
     */
    constructor(videoEndpoint, imageEndpoint, csrfToken) {
        this._videoEndpoint = videoEndpoint;
        this._imageEndpoint = imageEndpoint;
        this._csrfToken     = csrfToken;
    }

    // ── Video upload ──────────────────────────────────────────────────────────

    /**
     * Attach a change listener to an <input type="file"> that uploads the selected
     * video file with real-time progress feedback.
     *
     * @param {HTMLInputElement}  inputEl      - File input element
     * @param {HTMLElement}       containerEl  - Element that receives the progress UI
     * @param {function}          onSuccess    - Called with parsed JSON on HTTP 200
     */
    initVideoUpload(inputEl, containerEl, onSuccess) {
        inputEl.addEventListener('change', () => {
            const file = inputEl.files[0];
            if (!file) return;

            // Read the project slug from the designated display element (if present)
            const slugEl = document.getElementById('project-slug-display');
            const projectSlug = slugEl ? (slugEl.value ?? slugEl.textContent ?? '').trim() : '';

            const formData = new FormData();
            formData.append('csrf_token',    this._csrfToken);
            formData.append('project_slug',  projectSlug);
            formData.append('video',         file);

            // Render initial state immediately
            this._renderProgress(containerEl, 0, 0, null, 'uploading');

            const xhr       = new XMLHttpRequest();
            let   startTime = null; // set on loadstart

            // ── Progress tracking ────────────────────────────────────────────
            xhr.upload.addEventListener('loadstart', () => {
                startTime = Date.now();
            });

            xhr.upload.addEventListener('progress', (e) => {
                if (!e.lengthComputable || startTime === null) return;

                const loaded  = e.loaded;
                const total   = e.total;
                const percent = Math.min(100, Math.round((loaded / total) * 100));

                const elapsedSec = (Date.now() - startTime) / 1000;

                // Avoid division-by-zero during first instant
                const speedMBs = elapsedSec > 0.1
                    ? loaded / elapsedSec / 1024 / 1024
                    : 0;

                const remainingSec = (loaded > 0 && elapsedSec > 0.1)
                    ? (total - loaded) / (loaded / elapsedSec)
                    : null;

                this._renderProgress(containerEl, percent, speedMBs, remainingSec, 'uploading');
            });

            xhr.upload.addEventListener('load', () => {
                // Transfer complete; waiting for server to process and respond
                this._renderProgress(containerEl, 100, 0, null, 'processing');
            });

            xhr.upload.addEventListener('error', () => {
                this._renderProgress(containerEl, 0, 0, null, 'error', 'Network error during upload');
            });

            xhr.upload.addEventListener('abort', () => {
                this._renderProgress(containerEl, 0, 0, null, 'error', 'Upload cancelled');
            });

            // ── Response handling ────────────────────────────────────────────
            xhr.addEventListener('load', () => {
                let response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch {
                    this._renderProgress(containerEl, 100, 0, null, 'error', 'Invalid server response');
                    return;
                }

                if (xhr.status === 200 && response.success) {
                    this._renderProgress(containerEl, 100, 0, null, 'done');
                    if (typeof onSuccess === 'function') onSuccess(response);
                } else {
                    const msg = response.error ?? `Server error (HTTP ${xhr.status})`;
                    this._renderProgress(containerEl, 100, 0, null, 'error', msg);
                }
            });

            xhr.addEventListener('error', () => {
                this._renderProgress(containerEl, 0, 0, null, 'error', 'Request failed');
            });

            xhr.open('POST', this._videoEndpoint);
            xhr.send(formData);
        });
    }

    // ── Image upload ──────────────────────────────────────────────────────────

    /**
     * Attach a change listener to an <input type="file"> that previews then
     * uploads the selected image.
     *
     * @param {HTMLInputElement}  inputEl    - File input element
     * @param {HTMLImageElement}  previewEl  - <img> element for the preview
     * @param {function}          onSuccess  - Called with parsed JSON on HTTP 200
     * @param {string}            uploadDir  - Subdirectory key ('photos', 'about', etc.)
     */
    initImageUpload(inputEl, previewEl, onSuccess, uploadDir = 'photos') {
        inputEl.addEventListener('change', () => {
            const file = inputEl.files[0];
            if (!file) return;

            // Show local preview immediately via FileReader (no round-trip needed)
            if (previewEl) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewEl.src = e.target.result;
                    previewEl.style.display = 'block';
                    previewEl.style.opacity = '0.5';
                    previewEl.style.animation = 'upload-pulse 1.2s ease-in-out infinite';
                    _ensureUploadPulseKeyframes();
                };
                reader.readAsDataURL(file);
            }

            const formData = new FormData();
            formData.append('csrf_token',  this._csrfToken);
            formData.append('upload_dir',  uploadDir);
            formData.append('image',       file);

            const xhr = new XMLHttpRequest();

            xhr.addEventListener('load', () => {
                let response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch {
                    console.error('[UploadManager] Invalid JSON from image endpoint');
                    return;
                }

                if (previewEl) {
                    previewEl.style.opacity = '1';
                    previewEl.style.animation = '';
                }

                if (xhr.status === 200 && response.success) {
                    if (typeof onSuccess === 'function') onSuccess(response);
                } else {
                    const msg = response.error ?? `Server error (HTTP ${xhr.status})`;
                    console.error('[UploadManager] Image upload failed:', msg);
                }
            });

            xhr.addEventListener('error', () => {
                if (previewEl) {
                    previewEl.style.opacity = '1';
                    previewEl.style.animation = '';
                }
                console.error('[UploadManager] Network error during image upload');
            });

            xhr.open('POST', this._imageEndpoint);
            xhr.send(formData);
        });
    }

    // ── Progress UI ───────────────────────────────────────────────────────────

    /**
     * Render (or update) the progress UI inside containerEl.
     * Creates the DOM structure once, then mutates it on subsequent calls
     * to avoid flicker.
     *
     * @param {HTMLElement} containerEl      - Wrapper element
     * @param {number}      percent          - 0–100
     * @param {number}      speedMBs         - Transfer speed in MB/s
     * @param {number|null} secondsRemaining - ETA in seconds, or null
     * @param {string}      state            - 'uploading' | 'processing' | 'done' | 'error'
     * @param {string}      [errorMessage]   - Human-readable error text (state='error')
     */
    _renderProgress(containerEl, percent, speedMBs, secondsRemaining, state, errorMessage = '') {
        const WRAPPER_CLASS = 'upload-progress-wrapper';

        // ── Colour map ────────────────────────────────────────────────────────
        const barColors = {
            uploading:  '#FF5500',
            processing: '#FF5500',
            done:       '#22c55e',
            error:      '#ef4444',
        };

        const barColor = barColors[state] ?? '#FF5500';

        // ── Status label ──────────────────────────────────────────────────────
        let statusText;
        if (state === 'uploading')  statusText = 'Uploading...';
        else if (state === 'processing') statusText = 'Processing...';
        else if (state === 'done')  statusText = 'Done ✓';
        else                        statusText = 'Error ✗' + (errorMessage ? ' — ' + errorMessage : '');

        // ── Stats label ───────────────────────────────────────────────────────
        let statsText = '';
        if (state === 'uploading') {
            statsText = percent + '%';
            if (speedMBs > 0.01) {
                statsText += ' — ' + speedMBs.toFixed(1) + ' MB/s';
            }
            if (secondsRemaining !== null && secondsRemaining > 0) {
                statsText += ' — ~' + Math.ceil(secondsRemaining) + 's remaining';
            }
        }

        // ── Build or update DOM ───────────────────────────────────────────────
        let wrapper = containerEl.querySelector('.' + WRAPPER_CLASS);

        if (!wrapper) {
            // First call: create the structure
            wrapper = document.createElement('div');
            wrapper.className = WRAPPER_CLASS;
            wrapper.style.cssText = 'margin-top:16px;';

            wrapper.innerHTML = `
                <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:12px; color:#888;">
                    <span class="upload-status"></span>
                    <span class="upload-stats"></span>
                </div>
                <div style="height:4px; background:#2a2a2a; border-radius:2px; overflow:hidden;">
                    <div class="upload-bar" style="height:100%; width:0%; background:#FF5500; transition:width 0.3s ease;"></div>
                </div>
            `;

            containerEl.appendChild(wrapper);
        }

        // Update text nodes
        wrapper.querySelector('.upload-status').textContent = statusText;
        wrapper.querySelector('.upload-stats').textContent  = statsText;

        // Update bar
        const bar = wrapper.querySelector('.upload-bar');
        bar.style.width      = percent + '%';
        bar.style.background = barColor;

        // Pulsing animation while processing
        if (state === 'processing') {
            if (!bar.style.animation) {
                bar.style.animation = 'upload-pulse 1.2s ease-in-out infinite';
                _ensureUploadPulseKeyframes();
            }
        } else {
            bar.style.animation = '';
        }
    }
}

// ── Module-level helper: inject @keyframes once ───────────────────────────────

function _ensureUploadPulseKeyframes() {
    if (document.getElementById('upload-pulse-style')) return;

    const style = document.createElement('style');
    style.id = 'upload-pulse-style';
    style.textContent = `
        @keyframes upload-pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.45; }
        }
    `;
    document.head.appendChild(style);
}
