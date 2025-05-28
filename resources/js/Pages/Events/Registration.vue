<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useDateFormat } from '@vueuse/core'

const props = defineProps({
    event: {
        type: Object,
        required: true
    },

    medical_certificates: {
        type: Array,
        default: () => []
    }
})

const user = usePage().props.auth.user;
// États
const showCertificateModal = ref(false)
const showUploadModal = ref(false)
const selectedCertificate = ref(null)

// Formulaire d'inscription
const form = useForm({
    firstname: user.firstname || '',
    lastname: user.lastname || '',
    email: user.email || '',
    phone: user.phone || '',
    birth_date: user.birth_date || '',
    address: user.address ?
        `${user.address.house_number || ''} ${user.address.street_name || ''}`.trim() : '',
    city: user.address?.city || '',
    postal_code: user.address?.postal_code || '',

    medical_certificate_id: null,
})

// Formulaire d'upload de certificat
const uploadForm = useForm({
    title: '',
    file: null,
    expires_at: ''
})

// Computed
const formatDate = (date) => {
    return useDateFormat(date, 'DD MMMM YYYY', { locales: 'fr' }).value
}

const formatDateTime = (date) => {
    return useDateFormat(date, 'DD/MM/YYYY à HH:mm').value
}

const isFormValid = computed(() => {
    const required = [
        form.firstname, form.lastname, form.email, form.phone,
        form.birth_date,
    ]

    const hasRequiredFields = required.every(field => field && field.trim())

    if (!props.event.requires_medical_certificate) {
        return hasRequiredFields
    }

    return hasRequiredFields && form.medical_certificate_id
})

// Méthodes
const selectCertificate = (certificate) => {
    selectedCertificate.value = certificate
    form.medical_certificate_id = certificate.id
    showCertificateModal.value = false
}

const handleFileUpload = (event) => {
    uploadForm.file = event.target.files[0]
}

const uploadCertificate = () => {
    uploadForm.post(route('certificats.store'), {
        onSuccess: () => {
            showUploadModal.value = false
            uploadForm.reset()
            // Recharger la page pour avoir le nouveau certificat
            window.location.reload()
        },
        onError: (errors) => {
            console.error('Erreur upload:', errors)
        }
    })
}

const submitRegistration = () => {
    form.post(route('events.register', props.event.id), {
        onSuccess: () => {
            console.log('Inscription réussie')
        },
        onError: (errors) => {
            console.error('Erreur inscription:', errors)
        }
    })
}

const getCertificateStatusColor = (certificate) => {
    if (!certificate.expiration_date) return 'text-green-600'

    const expiry = new Date(certificate.expiration_date)
    const now = new Date()
    const daysUntilExpiry = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24))

    if (daysUntilExpiry < 30) return 'text-orange-600'
    return 'text-green-600'
}
</script>

