/**
 * videoPlayer.js — Custom video player
 *
 * Overlay controls (auto-hide while playing), center play indicator,
 * progress scrubbing, mute, time display, and fullscreen toggle.
 */

const PLAY_ICON            = `<i class="bx bx-play"            aria-hidden="true"></i>`;
const PAUSE_ICON           = `<i class="bx bx-pause"           aria-hidden="true"></i>`;
const UNMUTE_ICON          = `<i class="bx bx-volume-full"     aria-hidden="true"></i>`;
const MUTE_ICON            = `<i class="bx bx-volume-mute"     aria-hidden="true"></i>`;
const FULLSCREEN_ICON      = `<i class="bx bx-fullscreen"      aria-hidden="true"></i>`;
const EXIT_FULLSCREEN_ICON = `<i class="bx bx-exit-fullscreen" aria-hidden="true"></i>`;

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
