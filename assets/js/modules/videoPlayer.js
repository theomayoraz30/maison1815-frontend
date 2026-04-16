/**
 * videoPlayer.js — Custom video player
 *
 * Overlay controls (auto-hide while playing), center play indicator,
 * progress scrubbing, mute, time display, and fullscreen toggle.
 */

const PLAY_ICON  = `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>`;
const PAUSE_ICON = `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>`;

const UNMUTE_ICON = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
  <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
  <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
  <path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
</svg>`;

const MUTE_ICON = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
  <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
  <line x1="23" y1="9" x2="17" y2="15"/>
  <line x1="17" y1="9" x2="23" y2="15"/>
</svg>`;

const FULLSCREEN_ICON = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <polyline points="15 3 21 3 21 9"/>
  <polyline points="9 21 3 21 3 15"/>
  <line x1="21" y1="3" x2="14" y2="10"/>
  <line x1="3" y1="21" x2="10" y2="14"/>
</svg>`;

const EXIT_FULLSCREEN_ICON = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <polyline points="4 14 10 14 10 20"/>
  <polyline points="20 10 14 10 14 4"/>
  <line x1="10" y1="14" x2="3" y2="21"/>
  <line x1="21" y1="3" x2="14" y2="10"/>
</svg>`;

export function initVideoPlayer() {
  const video      = document.getElementById('project-video');
  if (!video) return;

  const container  = document.getElementById('video-container');
  const centerPlay = document.getElementById('video-center-play');
  const centerBtn  = centerPlay?.querySelector('.video-center-btn');
  const playBtn    = document.querySelector('.video-btn--play');
  const muteBtn    = document.querySelector('.video-btn--mute');
  const fsBtn      = document.querySelector('.video-btn--fullscreen');
  const progress   = document.querySelector('.video-progress');
  const timeEl     = document.getElementById('video-time');

  // ── Initial icon state ──
  updatePlayIcon();
  updateMuteIcon();
  if (fsBtn) fsBtn.innerHTML = FULLSCREEN_ICON;

  // ── Play / Pause ──
  function togglePlay() {
    video.paused ? video.play() : video.pause();
  }

  playBtn?.addEventListener('click',  (e) => { e.stopPropagation(); togglePlay(); });
  centerBtn?.addEventListener('click', (e) => { e.stopPropagation(); togglePlay(); });

  // Click on the video itself (not on controls) also toggles
  video.addEventListener('click', togglePlay);

  video.addEventListener('play', () => {
    updatePlayIcon();
    container?.classList.add('is-playing');
    centerPlay?.classList.add('is-hidden');
  });

  video.addEventListener('pause', () => {
    updatePlayIcon();
    container?.classList.remove('is-playing');
    centerPlay?.classList.remove('is-hidden');
  });

  // ── Progress bar ──
  video.addEventListener('timeupdate', () => {
    if (!video.duration) return;
    if (progress) progress.value = (video.currentTime / video.duration) * 100;
    if (timeEl) timeEl.textContent = `${formatTime(video.currentTime)} / ${formatTime(video.duration)}`;
  });

  video.addEventListener('loadedmetadata', () => {
    if (timeEl) timeEl.textContent = `0:00 / ${formatTime(video.duration)}`;
  });

  progress?.addEventListener('input', () => {
    if (!video.duration) return;
    video.currentTime = (Number(progress.value) / 100) * video.duration;
  });

  // Prevent progress bar interaction from propagating to video click
  progress?.addEventListener('click', (e) => e.stopPropagation());

  // ── Mute ──
  muteBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    video.muted = !video.muted;
    updateMuteIcon();
  });

  // ── Fullscreen ──
  fsBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    const target = container || video;
    if (!document.fullscreenElement && !document.webkitFullscreenElement) {
      (target.requestFullscreen?.() || target.webkitRequestFullscreen?.());
    } else {
      (document.exitFullscreen?.() || document.webkitExitFullscreen?.());
    }
  });

  document.addEventListener('fullscreenchange',       updateFsIcon);
  document.addEventListener('webkitfullscreenchange', updateFsIcon);

  // ── Helpers ──
  function updatePlayIcon() {
    if (playBtn)  playBtn.innerHTML  = video.paused ? PLAY_ICON : PAUSE_ICON;
    if (centerBtn) {
      centerBtn.innerHTML = PLAY_ICON;
      centerBtn.setAttribute('aria-label', video.paused ? 'Lecture' : 'Pause');
    }
  }

  function updateMuteIcon() {
    if (muteBtn) muteBtn.innerHTML = video.muted ? MUTE_ICON : UNMUTE_ICON;
  }

  function updateFsIcon() {
    if (!fsBtn) return;
    const inFs = !!(document.fullscreenElement || document.webkitFullscreenElement);
    fsBtn.innerHTML = inFs ? EXIT_FULLSCREEN_ICON : FULLSCREEN_ICON;
    fsBtn.setAttribute('aria-label', inFs ? 'Quitter le plein écran' : 'Plein écran');
  }

  function formatTime(seconds) {
    if (!seconds || isNaN(seconds)) return '0:00';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
  }
}
