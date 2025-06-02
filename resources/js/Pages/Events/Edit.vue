<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    today: {
        type: String,
        required: true
    }
})

// Calculer le minimum pour max_participants
const minParticipants = computed(() => {
    return props.event.current_participants_count || 1
})

// Message d'aide pour l'utilisateur
const participantsHelpText = computed(() => {
    if (props.event.current_participants_count > 0) {
        return `Minimum ${props.event.current_participants_count} (nombre actuel d'inscrits)`
    }
    return 'Laissez vide pour un nombre illimité'
})

const form = useForm({
    title: props.event.title || '',
    category: props.event.category || '',
    description: props.event.description || '',
    start_date: props.event.start_date || '',
    end_date: props.event.end_date || '',
    registration_open: props.event.registration_open || '',
    registration_close: props.event.registration_close || '',
    max_participants: props.event.max_participants || '',
    members_only: props.event.members_only || false,
    requires_medical_certificate: props.event.requires_medical_certificate || false,
    price: props.event.price || '',
    image: null,
    address: props.event.address?.address || '',
    city: props.event.address?.city || '',
    postal_code: props.event.address?.postal_code || '',
    country: props.event.address?.country || 'France',
})

const imagePreview = ref(props.event.illustration?.url || null)
const fileInput = ref(null)

const categories = [
    { value: 'competition', label: '🏆 Compétition', color: 'text-red-600' },
    { value: 'entrainement', label: '💪 Entraînement', color: 'text-blue-600' },
    { value: 'manifestation', label: '🎉 Manifestation', color: 'text-green-600' }
]

const selectedCategoryInfo = computed(() => {
    return categories.find(cat => cat.value === form.category) || categories[0]
})

const handleImageChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        form.image = file
        const reader = new FileReader()
        reader.onload = (e) => {
            imagePreview.value = e.target.result
        }
        reader.readAsDataURL(file)
    }
}

