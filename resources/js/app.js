// Alpine.js is bundled and started by Livewire — do not import it separately.

window.toastRegion = function () {
    return {
        toasts: [],
        nextId: 1,

        add(detail) {
            // Livewire 4 named-argument dispatch produces a flat object.
            // Guard against array-of-object shape and missing/empty payload.
            const raw = Array.isArray(detail) ? detail[0] : detail;
            const toast = raw ?? {};
            const id = this.nextId++;
            this.toasts.push({
                id,
                type: toast.type ?? 'info',
                title: toast.title ?? '',
                message: toast.message ?? '',
                visible: true,
            });

            const duration = toast.duration ?? 4000;
            setTimeout(() => this.remove(id), duration);
        },

        remove(id) {
            const item = this.toasts.find((t) => t.id === id);
            if (item) {
                item.visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter((t) => t.id !== id);
                }, 200);
            }
        },
    };
};
