<script setup>
import { ref, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import ManagingLayout from '@/Layouts/ManagingLayout.vue'

import { Cropper } from "vue-advanced-cropper"
import "vue-advanced-cropper/dist/style.css"

const props = defineProps({
    today: String,
    weekLater: String,
})

const form = useForm({
    title: '',
    category: '',
    address: '',
    city: '',
    postal_code: '',
    start_date: '',
    end_date: '',
    registration_open: '',
    registration_close: '',
    max_participants: null,
    price: '',
    image: null,
    description: '',
})


const dropActive = ref(false)
const fileInputRef = ref(null)
const fileInputKey = ref(Date.now())
const previewUrl = ref(null)

const showCropModal = ref(false)
const imagePreviewUrl = ref('')
const cropperRef = ref(null)

function openFileDialog() {
    fileInputRef.value?.click()
}

function handleFiles(files) {
    if (!files.length) return
    const file = files[0]

    imagePreviewUrl.value = URL.createObjectURL(file)

    showCropModal.value = true
}

function validateCrop() {
    if (!cropperRef.value) return

    const { canvas } = cropperRef.value.getResult()

    canvas.toBlob((blob) => {
        if (!blob) return

        const croppedFile = new File([blob], 'cropped.png', { type: blob.type })

        form.image = croppedFile

        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value)
        }
        previewUrl.value = URL.createObjectURL(croppedFile)

        showCropModal.value = false
    }, 'image/png', 1)
}

function cancelCrop() {
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value)
        imagePreviewUrl.value = ''
    }

    showCropModal.value = false
}

const submit = () => {
    form.post(route('events.store'), {
        onFinish: console.log('finito'),
    });
};
</script>