const removeImage = () => {
    form.image = null
    imagePreview.value = null
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const submit = () => {
    // Validation côté client avant envoi
    if (props.event.current_participants_count > 0 &&
        form.max_participants &&
        form.max_participants < props.event.current_participants_count) {

        alert(`Erreur : Le nombre maximum de participants (${form.max_participants}) ne peut pas être inférieur au nombre actuel d'inscrits (${props.event.current_participants_count}).`)
        return
    }

    form.put(route('events.update', props.event.id), {
        onSuccess: () => {
            // Redirection gérée par le contrôleur
        }
    })
}
</script>

<template>

    <Head title="Modifier l'événement" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Modifier l'événement
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Modifiez les informations de votre événement
                    </p>
                </div>
                <Link :href="route('events.show', event.id)"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium">
                Retour à l'événement
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">

                        <!-- Avertissement sur les limitations d'édition -->
                        <div v-if="event.current_participants_count > 0"
                            class="bg-amber-50 dark:bg-amber-900/50 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                        Modification avec participants inscrits
                                    </h3>
                                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                        Cet événement a déjà <strong>{{ event.current_participants_count }}
                                            participant{{
                                                event.current_participants_count > 1 ? 's' : '' }} inscrit{{
                                                event.current_participants_count > 1 ? 's' : '' }}</strong>.
                                        Certaines modifications sont limitées pour éviter les conflits.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-if="Object.keys(form.errors).length > 0"
                            class="bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                        Veuillez corriger les erreurs suivantes :
                                    </h3>
                                    <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                                        <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Informations principales -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Titre -->
                            <div class="md:col-span-2">
                                <label for="title"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Titre de l'événement *
                                </label>
                                <input id="title" v-model="form.title" type="text" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="Nom de votre événement" />
                            </div>

                            <!-- Catégorie -->
                            <div>
                                <label for="category"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Catégorie *
                                </label>
                                <select id="category" v-model="form.category" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Choisir une catégorie</option>
                                    <option v-for="category in categories" :key="category.value"
                                        :value="category.value">
                                        {{ category.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Prix -->
                            <div>
                                <label for="price"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Prix
                                </label>
                                <input id="price" v-model="form.price" type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="Ex: 15€, Gratuit, Prix libre" />
                            </div>

                            <!-- Date de début -->
                            <div>
                                <label for="start_date"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Date de début *
                                </label>
                                <input id="start_date" v-model="form.start_date" type="date" :min="today" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" />
                            </div>

                            <!-- Date de fin -->
                            <div>
                                <label for="end_date"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Date de fin *
                                </label>
                                <input id="end_date" v-model="form.end_date" type="date" :min="form.start_date || today"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" />
                            </div>

                            <!-- Ouverture des inscriptions -->
                            <div>
                                <label for="registration_open"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Ouverture des inscriptions *
                                </label>
                                <input id="registration_open" v-model="form.registration_open" type="date"
                                    :max="form.registration_close" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" />
                            </div>

                            <!-- Fermeture des inscriptions -->
                            <div>
                                <label for="registration_close"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Fermeture des inscriptions *
                                </label>
                                <input id="registration_close" v-model="form.registration_close" type="date"
                                    :min="form.registration_open" :max="form.start_date" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" />
                            </div>

                            <!-- Nombre maximum de participants -->
                            <div>
                                <label for="max_participants"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nombre maximum de participants
                                </label>
                                <input id="max_participants" v-model="form.max_participants" type="number"
                                    :min="minParticipants"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    :placeholder="participantsHelpText" />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ participantsHelpText }}
                                </p>
                                <div v-if="event.current_participants_count > 0"
                                    class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                                    📊 Actuellement {{ event.current_participants_count }} personne{{
                                        event.current_participants_count > 1 ? 's' : '' }} inscrite{{
                                        event.current_participants_count > 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description
                            </label>
                            <textarea id="description" v-model="form.description" rows="4"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                placeholder="Décrivez votre événement, les activités prévues, ce qu'il faut apporter..."></textarea>
                        </div>

                        <!-- Image d'illustration -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Image d'illustration
                            </label>

                            <!-- Aperçu de l'image actuelle ou nouvelle -->
                            <div v-if="imagePreview" class="mb-4">
                                <div class="relative inline-block">
                                    <img :src="imagePreview" alt="Aperçu"
                                        class="h-32 w-48 object-cover rounded-lg border border-gray-300 dark:border-gray-600">
                                    <button type="button" @click="removeImage"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
                                        ✕
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ form.image ? 'Nouvelle image sélectionnée' : 'Image actuelle' }}
                                </p>
                            </div>

                            <input ref="fileInput" type="file" accept="image/*" @change="handleImageChange"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                PNG, JPG, GIF jusqu'à 2MB
                            </p>
                        </div>

                        <!-- Adresse -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Lieu de l'événement</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Adresse -->
                                <div class="md:col-span-2">
                                    <label for="address"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Adresse
                                    </label>
                                    <input id="address" v-model="form.address" type="text"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                        placeholder="123 rue de la République" />
                                </div>

                                <!-- Ville -->
                                <div>
                                    <label for="city"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Ville
                                    </label>
                                    <input id="city" v-model="form.city" type="text"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                        placeholder="Paris" />
                                </div>

                                <!-- Code postal -->
                                <div>
                                    <label for="postal_code"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Code postal
                                    </label>
                                    <input id="postal_code" v-model="form.postal_code" type="text"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                        placeholder="75001" />
                                </div>

                                <!-- Pays -->
                                <div class="md:col-span-2">
                                    <label for="country"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Pays
                                    </label>
                                    <input id="country" v-model="form.country" type="text"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                        placeholder="France" />
                                </div>
                            </div>
                        </div>

                        <!-- Options avancées -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Options</h3>

                            <div class="space-y-4">
                                <!-- Réservé aux adhérents -->
                                <div class="flex items-center">
                                    <input id="members_only" v-model="form.members_only" type="checkbox"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                                    <label for="members_only" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                        Réservé aux adhérents uniquement
                                    </label>
                                </div>

                                <!-- Certificat médical requis -->
                                <div class="flex items-center">
                                    <input id="requires_medical_certificate" v-model="form.requires_medical_certificate"
                                        type="checkbox"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                                    <label for="requires_medical_certificate"
                                        class="ml-2 block text-sm text-gray-900 dark:text-white">
                                        Certificat médical obligatoire
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div
                            class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('events.show', event.id)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Annuler
                            </Link>

                            <button type="submit" :disabled="form.processing"
                                class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                {{ form.processing ? 'Modification...' : 'Modifier l\'événement' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>