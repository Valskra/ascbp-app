<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    open: Boolean,
    links: {
        type: Array,
        default: () => []
    }
})
const emit = defineEmits(['close'])

const currentPage = ref(1)
const perPage = 15

const totalPages = computed(() => Math.ceil(props.links.length / perPage))
const paginatedLinks = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return props.links.slice(start, start + perPage)
})

/**
 * Format remaining time:
 * - ≥7 days → weeks
 * - ≥1 day and <7 days → days
 * - <1 day → HH:MM
 */
const formatRemaining = expiresAt => {
    const now = new Date()
    const exp = new Date(expiresAt)
    const diffMs = exp - now
    if (diffMs <= 0) return '0 j'
    const diffDays = diffMs / 86400000
    if (diffDays >= 7) {
        const weeks = Math.floor(diffDays / 7)
        return `${weeks} sem`
    } else if (diffDays >= 1) {
        const days = Math.floor(diffDays)
        return `${days} j`
    } else {
        const hours = Math.floor(diffMs / 3600000)
        const minutes = Math.floor((diffMs % 3600000) / 60000)
        const pad = n => String(n).padStart(2, '0')
        return `${pad(hours)}:${pad(minutes)}`
    }
}
const truncate = (text, len = 50) => text.length > len ? text.slice(0, len - 3) + '...' : text
const setPage = page => {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page
}

const copied = ref(false)
function copy(text) {
    navigator.clipboard.writeText(text).then(() => {
        copied.value = true
        setTimeout(() => (copied.value = false), 2000)
    })
}
</script>

<template>
    <Teleport to="body">
        <!-- Toast -->
        <Transition name="fade">
            <div v-if="copied" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-[60] pointer-events-none">
                <div class="bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg">
                    Lien copié !
                </div>
            </div>
        </Transition>

        <!-- Modal -->
        <div v-if="open" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" role="dialog"
            aria-modal="true" aria-labelledby="modal-title">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-11/12 sm:w-3/4 lg:w-2/3 max-h-[80vh] flex flex-col overflow-hidden">

                <!-- Header -->
                <header class="flex items-center justify-between px-8 py-5 bg-gray-50 dark:bg-gray-700">
                    <h3 id="modal-title" class="text-xl font-semibold text-gray-800 dark:text-gray-100">Mes liens
                        d'envoi</h3>
                    <button aria-label="Fermer" @click="emit('close')"
                        class="text-2xl text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">×</button>
                </header>

                <!-- Body -->
                <div class="flex-1 overflow-auto px-6 py-4">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300 uppercase">Titre</th>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300 uppercase">Lien</th>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300 uppercase">Restant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="link in paginatedLinks" :key="link.id" :class="[
                                'transition',
                                link.used_at
                                    ? 'opacity-50 cursor-not-allowed pointer-events-none'
                                    : 'hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer'
                            ]" @click="!link.used_at && copy(link.url)">
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ link.title || '—' }}</td>
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                                    <span class="truncate max-w-xs break-all" :title="link.url">{{ truncate(link.url)
                                        }}</span>
                                </td>
                                <td class="px-4 py-3  text-gray-800 dark:text-gray-200">{{
                                    formatRemaining(link.expires_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav v-if="totalPages > 1"
                    class="flex items-center justify-center px-6 py-3 bg-gray-50 dark:bg-gray-700">
                    <button @click="setPage(currentPage - 1)" :disabled="currentPage === 1" aria-label="Page précédente"
                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-l hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50">
                        ‹
                    </button>
                    <span class="px-4 text-gray-800 dark:text-gray-200">Page {{ currentPage }} / {{ totalPages }}</span>
                    <button @click="setPage(currentPage + 1)" :disabled="currentPage === totalPages"
                        aria-label="Page suivante"
                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-r hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50">
                        ›
                    </button>
                </nav>

                <!-- Footer -->
                <footer class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end">
                    <button @click="emit('close')"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-500">
                        Fermer
                    </button>
                </footer>
            </div>
        </div>
    </Teleport>
</template>