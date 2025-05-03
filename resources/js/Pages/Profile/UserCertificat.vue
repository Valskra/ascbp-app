<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProfileLayout from './Partials/ProfileLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Certificat from '@/Components/Certificat.vue';
import ErrorAlert from '@/Components/ErrorAlert.vue';
import { ref, computed } from 'vue';
import FileIcon from '@/Components/svg/fileIcon.vue';
import { File } from '@coreui/vue/dist/esm/components/form/CFormInput';

const props = defineProps({ certificates: Array });

const form = useForm({
    title: '',
    file: null,
    signed_at: new Date().toISOString().slice(0, 10),
    expires_at: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000)
        .toISOString().slice(0, 10),
});

const dropActive = ref(false);
const fileInput = ref(null);

const fileName = computed(() =>
    form.file ? form.file.name : null
);

function handleFiles(files) {
    if (!files.length) return;

    const f = files[0];
    form.file = f;

    /* titre auto = nom sans extension, si l’utilisateur ne l’a pas déjà tapé */
    if (!form.title) {
        const autoTitle = f.name.replace(/\.[^/.]+$/, ''); // retire l’extension
        form.title = autoTitle.slice(0, 100);              // max 100 chars
    }
}

function submit() {
    form.post('/certificats/store', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            /* réinitialise les données du formulaire */
            form.reset('title', 'file');

            /* force le recré-render du champ fichier */
            fileInputKey.value = Date.now();
        },
    });
}
</script>

<template>

    <Head title="Mes documents" />

    <AuthenticatedLayout>
        <template #header>
            <ProfileLayout>
            </ProfileLayout>
        </template>
        <div class="py-8 max-w-5xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Colonne gauche -->
                <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow p-4 rounded">
                    <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Mes documents</h2>
                    <div v-if="certificates.length" class="space-y-2">
                        <Certificat v-for="certificate in certificates" :key="certificate.id"
                            :certificate="certificate" />
                    </div>
                    <p v-else class="text-gray-500 text-sm">Aucun document disponible.</p>
                </div>

                <div class="md:col-span-2 space-y-6">

                    <!-- ─── ZONE DÉPÔT ─────────────────────────────────────────── -->
                    <div class="flex flex-col items-center justify-center h-72 w-full
         border-2 border-dashed rounded-lg
         transition cursor-pointer
         bg-gray-50 dark:bg-gray-800/40
         border-gray-400/60 hover:border-blue-400/70"
                        :class="dropActive ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : ''"
                        @click="fileInput.click()" @dragenter.prevent="dropActive = true"
                        @dragleave.prevent="dropActive = false" @dragover.prevent
                        @drop.prevent="dropActive = false; handleFiles($event.dataTransfer.files)">
                        <!-- Si un fichier est déjà choisi -->
                        <template v-if="fileName">
                            <FileIcon class="w-12 stroke-blue-500 dark:stroke-white mb-4" />

                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">
                                {{ fileName }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Cliquez ou glissez un autre fichier pour le remplacer
                            </p>
                        </template>

                        <!-- Sinon, texte initial -->
                        <template v-else>
                            <p class="text-2xl font-semibold text-gray-500 dark:text-gray-400 mb-2">
                                Certificat Médical
                            </p>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                Faites glisser votre document ou cliquez ici<br>
                                Formats : png, pdf, jpg, svg • Taille maxi : 10 Mo
                            </p>
                        </template>

                        <!-- input file caché -->
                        <input type="file" accept=".pdf,.png,.jpg,.jpeg,.svg" class="hidden" ref="fileInput"
                            @change="handleFiles($event.target.files)" />
                    </div>

                    <!-- ─── BLOC INFOS ─────────────────────────────────────────────── -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded p-6 space-y-4">

                        <!-- erreurs -->
                        <ErrorAlert :errors="form.errors" />

                        <!-- Titre -->
                        <input v-model="form.title" type="text" name="title" maxlength="100"
                            placeholder="Titre du certificat" class="w-full border p-2 rounded"
                            :class="{ 'border-red-500': form.errors.title }" required />

                        <!-- Dates (readonly) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="text-sm text-gray-600 dark:text-gray-400 flex flex-col">
                                Date de signature :
                                <input v-model="form.signed_at" type="date" readonly
                                    class="border p-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200" />
                            </label>

                            <label class="text-sm text-gray-600 dark:text-gray-400 flex flex-col">
                                Date d’expiration :
                                <input v-model="form.expires_at" type="date" readonly
                                    class="border p-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200" />
                            </label>
                        </div>

                        <!-- Bouton -->
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
