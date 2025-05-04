<!-- DocumentCard.vue -->
<script setup>
import { computed, ref } from 'vue'
import FileIcon from '@/Components/svg/fileIcon.vue'
import TrashIcon from '@/Components/svg/trashIcon.vue'   // ← assure-toi du chemin / casse
import { router } from '@inertiajs/vue3'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({ doc: Object })

const ext = computed(() => props.doc.extension.toLowerCase())
const isImage = computed(() => ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg'].includes(ext.value))
const isPdf = computed(() => ext.value === 'pdf')

function label(title, ext) {
    const MAX = 30
    return title.length <= MAX
        ? `${title}.${ext}`
        : `${title.slice(0, 15)}…${title.slice(-12)}.${ext}`
}
const showConfirm = ref(false)

function askDestroy() {        // ← appelée par le clic sur la poubelle
    showConfirm.value = true
}

function destroy() {           // ← appelée par le bouton “OK” du modal
    showConfirm.value = false
    router.delete(route('certificats.destroy', props.doc.id), {
        preserveScroll: true,
        onSuccess() {
            Inertia.reload()
        }
    })
}
</script>

<template>
    <!-- parent = .group + .relative -->
    <div class="group relative rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow
                transition hover:-translate-y-1 hover:shadow-lg">

        <!-- bouton poubelle -->
        <button @click.stop="askDestroy" class="absolute top-2 right-2 z-10 w-7 h-7 rounded bg-gray-800/80
                   flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
            <TrashIcon class="w-5 stroke-white hover:stroke-red-500" />
        </button>

        <!-- miniature -->
        <div class="h-52 flex items-center justify-center bg-gray-100 dark:bg-gray-700">
            <img v-if="isImage" :src="doc.url" alt=""
                class="h-full w-full object-cover transition group-hover:scale-105" />
            <embed v-else-if="isPdf" :src="doc.url" type="application/pdf"
                class="h-full w-full object-cover opacity-80" />
            <FileIcon v-else :ext="ext" class="w-20 h-20 text-gray-400" />
        </div>

        <!-- pied -->
        <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800">
            <FileIcon :ext="ext" class="w-6 h-6 shrink-0" />
            <span class="truncate text-sm font-medium text-gray-800 dark:text-gray-200">
                {{ label(doc.title, doc.extension) }}
            </span>
        </div>
    </div>
    <ConfirmModal v-if="showConfirm" title="Supprimer ce document ?" :message="label(doc.title, doc.extension)"
        @confirm="destroy" @cancel="showConfirm = false" />
</template>
