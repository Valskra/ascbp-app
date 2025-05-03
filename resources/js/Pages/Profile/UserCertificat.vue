<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import ErrorAlert from '@/Components/ErrorAlert.vue';
import DocumentCard from '@/Components/DocumentCard.vue';
import FileIcon from '@/Components/svg/fileIcon.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProfileLayout from './Partials/ProfileLayout.vue';


const props = defineProps({ certificates: Array });

/* --------- formulaire --------- */
const form = useForm({
    title: '',
    file: null,
    signed_at: new Date().toISOString().slice(0, 10),
    expires_at: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000)
        .toISOString().slice(0, 10),
});

/* --------- refs / état --------- */
const dropActive = ref(false);
const fileInputRef = ref(null);      // 👉 ref unique
const fileInputKey = ref(Date.now()); // pour reset visuel

const fileName = computed(() => form.file?.name ?? null);

/* --------- handlers --------- */
function openFileDialog() {
    if (fileInputRef.value) fileInputRef.value.click();
}

function handleFiles(files) {
    if (!files.length) return;
    const f = files[0];
    form.file = f;
    if (!form.title) {
        form.title = f.name.replace(/\.[^/.]+$/, '').slice(0, 100);
    }
}

function submit() {
    form.post('/certificats/store', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('title', 'file');
            fileInputKey.value = Date.now();   // recrée l’input
        },
    });
}
</script>


<!-- … balise script inchangée … -->

<template>

    <Head title="Mes documents" />
    <AuthenticatedLayout>
        <template #header>
            <ProfileLayout />
        </template>

        <div class="py-8 px-4 sm:px-8 xl:px-16 mx-auto max-w-screen-2xl">
            <div class="flex flex-col lg:flex-row lg:gap-12">

                <!-- A ─── COLONNE CARTES (ordre 2 sur mobile) -->
                <div class="order-last lg:order-1 lg:flex-grow">
                    <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                        Mes documents
                    </h2>

                    <!-- C ─── grille responsive max 3 -->
                    <div class="grid gap-6
                      grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">

                        <DocumentCard v-for="c in certificates" :key="c.id" :doc="c" />
                    </div>
                </div>

                <!-- B ─── COLONNE DEPOT + FORMULAIRE (ordre 1 sur mobile) -->
                <div class="order-first lg:order-2
                            flex flex-col items-center lg:items-start space-y-10
                            lg:sticky lg:top-24 lg:self-start
                            w-full lg:w-[800px]">
                    <!-- Zone dépôt : 100 % → 60 % → 50 % -->
                    <div class="w-full h-96 border-2 border-dashed rounded-lg bg-gray-50 mt-8 px-8
                    dark:bg-gray-800/40 flex flex-col items-center justify-center cursor-pointer transition
                    border-gray-400/60 hover:border-blue-400" :class="dropActive
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                        : ''" @click="openFileDialog" @dragenter.prevent="dropActive = true"
                        @dragleave.prevent="dropActive = false" @dragover.prevent
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
                                Certificat&nbsp;Médical
                            </p>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                Faites glisser votre document ou cliquez ici<br>
                                Formats&nbsp;: png, pdf, jpg, svg&nbsp;&bull;&nbsp;Taille&nbsp;maxi&nbsp;: 10 Mo
                            </p>
                        </template>

                        <input :key="fileInputKey" ref="fileInputRef" class="hidden" type="file"
                            accept=".doc,.docx,.dotx,.odt,.svg,.pdf,.png,.jpg,.jpeg,.gif,.bmp,.webp,.heic"
                            @change="handleFiles($event.target.files)" />
                    </div>

                    <!-- Formulaire (mêmes classes de largeur) -->
                    <div class="w-full bg-white dark:bg-gray-800 shadow rounded
                     p-6 space-y-4">
                        <ErrorAlert :errors="form.errors" />

                        <input v-model="form.title" type="text" maxlength="100" placeholder="Titre du certificat"
                            class="w-full border p-2 rounded" :class="{ 'border-red-500': form.errors.title }" />

                        <div class="grid sm:grid-cols-2 gap-4">
                            <label class="text-sm text-gray-600 dark:text-gray-400 flex flex-col">
                                Date de signature&nbsp;:
                                <input v-model="form.signed_at" type="date" readonly class="border p-2 rounded bg-gray-100 dark:bg-gray-700
                                text-gray-700 dark:text-gray-200" />
                            </label>
                            <label class="text-sm text-gray-600 dark:text-gray-400 flex flex-col">
                                Date d’expiration&nbsp;:
                                <input v-model="form.expires_at" type="date" readonly class="border p-2 rounded bg-gray-100 dark:bg-gray-700
                                text-gray-700 dark:text-gray-200" />
                            </label>
                        </div>

                        <button @click="submit" :disabled="form.processing || !form.file" class="w-full py-2 rounded bg-blue-600 text-white hover:bg-blue-700
                       disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ form.processing ? 'Envoi…' : 'Uploader le certificat' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>