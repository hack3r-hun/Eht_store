/**
 * Storefront polish: scroll progress bar, shrinking header, back-to-top,
 * magnetic buttons, button ripple, confetti, split-word headings and
 * submit-loading states. Everything honours prefers-reduced-motion.
 */
const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initScrollUI() {
    const bar = document.querySelector('[data-scroll-progress]');
    const header = document.querySelector('[data-site-header]');
    const toTop = document.querySelector('[data-back-to-top]');
    if (!bar && !header && !toTop) return;

    let ticking = false;
    const update = () => {
        const y = window.scrollY;
        const max = document.documentElement.scrollHeight - window.innerHeight;
        if (bar) bar.style.transform = `scaleX(${max > 0 ? Math.min(y / max, 1) : 0})`;
        if (header) header.classList.toggle('is-scrolled', y > 24);
        if (toTop) toTop.classList.toggle('is-shown', y > 600);
        ticking = false;
    };
    window.addEventListener('scroll', () => {
        if (!ticking) { ticking = true; requestAnimationFrame(update); }
    }, { passive: true });
    update();

    toTop?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: reduced() ? 'auto' : 'smooth' });
    });
}

function initMagnetic() {
    if (reduced() || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
    document.querySelectorAll('[data-magnetic]').forEach((el) => {
        el.addEventListener('mousemove', (e) => {
            const r = el.getBoundingClientRect();
            const x = (e.clientX - (r.left + r.width / 2)) * 0.18;
            const y = (e.clientY - (r.top + r.height / 2)) * 0.25;
            el.style.transform = `translate(${x}px, ${y}px)`;
        });
        el.addEventListener('mouseleave', () => { el.style.transform = ''; });
    });
}

function initRipple() {
    if (reduced()) return;
    document.addEventListener('pointerdown', (e) => {
        const btn = e.target.closest('.btn-primary, .btn-accent, .btn-outline');
        if (!btn) return;
        const r = btn.getBoundingClientRect();
        const size = Math.max(r.width, r.height) * 1.6;
        const dot = document.createElement('span');
        dot.className = 'ripple-dot';
        dot.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX - r.left - size / 2}px;top:${e.clientY - r.top - size / 2}px`;
        btn.appendChild(dot);
        dot.addEventListener('animationend', () => dot.remove());
    });
}

/** Spin + disable submit buttons so double-clicks can't double-submit. */
function initSubmitLoading() {
    const reset = (b) => { b.disabled = false; b.classList.remove('is-loading'); delete b.dataset.loading; };

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented || form.hasAttribute('data-no-loading')) return;
        const btn = form.querySelector('button[type="submit"].btn-primary, button[type="submit"].btn-accent');
        if (!btn || btn.dataset.loading) return;
        btn.dataset.loading = '1';
        btn.classList.add('is-loading');
        // Defer so the form payload is captured before the button is disabled.
        setTimeout(() => { btn.disabled = true; }, 0);
        setTimeout(() => reset(btn), 10000);
    });

    window.addEventListener('pageshow', (e) => {
        if (e.persisted) document.querySelectorAll('.is-loading').forEach(reset);
    });
}

export function launchConfetti(origin) {
    if (reduced()) return;
    const colors = ['#E2725B', '#8A9A5B', '#F5F5DC', '#C9A24B', '#36454F'];
    const wrap = document.createElement('div');
    wrap.className = 'confetti-wrap';
    wrap.setAttribute('aria-hidden', 'true');
    document.body.appendChild(wrap);
    const ox = origin?.x ?? window.innerWidth / 2;
    const oy = origin?.y ?? window.innerHeight / 3;
    for (let i = 0; i < 70; i++) {
        const p = document.createElement('i');
        const angle = Math.random() * Math.PI * 2;
        const dist = 120 + Math.random() * 260;
        p.style.cssText = `left:${ox}px;top:${oy}px;background:${colors[i % colors.length]};` +
            `--dx:${Math.cos(angle) * dist}px;--dy:${Math.sin(angle) * dist - 80}px;--rot:${Math.random() * 720 - 360}deg;` +
            `animation-delay:${Math.random() * 120}ms;border-radius:${Math.random() > 0.5 ? '50%' : '2px'}`;
        wrap.appendChild(p);
    }
    setTimeout(() => wrap.remove(), 2600);
}

function initConfetti() {
    if (document.querySelector('[data-confetti]')) setTimeout(() => launchConfetti(), 350);
}

/** Split [data-split-words] headings into staggered words. */
function initSplitWords() {
    if (reduced()) return;
    document.querySelectorAll('[data-split-words]').forEach((el) => {
        const words = el.textContent.trim().split(/\s+/);
        el.setAttribute('aria-label', words.join(' '));
        el.textContent = '';
        words.forEach((w, i) => {
            const s = document.createElement('span');
            s.className = 'split-word';
            s.setAttribute('aria-hidden', 'true');
            s.style.animationDelay = `${150 + i * 70}ms`;
            s.textContent = w;
            el.appendChild(s);
            el.appendChild(document.createTextNode(' '));
        });
    });
}

export function initEffects() {
    initSplitWords();
    initScrollUI();
    initMagnetic();
    initRipple();
    initSubmitLoading();
    initConfetti();
}

/* ---- Globals used by inline Alpine components ---- */
window.launchConfetti = launchConfetti;

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

/** Toggle a product on the wishlist; resolves with { added, count, message }. */
window.toggleWishlist = async (productId) => {
    const res = await fetch('/wishlist/toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ product_id: productId }),
    });
    if (!res.ok) throw new Error('wishlist');
    const data = await res.json();
    window.dispatchEvent(new CustomEvent('wishlist-updated', { detail: data }));
    window.showToast?.(data.message, 'success');
    return data;
};