<template>

    <Head :title="`Inscription - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Inscription à l'événement
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ event.title }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">

                <!-- Informations de l'événement -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-8">
                    <div class="flex items-start space-x-6">
                        <div v-if="event.illustration" class="flex-shrink-0">
                            <img :src="event.illustration.url" :alt="event.title"
                                class="w-24 h-24 object-cover rounded-lg">
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                {{ event.title }}
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Date :</span>
                                    {{ formatDate(event.start_date) }}
                                    <span v-if="event.end_date && event.end_date !== event.start_date">
                                        au {{ formatDate(event.end_date) }}
                                    </span>
                                </div>
                                <div v-if="event.address">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Lieu :</span>
                                    {{ event.address.street_name }}, {{ event.address.city }}
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Catégorie :</span>
                                    <span class="capitalize">{{ event.category }}</span>
                                </div>
                                <div v-if="event.price > 0">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Prix :</span>
                                    <span class="text-lg font-bold text-green-600">{{ event.real_price }}€</span>
                                </div>
                                <div v-else>
                                    <span class="font-medium text-green-600">Gratuit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire d'inscription -->
                <form @submit.prevent="submitRegistration" class="space-y-8">

                    <!-- Informations personnelles -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                            Informations personnelles
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Prénom *
                                </label>
                                <input v-model="form.firstname" type="text" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <p v-if="form.errors.firstname" class="mt-1 text-red-600 text-sm">{{
                                    form.errors.firstname }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nom *
                                </label>
                                <input v-model="form.lastname" type="text" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <p v-if="form.errors.lastname" class="mt-1 text-red-600 text-sm">{{ form.errors.lastname
                                    }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Email *
                                </label>
                                <input v-model="form.email" type="email" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <p v-if="form.errors.email" class="mt-1 text-red-600 text-sm">{{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Téléphone *
                                </label>
                                <input v-model="form.phone" type="tel" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <p v-if="form.errors.phone" class="mt-1 text-red-600 text-sm">{{ form.errors.phone }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Date de naissance *
                                </label>
                                <input v-model="form.birth_date" type="date" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <p v-if="form.errors.birth_date" class="mt-1 text-red-600 text-sm">{{
                                    form.errors.birth_date }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Adresse
                                </label>
                                <input v-model="form.address" type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Ville
                                </label>
                                <input v-model="form.city" type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Code postal
                                </label>
                                <input v-model="form.postal_code" type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <!-- Certificat médical (si requis) -->
                    <div v-if="event.requires_medical_certificate"
                        class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                            Certificat médical requis
                        </h3>

                        <div v-if="selectedCertificate"
                            class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-green-800 dark:text-green-200">{{
                                        selectedCertificate.title }}
                                    </h4>
                                    <p class="text-sm text-green-600 dark:text-green-400">
                                        Expire le {{ formatDate(selectedCertificate.expiration_date) }}
                                    </p>
                                </div>
                                <button type="button"
                                    @click="selectedCertificate = null; form.medical_certificate_id = null"
                                    class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-else class="space-y-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Cet événement nécessite un certificat médical valide. Veuillez sélectionner un
                                certificat
                                existant ou en déposer un nouveau.
                            </p>

                            <div v-if="medical_certificates.length > 0" class="space-y-2">
                                <button type="button" @click="showCertificateModal = true"
                                    class="w-full flex items-center justify-center px-4 py-3 border border-blue-300 rounded-lg text-blue-600 hover:bg-blue-50 dark:border-blue-600 dark:text-blue-400 dark:hover:bg-blue-900/20 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Sélectionner un certificat existant ({{ medical_certificates.length }} disponible{{
                                        medical_certificates.length > 1 ? 's' : '' }})
                                </button>
                            </div>

                            <button type="button" @click="showUploadModal = true"
                                class="w-full flex items-center justify-center px-4 py-3 border border-green-300 rounded-lg text-green-600 hover:bg-green-50 dark:border-green-600 dark:text-green-400 dark:hover:bg-green-900/20 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                Déposer un nouveau certificat
                            </button>
                        </div>

                        <p v-if="form.errors.medical_certificate_id" class="mt-2 text-red-600 text-sm">{{
                            form.errors.medical_certificate_id }}</p>
                    </div>

                    <!-- Bouton de soumission -->
                    <div class="flex justify-end">
                        <button type="submit" :disabled="!isFormValid || form.processing"
                            class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium text-lg">
                            <span v-if="form.processing">⏳ Traitement...</span>
                            <span v-else-if="event.price > 0">💳 Passer au paiement ({{ event.real_price }}€)</span>
                            <span v-else>✅ S'inscrire gratuitement</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal de sélection de certificat -->
        <div v-if="showCertificateModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Sélectionner un certificat médical
                    </h3>
                    <button @click="showCertificateModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <div v-for="certificate in medical_certificates" :key="certificate.id"
                        @click="selectCertificate(certificate)"
                        class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ certificate.title }}</h4>
                                <p class="text-sm mt-1" :class="getCertificateStatusColor(certificate)">
                                    <span v-if="certificate.expiration_date">
                                        Expire le {{ formatDate(certificate.expiration_date) }}
                                    </span>
                                    <span v-else>Certificat permanent</span>
                                </p>
                            </div>
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal d'upload de certificat -->
        <div v-if="showUploadModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-lg">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Déposer un certificat médical
                    </h3>
                    <button @click="showUploadModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="uploadCertificate" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Titre du certificat *
                        </label>
                        <input v-model="uploadForm.title" type="text" required
                            placeholder="Ex: Certificat médical tennis 2024"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        <p v-if="uploadForm.errors.title" class="mt-1 text-red-600 text-sm">{{ uploadForm.errors.title
                            }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Fichier *
                        </label>
                        <input type="file" @change="handleFileUpload" required accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500">Formats acceptés : PDF, JPG, PNG (max 10 Mo)</p>
                        <p v-if="uploadForm.errors.file" class="mt-1 text-red-600 text-sm">{{ uploadForm.errors.file }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Date d'expiration *
                        </label>
                        <input v-model="uploadForm.expires_at" type="date" required
                            :min="new Date().toISOString().split('T')[0]"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        <p v-if="uploadForm.errors.expires_at" class="mt-1 text-red-600 text-sm">{{
                            uploadForm.errors.expires_at
                            }}</p>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" @click="showUploadModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Annuler
                        </button>
                        <button type="submit" :disabled="uploadForm.processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
                            {{ uploadForm.processing ? 'Upload...' : 'Déposer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>