/**
 * cards.js — Project card hover (image → video crossfade + scale)
 *
 * On hover: GSAP fades image out, plays video in + scales media(1.03).
 * On leave: reverses, pauses and rewinds the video.
 * Also registers ScrollTrigger reveal for the projects grid.
 */

export function initCards() {
  const cards = document.querySelectorAll('.card');
  if (!cards.length) return;

  // ── Per-card hover: crossfade image → video
  cards.forEach(card => {
    const img   = card.querySelector('.card__img');
    const video = card.querySelector('.card__video');
    const media = card.querySelector('.card__media');
    if (!img || !video || !media) return;

    card.addEventListener('mouseenter', () => {
      // Attempt autoplay (may be blocked on some browsers without interaction)
      video.play().catch(() => {});
      gsap.to(video, { opacity: 1, duration: 0.4, ease: 'power2.out' });
      gsap.to(img,   { opacity: 0, duration: 0.4, ease: 'power2.out' });
      gsap.to(media, { scale: 1.03, duration: 0.6, ease: 'power2.out' });
    });

    card.addEventListener('mouseleave', () => {
      gsap.to(video, {
        opacity: 0,
        duration: 0.35,
        ease: 'power2.in',
        onComplete() { video.pause(); video.currentTime = 0; },
      });
      gsap.to(img,   { opacity: 1, duration: 0.35, ease: 'power2.in' });
      gsap.to(media, { scale: 1,   duration: 0.5,  ease: 'power2.in' });
    });
  });

  // ── Scroll reveal — staggered fade+slide when grid enters viewport
  if (typeof ScrollTrigger !== 'undefined') {
    gsap.fromTo('.projects__header',
      { opacity: 0, y: 15 },
      {
        opacity: 1, y: 0,
        duration: 0.6,
        ease: 'power2.out',
        scrollTrigger: { trigger: '.projects', start: 'top 82%' },
      }
    );

    gsap.fromTo('.card',
      { opacity: 0, y: 35 },
      {
        opacity: 1, y: 0,
        duration: 0.7,
        stagger: 0.09,
        ease: 'power2.out',
        scrollTrigger: {
          trigger: '.projects__grid',
          start: 'top 80%',
        },
      }
    );
  }
}
