document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        items: [],

        push(message, type = 'success', duration = 5000) {
            if (!message) {
                return;
            }

            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });

            setTimeout(() => this.remove(id), duration);
        },

        remove(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    });

    window.showToast = (message, type = 'success') => {
        Alpine.store('toast').push(message, type);
    };
});
