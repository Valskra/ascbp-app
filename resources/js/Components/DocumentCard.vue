<script setup>
import { computed, ref } from 'vue'
import FileIcon from '@/Components/svg/fileIcon.vue'
import TrashIcon from '@/Components/svg/trashIcon.vue'
import ShareIcon from '@/Components/svg/shareIcon.vue' // Nouveau composant
import { router, usePage } from '@inertiajs/vue3'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({ doc: Object })

const user = usePage().props.auth.user

const ext = computed(() => props.doc.extension.toLowerCase())
const isImage = computed(() => ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg'].includes(ext.value))
const isPdf = computed(() => ext.value === 'pdf')

// Utiliser l'URL admin si l'utilisateur est admin, sinon l'URL publique
const documentUrl = computed(() => {
    if (user.is_admin && props.doc.admin_url) {
        return props.doc.admin_url
    }
    return props.doc.url
})

function label(title, ext) {
    const MAX = 30
    return title.length <= MAX
        ? `${title}.${ext}`
        : `${title.slice(0, 15)}…${title.slice(-12)}.${ext}`
}

const showConfirm = ref(false)
const showCopiedToast = ref(false)

function askDestroy() {
    showConfirm.value = true
}

function destroy() {
    showConfirm.value = false
    router.delete(route('certificats.destroy', props.doc.id), {
        preserveScroll: true,
        onSuccess() {
            router.reload()
        }
    })
}

function openInNewTab() {
    window.open(documentUrl.value, '_blank')
}

// Nouvelle fonction pour partager/copier l'URL
async function shareDocument() {
    try {
        await navigator.clipboard.writeText(documentUrl.value)
        showCopiedToast.value = true
        // Masquer le toast après 2 secondes
        setTimeout(() => {
            showCopiedToast.value = false
        }, 2000)
    } catch (err) {
        console.error('Erreur lors de la copie:', err)
        // Fallback pour les navigateurs qui ne supportent pas l'API Clipboard
        fallbackCopyTextToClipboard(documentUrl.value)
    }
}

// Fallback pour la copie si l'API Clipboard n'est pas supportée
function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea")
    textArea.value = text
    textArea.style.top = "0"
    textArea.style.left = "0"
    textArea.style.position = "fixed"

    document.body.appendChild(textArea)
    textArea.focus()
    textArea.select()

    try {
        document.execCommand('copy')
        showCopiedToast.value = true
        setTimeout(() => {
            showCopiedToast.value = false
        }, 2000)
    } catch (err) {
        console.error('Fallback: Impossible de copier', err)
    }

    document.body.removeChild(textArea)
}
</script>

<template>
    <div class="group relative rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow transition hover:-translate-y-1 hover:shadow-lg cursor-pointer max-w-80"
        @click="openInNewTab">

        <!-- Boutons d'action -->
        <div class="absolute top-2 right-2 z-10 flex gap-1 opacity-0 group-hover:opacity-100 transition">
            <!-- Bouton partage -->
            <button @click.stop="shareDocument"
                class="w-7 h-7 rounded bg-gray-800/80 hover:bg-blue-700 flex items-center justify-center transition-colors"
                title="Copier le lien">
                <ShareIcon class="w-4 h-4 stroke-white" />
            </button>

            <!-- Bouton supprimer -->
            <button @click.stop="askDestroy"
                class="w-7 h-7 rounded bg-gray-800/80 hover:bg-red-600 flex items-center justify-center transition-colors"
                title="Supprimer">
                <TrashIcon class="w-4 h-4 stroke-white " />
            </button>
        </div>

        <div class="h-52 flex items-center justify-center bg-gray-100 dark:bg-gray-700">
            <img v-if="isImage" :src="documentUrl" alt=""
                class="h-full w-full object-cover transition group-hover:scale-105" />
            <embed v-else-if="isPdf" :src="documentUrl" type="application/pdf"
                class="h-full w-full object-cover opacity-80" />
            <FileIcon v-else :ext="ext" class="w-20 h-20 text-gray-400" />
        </div>

        <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800">
            <FileIcon :ext="ext" class="w-6 h-6 shrink-0" />
            <span class="truncate text-sm font-medium text-gray-800 dark:text-gray-200">
                {{ label(doc.title, doc.extension) }}
            </span>
        </div>


    </div>

    <!-- Toast de confirmation -->
    <Teleport to="body">
        <Transition name="toast">
            <div v-if="showCopiedToast"
                class="fixed bottom-4 right-4 z-50 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Lien copié !
            </div>
        </Transition>
    </Teleport>

    <ConfirmModal v-if="showConfirm" title="Supprimer ce document ?" :message="label(doc.title, doc.extension)"
        @confirm="destroy" @cancel="showConfirm = false" />
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>