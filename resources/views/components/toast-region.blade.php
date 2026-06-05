<div
    x-data="toastRegion()"
    x-on:toast.window="add($event.detail)"
    id="toast-region"
    role="region"
    aria-label="Notifications"
    aria-live="polite"
    aria-atomic="false"
    class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-2"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            :class="{
                'border-green-200 bg-green-50 text-green-800': toast.type === 'success',
                'border-red-200 bg-red-50 text-red-800': toast.type === 'error',
                'border-blue-200 bg-blue-50 text-blue-800': toast.type === 'info',
                'border-amber-200 bg-amber-50 text-amber-800': toast.type === 'warning',
            }"
            class="flex w-80 items-start gap-3 rounded-[var(--ca-radius-md)] border px-4 py-3 shadow-[var(--ca-shadow-card)]"
            role="status"
        >
            <div class="min-w-0 flex-1">
                <p x-show="toast.title" x-text="toast.title" class="text-sm font-semibold leading-snug"></p>
                <p x-show="toast.message" x-text="toast.message" class="mt-0.5 text-xs leading-snug opacity-80"></p>
            </div>
            <button
                type="button"
                @click="remove(toast.id)"
                aria-label="Dismiss notification"
                class="mt-0.5 shrink-0 text-current opacity-50 hover:opacity-100"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                </svg>
            </button>
        </div>
    </template>
</div>
