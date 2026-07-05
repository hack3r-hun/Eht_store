/**
 * Back navigation — history.back() with href fallback if history does not change.
 */
export function initBackButtons() {
    document.querySelectorAll('[data-back-nav]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();

            const fallback = link.getAttribute('href');

            if (window.history.length <= 1) {
                if (fallback) {
                    window.location.assign(fallback);
                }

                return;
            }

            const currentUrl = window.location.href;
            window.history.back();

            window.setTimeout(() => {
                if (window.location.href === currentUrl && fallback) {
                    window.location.assign(fallback);
                }
            }, 350);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBackButtons);
} else {
    initBackButtons();
}