<template>

    <Head title="Création D'Événements" />

    <ManagingLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                Création d'un événement
            </h2>
        </template>

        <!-- Section principale centrée -->
        <form @submit.prevent="submit">
            <section class="flex justify-center py-8 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-7xl mx-auto">
                    <div class="flex flex-col lg:flex-row gap-8">

                        <!-- Colonne gauche : Formulaire -->
                        <div class="w-full lg:w-1/2 flex-shrink-0">

                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-6">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Libellé -->
                                    <div>
                                        <label for="title"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                            required>Libellé</label>
                                        <input v-model="form.title" id="title" type="text"
                                            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                        <p v-if="form.errors.title" class="mt-1 text-red-600 text-sm">{{
                                            form.errors.title
                                            }}</p>
                                    </div>

                                    <!-- Catégorie -->
                                    <div>
                                        <label for="category"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catégorie
                                            *</label>
                                        <select v-model="form.category" id="category"
                                            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="" disabled>Choisir une catégorie</option>
                                            <option value="competition">Compétition</option>
                                            <option value="entrainement">Entraînement</option>
                                            <option value="manifestation">Manifestation</option>
                                        </select>
                                        <p v-if="form.errors.category" class="mt-1 text-red-600 text-sm">{{
                                            form.errors.category }}
                                        </p>
                                    </div>
                                    <!-- Adresse -->
                                    <div class="grid grid-cols-1 gap-2">

                                        <div>
                                            <label for="address"
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Lieu de RDV
                                            </label>
                                            <input v-model="form.address" id="address" type="text" placeholder="Adresse"
                                                class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500
                                        dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                            <p v-if="form.errors.address" class="mt-1 text-red-600 text-sm">
                                                {{ form.errors.address }}
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <!-- Ville -->
                                            <div>
                                                <input v-model="form.city" id="city" type="text" placeholder="Ville"
                                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500
         dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                                <p v-if="form.errors.city" class="mt-1 text-red-600 text-sm">
                                                    {{ form.errors.city }}
                                                </p>
                                            </div>

                                            <!-- Code postal -->
                                            <div>
                                                <input v-model="form.postal_code" id="postal_code" type="text"
                                                    placeholder="Code Postal" class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500
         dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                                <p v-if="form.errors.postal_code" class="mt-1 text-red-600 text-sm">
                                                    {{ form.errors.postal_code }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Inscriptions côte à côte -->
                                <div class="flex gap-4 flex-col sm:flex-row">
                                    <div class="flex-1">
                                        <label for="registration_open"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ouverture
                                            guichet</label>
                                        <input v-model="form.registration_open" id="registration_open" type="date"
                                            :min="props.today"
                                            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                        <p v-if="form.errors.registration_open" class="mt-1 text-red-600 text-sm">{{
                                            form.errors.registration_open }}</p>
                                    </div>
                                    <div class="flex-1">
                                        <label for="registration_close"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fermeture
                                            guichet</label>
                                        <input v-model="form.registration_close" id="registration_close" type="date"
                                            :min="form.registration_open ? form.registration_open : props.today"
                                            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                        <p v-if="form.errors.registration_close" class="mt-1 text-red-600 text-sm">{{
                                            form.errors.registration_close }}</p>
                                    </div>
                                </div>

                                <!-- Dates début/fin côte à côte -->
                                <div class="flex gap-4 flex-col sm:flex-row">
                                    <div class="flex-1">
                                        <label for="start_date"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de
                                            début</label>
                                        <input v-model="form.start_date" id="start_date" type="date"
                                            :min="form.registration_close ? form.registration_close : form.registration_open ? form.registration_open : props.today"
                                            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                        <p v-if="form.errors.start_date" class="mt-1 text-red-600 text-sm">
                                            {{ form.errors.start_date }}
                                        </p>
                                    </div>
                                    <div class="flex-1">
                                        <label for="end_date"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de
                                            fin</label>
                                        <input v-model="form.end_date" id="end_date" type="date"
                                            :min="form.start_date ? form.start_date : form.registration_close ? form.registration_close : form.registration_open ? form.registration_open : props.today"
                                            class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                        <p v-if="form.errors.end_date" class="mt-1 text-red-600 text-sm">
                                            {{ form.errors.end_date }}
                                        </p>
                                    </div>
                                </div>




                                <!-- Participants, Tarif & Responsable -->
                                <div class="grid grid-cols-1 gap-6">
                                    <div class="flex gap-4 flex-col sm:flex-row">
                                        <div class="flex-1">
                                            <label for="max_participants"
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Participants
                                                max.</label>
                                            <input v-model="form.max_participants" id="max_participants" type="number"
                                                min="1"
                                                class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                            <p v-if="form.errors.max_participants" class="mt-1 text-red-600 text-sm">{{
                                                form.errors.max_participants }}</p>
                                        </div>
                                        <div class="flex-1">
                                            <label for="price"
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tarif
                                                inscription</label>
                                            <input v-model="form.price" id="price" type="text" placeholder="Ex. 15€"
                                                class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                            <p v-if="form.errors.price" class="mt-1 text-red-600 text-sm">{{
                                                form.errors.price }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Colonne droite : Illustration & Description -->
                        <div class="w-full lg:w-1/2 space-y-6">
                            <!-- Zone d'upload & aperçu agrandie -->
                            <div class="w-full h-80 border-2 border-dashed rounded-lg bg-gray-50 dark:bg-gray-800/40 
                             flex items-center justify-center cursor-pointer transition border-gray-400/60 hover:border-blue-400
                             relative overflow-hidden"
                                :class="dropActive ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : ''"
                                @click="openFileDialog" @dragenter.prevent="dropActive = true"
                                @dragleave.prevent="dropActive = false" @dragover.prevent
                                @drop.prevent="dropActive = false; handleFiles($event.dataTransfer.files)">
                                <template v-if="previewUrl">
                                    <img :src="previewUrl" alt="Aperçu" class="h-full w-full object-contain" />
                                    <div
                                        class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                        <span class="text-white font-semibold">Modifier l'image</span>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="text-center p-6">
                                        <p class="text-2xl font-semibold text-gray-500 dark:text-gray-400 mb-3">
                                            Illustration
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Glissez-déposez votre image ou cliquez ici<br />
                                            Formats : png, jpg – Taille maxi : 2 Mo
                                        </p>
                                    </div>
                                </template>
                                <input :key="fileInputKey" ref="fileInputRef" type="file" accept="image/png, image/jpeg"
                                    class="hidden" @change="e => handleFiles(e.target.files)" />
                            </div>

                            <!-- Modal pour le recadrage d'image -->
                            <div v-if="showCropModal"
                                class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-2xl shadow-xl">
                                    <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Recadrer
                                        l'image
                                    </h3>

                                    <!-- Zone du recadrage -->
                                    <div
                                        class="mb-6 max-h-[60vh] bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                                        <Cropper ref="cropperRef" class="max-w-full h-[400px] mx-auto"
                                            :src="imagePreviewUrl" :stencil-props="{ aspectRatio: 16 / 9 }" />
                                    </div>

                                    <div class="flex justify-end gap-3">
                                        <button type="button"
                                            class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded hover:bg-gray-400 transition-colors"
                                            @click="cancelCrop">
                                            Annuler
                                        </button>
                                        <button type="button"
                                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                                            @click="validateCrop">
                                            Appliquer
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                                <label for="description"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                <textarea v-model="form.description" id="description" rows="8"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                <p v-if="form.errors.description" class="mt-1 text-red-600 text-sm">{{
                                    form.errors.description }}
                                </p>
                            </div>

                            <!-- Bouton de création -->
                            <div class="flex justify-end">
                                <button type="submit" @click="submit" :disabled="form.processing"
                                    class="px-8 py-3 bg-blue-600 text-white text-lg rounded-md shadow hover:bg-blue-700 disabled:opacity-50 transition-colors">
                                    Créer l'événement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </form>

    </ManagingLayout>
</template>