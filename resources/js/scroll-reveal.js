/**
 * Scroll reveal — adds .is-visible when elements enter viewport
 */
export function initScrollReveal() {
    const skipReveal = document.body?.dataset.scrollReveal === 'off';

    if (skipReveal) {
        document.querySelectorAll('[data-reveal]').forEach((el) => {
            el.classList.add('is-visible');
        });
    } else {
        document.documentElement.classList.add('js-reveal');

        const reveals = document.querySelectorAll('[data-reveal]');

        if (reveals.length) {
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                },
                { threshold: 0.08, rootMargin: '0px 0px -20px 0px' }
            );

            reveals.forEach((el, i) => {
                const delay = el.dataset.revealDelay || (i % 6) * 80;
                el.style.transitionDelay = `${delay}ms`;

                const rect = el.getBoundingClientRect();
                const inViewport = rect.top < window.innerHeight + 80 && rect.bottom > -80;
                const hasInteractive = el.querySelector('a, button, input, select, textarea, form, label');

                if (inViewport || hasInteractive) {
                    el.classList.add('is-visible');
                } else {
                    observer.observe(el);
                }
            });
        }
    }

    // Count-up numbers run regardless of reveal mode (admin pages set
    // data-scroll-reveal="off" to skip entrance animation, but still want
    // animated stat values).
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('[data-count]').forEach((el) => {
        const target = parseInt(el.dataset.count, 10);
        const suffix = el.dataset.countSuffix || '';
        const prefix = el.dataset.countPrefix || '';

        if (reducedMotion) {
            el.textContent = prefix + target.toLocaleString() + suffix;
            return;
        }

        const counterObserver = new IntersectionObserver(
            (entries) => {
                if (! entries[0].isIntersecting) {
                    return;
                }
                counterObserver.disconnect();

                const duration = 1500;
                const start = performance.now();

                const tick = (now) => {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = prefix + Math.floor(eased * target).toLocaleString() + suffix;
                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    } else {
                        el.textContent = prefix + target.toLocaleString() + suffix;
                    }
                };

                requestAnimationFrame(tick);
            },
            { threshold: 0.5 }
        );

        counterObserver.observe(el);
    });
}
