/**
 * Subtle scroll parallax for the homepage hero image — desktop only (mirrors
 * the `hidden lg:block` wrapper), rAF-throttled, and only listens to scroll
 * while the hero is actually in view so it costs nothing on the rest of the
 * page. Skipped entirely under prefers-reduced-motion.
 */
export function initHeroParallax() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (window.matchMedia('(max-width: 1023px)').matches) return;

    const el = document.querySelector('[data-hero-parallax]');
    if (!el) return;

    let ticking = false;
    let listening = false;

    const update = () => {
        const rect = el.getBoundingClientRect();
        const progress = Math.min(Math.max(-rect.top / (rect.height || 1), -1), 1);
        el.style.transform = `translateY(${progress * 20}px)`;
        ticking = false;
    };

    const onScroll = () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(update);
    };

    const observer = new IntersectionObserver(
        (entries) => {
            const visible = entries[0].isIntersecting;
            if (visible && !listening) {
                listening = true;
                window.addEventListener('scroll', onScroll, { passive: true });
                update();
            } else if (!visible && listening) {
                listening = false;
                window.removeEventListener('scroll', onScroll);
            }
        },
        { threshold: 0 }
    );

    observer.observe(el);
}
