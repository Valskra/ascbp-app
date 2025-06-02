<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
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
    country: 'France',
    registration_open: '',
    registration_close: '',
    start_date: '',
    end_date: '',
    max_participants: null,
    members_only: false,
    requires_medical_certificate: false,
    price: '',
    image: null,
    description: '',
})

// États pour l'interface
const currentStep = ref(1)
const dropActive = ref(false)
const fileInputRef = ref(null)
const fileInputKey = ref(Date.now())
const previewUrl = ref(null)
const showCropModal = ref(false)
const imagePreviewUrl = ref('')
const cropperRef = ref(null)

// Limite de caractères pour la description
const maxDescriptionLength = 2000
const descriptionLength = computed(() => form.description.length)
const descriptionProgress = computed(() => (descriptionLength.value / maxDescriptionLength) * 100)

// Validation par étape
const step1Valid = computed(() => {
    return form.title && form.category && form.registration_open && form.registration_close
})

const step2Valid = computed(() => {
    return form.start_date && form.end_date
})

// Auto-activation du certificat médical pour les compétitions
watch(() => form.category, (newCategory) => {
    if (newCategory === 'competition') {
        form.requires_medical_certificate = true
    }
})

// Navigation entre les étapes
function nextStep() {
    if (currentStep.value < 3) {
        currentStep.value++
    }
}

