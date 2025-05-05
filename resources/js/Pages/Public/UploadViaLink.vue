<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import FileIcon from '@/Components/svg/fileIcon.vue'

const props = defineProps({
    token: String,
    title: String || null,
    expire: String || null,
    used: Boolean,
})


const form = useForm({
    file: null,
    title: props.title ?? '',
})
const fileName = ref(null)
const dropActive = ref(false)
const fileInputRef = ref(null)

function openFileDialog() {
    fileInputRef.value?.click()
}

function handleFiles(files) {
    if (!files.length) return
    const file = files[0]
    form.file = file
    fileName.value = file.name
}

function submit() {
    form.post(route('upload-link.upload', props.token), {
        forceFormData: true,
    })
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center px-4 py-10 bg-gray-50 dark:bg-gray-900">
        <div class="w-full max-w-2xl space-y-6 text-center">

            <div v-if="props.used"
                class="bg-yellow-100 dark:bg-yellow-900 border border-yellow-300 dark:border-yellow-700 p-6 rounded-lg">
                <p class="text-yellow-800 dark:text-yellow-100 text-lg font-medium">Ce lien a déjà été utilisé.</p>
                <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-2">
                    Vous ne pouvez envoyer un document qu’une seule fois par lien.
                </p>
            </div>
            <div v-else>

                <h1 class="text-2xl font-semibold text-gray-800 dark:text-white mb-1">
                    Déposer un document
                </h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">
                    Lien valable jusqu’au {{ new Date(expire).toLocaleDateString() }}
                </p>

                <!-- Zone de dépôt -->
                <div class="w-full h-80 border-2 border-dashed rounded-lg bg-white dark:bg-gray-800 px-6
            flex flex-col items-center justify-center cursor-pointer transition
            border-gray-300 hover:border-blue-500 dark:border-gray-600 dark:hover:border-blue-400"
                    :class="dropActive ? 'bg-blue-50 dark:bg-blue-900/20' : ''" @click="openFileDialog"
                    @dragenter.prevent="dropActive = true" @dragleave.prevent="dropActive = false" @dragover.prevent
                    @drop.prevent="dropActive = false; handleFiles($event.dataTransfer.files)">
                    <template v-if="fileName">
                        <FileIcon class="w-12 stroke-blue-500 dark:stroke-white mb-4" />
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">
                            {{ fileName }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Cliquez ou glissez un autre fichier pour le remplacer
                        </p>
                    </template>

                    <template v-else>
                        <p class="text-2xl font-semibold text-gray-500 dark:text-gray-400 mb-2">
                            Déposez votre fichier
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                            Cliquez ou glissez un document ici<br />
                            Formats : png, pdf, jpg, docx, svg — Max 10 Mo
                        </p>
                    </template>

                    <input ref="fileInputRef" type="file" class="hidden"
                        accept=".doc,.docx,.dotx,.odt,.svg,.pdf,.png,.jpg,.jpeg,.gif,.bmp,.webp,.heic"
                        @change="handleFiles($event.target.files)" />
                </div>

                <!-- Formulaire titre + bouton -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                    <div v-if="!props.title">
                        <input v-model="form.title" type="text" maxlength="100" placeholder="Titre du document"
                            class="w-full border p-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            :class="{ 'border-red-500': form.errors.title }" />
                        <p v-if="form.errors.title" class="text-red-500 text-sm">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <button @click="submit" :disabled="!form.file || form.processing"
                        class="w-full py-2 rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ form.processing ? 'Envoi…' : 'Envoyer le document' }}
                    </button>

                    <p v-if="form.errors.file" class="text-red-500 text-sm">
                        {{ form.errors.file }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
