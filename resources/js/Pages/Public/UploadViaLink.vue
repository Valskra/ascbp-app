<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import FileIcon from '@/Components/svg/fileIcon.vue'

const props = defineProps({
    token: String,
    title: String | null,
    expire: String,    // ISO
})

const form = useForm({ file: null, title: props.title ?? '' })
const fileName = ref(null)

function handle(e) {
    const f = e.target.files[0]
    form.file = f
    fileName.value = f?.name
}
function submit() {
    form.post(route('upload-link.upload', props.token), { forceFormData: true })
}
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center p-6">
        <h1 class="text-2xl font-semibold mb-6">Déposer un document</h1>

        <p class="text-gray-500 mb-6">Lien valable jusqu’au {{ new Date(expire).toLocaleDateString() }}</p>

        <div class="w-full max-w-md space-y-4">

            <input v-if="!props.title" v-model="form.title" class="input w-full" placeholder="Titre du document" />

            <input type="file" @change="handle"
                accept=".doc,.docx,.dotx,.odt,.svg,.pdf,.png,.jpg,.jpeg,.gif,.bmp,.webp,.heic"
                class="w-full border p-2 rounded" />

            <p v-if="fileName" class="text-sm text-gray-500">{{ fileName }}</p>

            <button @click="submit" class="btn btn-primary w-full" :disabled="!form.file || form.processing">
                {{ form.processing ? 'Envoi…' : 'Envoyer' }}
            </button>

            <p v-if="Object.keys(form.errors).length" class="text-red-600 text-sm">
                {{ Object.values(form.errors)[0] }}
            </p>
        </div>
    </div>
</template>