function prevStep() {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

// Gestion des images
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

// Auto-remplissage des dates
watch(() => form.registration_open, (newDate) => {
    if (newDate && !form.registration_close) {
        const regOpen = new Date(newDate)
        const regClose = new Date(regOpen)
        regClose.setDate(regClose.getDate() + 7)
        form.registration_close = regClose.toISOString().split('T')[0]
    }
})

watch(() => form.registration_close, (newDate) => {
    if (newDate && !form.start_date) {
        const regClose = new Date(newDate)
        const eventStart = new Date(regClose)
        eventStart.setDate(eventStart.getDate() + 1)
        form.start_date = eventStart.toISOString().split('T')[0]
    }
})

watch(() => form.start_date, (newDate) => {
    if (newDate && !form.end_date) {
        form.end_date = newDate
    }
})

const submit = () => {
    form.post(route('events.store'), {
        forceFormData: true,
        onError: (errors) => {
            console.error('Erreurs:', errors)
        }
    })
}
</script>

<template>

    <Head title="Créer un Événement" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        Créer un événement
                    </h2>
                </div>

                <!-- Indicateur de progression -->
                <div class="flex items-center space-x-2">
                    <div v-for="step in 3" :key="step"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors"
                        :class="currentStep >= step
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400'">
                        {{ step }}
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8 px-4 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="max-w-4xl mx-auto">

                <!-- Étape 1: Informations de base + Inscriptions -->
                <div v-show="currentStep === 1" class="space-y-8">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white flex items-center">
                            <div
                                class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-3">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">1</span>
                            </div>
                            Informations générales et inscriptions
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Titre -->
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Titre de l'événement *
                                </label>
                                <input v-model="form.title" type="text" required
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                                    placeholder="Ex: Championnat de tennis 2024" />
                                <p v-if="form.errors.title" class="mt-1 text-red-600 text-sm">{{ form.errors.title }}
                                </p>
                            </div>

                            <!-- Catégorie -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Catégorie *
                                </label>
                                <select v-model="form.category" required
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                    <option value="" disabled>Choisir une catégorie</option>
                                    <option value="competition">🏆 Compétition</option>
                                    <option value="entrainement">💪 Entraînement</option>
                                    <option value="manifestation">🎉 Manifestation</option>
                                </select>
                                <p v-if="form.errors.category" class="mt-1 text-red-600 text-sm">{{ form.errors.category
                                }}</p>
                            </div>

                            <!-- Participants max -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nombre max de participants
                                </label>
                                <input v-model="form.max_participants" type="number" min="1"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                    placeholder="Illimité si vide" />
                                <p v-if="form.errors.max_participants" class="mt-1 text-red-600 text-sm">{{
                                    form.errors.max_participants }}</p>
                            </div>

                            <!-- Prix -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tarif d'inscription
                                </label>
                                <input v-model="form.price" type="text"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                    placeholder="Ex: 15€, Gratuit, 10€ membres / 20€ non-membres" />
                                <p v-if="form.errors.price" class="mt-1 text-red-600 text-sm">{{ form.errors.price }}
                                </p>
                            </div>

                            <!-- Options d'accès -->
                            <div class="lg:col-span-2 mt-6">
                                <h4
                                    class="text-md font-medium text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    🔐 Restrictions d'accès
                                </h4>
                                <div class="space-y-4 lg:flex lg:flex-row gap-6  lg:items-baseline">
                                    <!-- Réservé aux adhérents -->
                                    <div class="flex items-center">
                                        <input v-model="form.members_only" type="checkbox" id="members_only"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <label for="members_only"
                                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                            Réservé aux adhérents uniquement
                                        </label>
                                    </div>

                                    <!-- Certificat médical requis -->
                                    <div class="flex items-center">
                                        <input v-model="form.requires_medical_certificate" type="checkbox"
                                            id="requires_medical" :disabled="form.category === 'competition'"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-50">
                                        <label for="requires_medical"
                                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                            Certificat médical requis
                                            <span v-if="form.category === 'competition'"
                                                class="text-orange-600 text-xs ml-1">
                                                (obligatoire pour les compétitions)
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Section Inscriptions -->
                            <div class="lg:col-span-2 mt-8">
                                <h4
                                    class="text-md font-medium text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    📝 Période d'inscription
                                </h4>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Ouverture inscriptions -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Ouverture des inscriptions *
                                        </label>
                                        <input v-model="form.registration_open" type="date" :min="props.today" required
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" />
                                        <p v-if="form.errors.registration_open" class="mt-1 text-red-600 text-sm">{{
                                            form.errors.registration_open }}</p>
                                    </div>

                                    <!-- Fermeture inscriptions -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Fermeture des inscriptions *
                                        </label>
                                        <input v-model="form.registration_close" type="date"
                                            :min="form.registration_open || props.today" required
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" />
                                        <p v-if="form.errors.registration_close" class="mt-1 text-red-600 text-sm">{{
                                            form.errors.registration_close }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Étape 2: Dates d'événement et lieu -->
                <div v-show="currentStep === 2" class="space-y-8">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white flex items-center">
                            <div
                                class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-3">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">2</span>
                            </div>
                            Dates de l'événement et localisation
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Section Dates d'événement -->
                            <div class="lg:col-span-2 mb-6">
                                <h4
                                    class="text-md font-medium text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    📅 Dates de l'événement
                                </h4>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Date de début -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Date de début *
                                        </label>
                                        <input v-model="form.start_date" type="date"
                                            :min="form.registration_close || props.today" required
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" />
                                        <p v-if="form.errors.start_date" class="mt-1 text-red-600 text-sm">{{
                                            form.errors.start_date }}</p>
                                    </div>

                                    <!-- Date de fin -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Date de fin *
                                        </label>
                                        <input v-model="form.end_date" type="date"
                                            :min="form.start_date || form.registration_close || props.today" required
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" />
                                        <p v-if="form.errors.end_date" class="mt-1 text-red-600 text-sm">{{
                                            form.errors.end_date
                                        }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Section Localisation -->
                            <div class="lg:col-span-2">
                                <h4
                                    class="text-md font-medium text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-600">
                                    📍 Lieu de rendez-vous
                                </h4>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Adresse -->
                                    <div class="lg:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Adresse
                                        </label>
                                        <input v-model="form.address" type="text"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                            placeholder="Ex: 123 Avenue des Sports" />
                                        <p v-if="form.errors.address" class="mt-1 text-red-600 text-sm">{{
                                            form.errors.address
                                        }}</p>
                                    </div>

                                    <!-- Ville -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Ville
                                        </label>
                                        <input v-model="form.city" type="text"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                            placeholder="Ex: Paris" />
                                        <p v-if="form.errors.city" class="mt-1 text-red-600 text-sm">{{ form.errors.city
                                        }}</p>
                                    </div>

                                    <!-- Code postal -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Code postal
                                        </label>
                                        <input v-model="form.postal_code" type="text"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                            placeholder="Ex: 75001" />
                                        <p v-if="form.errors.postal_code" class="mt-1 text-red-600 text-sm">{{
                                            form.errors.postal_code }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Étape 3: Illustration et description -->
                <div v-show="currentStep === 3" class="space-y-8">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white flex items-center">
                            <div
                                class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-3">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">3</span>
                            </div>
                            Illustration et description
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Zone d'upload d'image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Image de l'événement
                                </label>
                                <div class="w-full h-64 border-2 border-dashed rounded-xl bg-gray-50 dark:bg-gray-800/40 
                                         flex items-center justify-center cursor-pointer transition-all duration-200
                                         border-gray-300 hover:border-blue-400 dark:border-gray-600 dark:hover:border-blue-500"
                                    :class="dropActive ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 scale-105' : ''"
                                    @click="openFileDialog" @dragenter.prevent="dropActive = true"
                                    @dragleave.prevent="dropActive = false" @dragover.prevent
                                    @drop.prevent="dropActive = false; handleFiles($event.dataTransfer.files)">

                                    <template v-if="previewUrl">
                                        <div class="relative w-full h-full">
                                            <img :src="previewUrl" alt="Aperçu"
                                                class="w-full h-full object-cover rounded-lg" />
                                            <div
                                                class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity rounded-lg">
                                                <span class="text-white font-semibold">📸 Modifier l'image</span>
                                            </div>
                                        </div>
                                    </template>

                                    <template v-else>
                                        <div class="text-center">
                                            <div class="text-4xl mb-4">🖼️</div>
                                            <p class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-2">
                                                Ajouter une illustration
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-500">
                                                Glissez une image ou cliquez ici<br />
                                                PNG, JPG - Max 2 Mo
                                            </p>
                                        </div>
                                    </template>

                                    <input :key="fileInputKey" ref="fileInputRef" type="file"
                                        accept="image/png, image/jpeg, image/jpg, image/gif" class="hidden"
                                        @change="e => handleFiles(e.target.files)" />
                                </div>
                                <p v-if="form.errors.image" class="mt-2 text-red-600 text-sm">{{ form.errors.image }}
                                </p>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Description de l'événement
                                </label>
                                <div class="relative">
                                    <textarea v-model="form.description" :maxlength="maxDescriptionLength" rows="10"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg 
                                              focus:ring-2 focus:ring-blue-500 focus:border-transparent 
                                              dark:bg-gray-700 dark:text-white resize-none"
                                        placeholder="Décrivez votre événement : règlement, matériel nécessaire, niveau requis, récompenses..."></textarea>

                                    <!-- Compteur de caractères -->
                                    <div class="flex justify-between items-center mt-2">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-4">
                                            <div class="h-2 rounded-full transition-all duration-300"
                                                :class="descriptionProgress > 90 ? 'bg-red-500' : descriptionProgress > 70 ? 'bg-yellow-500' : 'bg-green-500'"
                                                :style="{ width: `${Math.min(descriptionProgress, 100)}%` }"></div>
                                        </div>
                                        <span class="text-sm font-medium whitespace-nowrap"
                                            :class="descriptionProgress > 90 ? 'text-red-600' : 'text-gray-600 dark:text-gray-400'">
                                            {{ descriptionLength }} / {{ maxDescriptionLength }}
                                        </span>
                                    </div>
                                </div>
                                <p v-if="form.errors.description" class="mt-2 text-red-600 text-sm">{{
                                    form.errors.description
                                }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button v-if="currentStep > 1" type="button" @click="prevStep" class="flex items-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg 
                               text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 
                               transition-colors font-medium">
                        ← Précédent
                    </button>
                    <div v-else></div>

                    <button v-if="currentStep < 3" type="button" @click="nextStep"
                        :disabled="(currentStep === 1 && !step1Valid) || (currentStep === 2 && !step2Valid)" class="flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg 
                               hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed 
                               transition-colors font-medium">
                        Suivant →
                    </button>

                    <button v-else type="submit" :disabled="form.processing" class="flex items-center px-8 py-3 bg-green-600 text-white rounded-lg 
                               hover:bg-green-700 disabled:opacity-50 transition-colors font-medium">
                        <span v-if="form.processing">⏳ Création...</span>
                        <span v-else>✨ Créer l'événement</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Modal de recadrage -->
        <div v-if="showCropModal" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-2xl shadow-2xl">
                <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">
                    📐 Recadrer l'image
                </h3>

                <div class="mb-6 max-h-[60vh] bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                    <Cropper ref="cropperRef" class="max-w-full h-[400px] mx-auto" :src="imagePreviewUrl"
                        :stencil-props="{ aspectRatio: 16 / 9 }" />
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="cancelCrop" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 
                               rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Annuler
                    </button>
                    <button type="button" @click="validateCrop"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        ✓ Appliquer
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>