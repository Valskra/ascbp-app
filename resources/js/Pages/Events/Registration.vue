<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, usePage, Link } from '@inertiajs/vue3'
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

// Formatage de la date de naissance pour l'input date HTML
const formatDateForInput = (date) => {
    if (!date) return ''
    const d = new Date(date)
    return d.toISOString().split('T')[0] // Format YYYY-MM-DD
}

// Formulaire d'inscription simplifié - on ne garde que les champs manquants
const form = useForm({
    // Champs requis seulement si manquants chez l'utilisateur
    firstname: user.firstname || '',
    lastname: user.lastname || '',
    email: user.email || '',
    phone: user.phone || '',
    birth_date: formatDateForInput(user.birth_date),

    // Adresse optionnelle - on reconstruit depuis l'adresse utilisateur
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
    return useDateFormat(date, 'DD/MM/YYYY à HH:mm', { locales: 'fr' }).value
}

// Calcul du prix d'affichage
const displayPrice = computed(() => {
    if (!props.event.price || props.event.price === 0) return 'Gratuit'
    return `${props.event.real_price || props.event.price}€`
})

// Couleurs selon la catégorie
const getCategoryColor = (category) => {
    const colors = {
        'competition': 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
        'entrainement': 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
        'manifestation': 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800'
    }
    return colors[category] || 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:border-gray-800'
}

// Icônes selon la catégorie
const getCategoryIcon = (category) => {
    const icons = {
        'competition': '🏆',
        'entrainement': '💪',
        'manifestation': '🎉'
    }
    return icons[category] || '📅'
}

// Validation du formulaire
const isFormValid = computed(() => {
    const required = [form.firstname, form.lastname, form.email, form.phone, form.birth_date]
    const hasRequiredFields = required.every(field => field && field.trim())

    if (!props.event.requires_medical_certificate) {
        return hasRequiredFields
    }

    return hasRequiredFields && form.medical_certificate_id
})

// Textes localisés
const texts = {
    backToEvents: 'Retour aux l\'événements',
    registrationTitle: 'Inscription à',
    personalInfoComplete: 'Informations personnelles complètes',
    personalInfoIncomplete: 'Informations personnelles',
    completeDescription: 'Complétez les informations manquantes pour finaliser votre inscription',
    usingProfileInfo: 'Nous utiliserons les informations de votre profil',
    medicalCertRequired: 'Certificat médical requis',
    medicalCertDescription: 'Cet événement nécessite un certificat médical valide',
    participationConditions: 'Conditions de participation',
    membershipRequired: 'Adhésion à l\'association requise',
    medicalCertMandatory: 'Certificat médical valide obligatoire',
    finalizeRegistration: 'Finaliser l\'inscription',
    event: 'Événement',
    price: 'Prix',
    processingInProgress: 'Traitement en cours...',
    confirmFreeRegistration: 'Confirmer l\'inscription gratuite',
    proceedToPayment: 'Procéder au paiement',
    confirmRegistration: 'En cliquant sur ce bouton, vous confirmez votre inscription à cet événement.',
    paymentRedirect: 'Vous serez redirigé vers notre plateforme de paiement sécurisée.',
    selectMedicalCert: 'Sélectionner un certificat médical',
    chooseCertificate: 'Choisir un certificat',
    uploadNewCert: 'Déposer un nouveau certificat',
    uploadMedicalCert: 'Déposer un certificat médical',
    certTitle: 'Titre du certificat',
    certTitlePlaceholder: 'Ex: Certificat médical tennis 2024',
    file: 'Fichier',
    acceptedFormats: 'Formats acceptés : PDF, JPG, PNG (max 10 Mo)',
    expirationDate: 'Date d\'expiration',
    cancel: 'Annuler',
    uploadInProgress: 'Dépôt en cours...',
    uploadCertificate: 'Déposer le certificat',
    expires: 'Expire le',
    permanentCert: 'Certificat permanent',
    free: 'Gratuit'
}
const missingUserFields = computed(() => {
    const missing = []
    if (!user.firstname) missing.push('firstname')
    if (!user.lastname) missing.push('lastname')
    if (!user.email) missing.push('email')
    if (!user.phone) missing.push('phone')
    if (!user.birth_date) missing.push('birth_date')
    return missing
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
            window.location.reload()
        },
        onError: (errors) => {
            console.error('Erreur upload:', errors)
        }
    })
}

const submitRegistration = () => {
    form.post(route('events.register', props.event.id), {
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

// Gestion de la date d'affichage
const displayDate = computed(() => {
    if (!props.event.start_date) return ''

    const start = formatDate(props.event.start_date)
    const end = props.event.end_date ? formatDate(props.event.end_date) : null

    if (end && end !== start) {
        return { single: false, start, end }
    }

    return { single: true, date: start }
})

// Construction de l'adresse complète de l'événement
const fullAddress = computed(() => {
    if (!props.event.address) return null

    const parts = []
    if (props.event.address.house_number) parts.push(props.event.address.house_number)
    if (props.event.address.street_name) parts.push(props.event.address.street_name)

    const street = parts.join(' ')
    const cityPostal = [props.event.address.city, props.event.address.postal_code].filter(Boolean).join(' ')

    return {
        street: street || null,
        cityPostal: cityPostal || null,
        country: props.event.address.country || null
    }
})
</script>

<template>

    <Head :title="`Inscription - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('events.index')"
                        class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ texts.backToEvents }}
                    </Link>

                    <div class="text-gray-300 dark:text-gray-600">•</div>

                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border"
                        :class="getCategoryColor(event.category)">
                        <span class="mr-1">{{ getCategoryIcon(event.category) }}</span>
                        <span class="capitalize">{{ event.category }}</span>
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

                <!-- En-tête avec résumé de l'événement -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
                    <!-- Image d'illustration réduite -->
                    <div class="relative h-48 overflow-hidden">
                        <!-- Image si présente -->
                        <img v-if="event.illustration?.url" :src="event.illustration.url" :alt="event.title"
                            class="w-full h-full object-cover">

                        <!-- Motif géométrique si pas d'image -->
                        <div v-else class="w-full h-full relative" :class="{
                            'bg-gradient-to-br from-red-200 via-red-300 to-red-500 dark:from-red-800 dark:via-red-700 dark:to-red-600': event.category === 'competition',
                            'bg-gradient-to-br from-blue-200 via-blue-300 to-blue-500 dark:from-blue-800 dark:via-blue-700 dark:to-blue-600': event.category === 'entrainement',
                            'bg-gradient-to-br from-green-200 via-green-300 to-green-500 dark:from-green-800 dark:via-green-700 dark:to-green-600': event.category === 'manifestation',
                            'bg-gradient-to-br from-gray-200 via-gray-300 to-gray-500 dark:from-gray-800 dark:via-gray-700 dark:to-gray-600': !event.category || !['competition', 'entrainement', 'manifestation'].includes(event.category)
                        }">
                            <!-- Motifs géométriques simplifiés -->
                            <svg class="absolute inset-0 w-full h-full opacity-20" viewBox="0 0 400 200"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="60" cy="60" r="40" fill="none" stroke="currentColor" stroke-width="2"
                                    opacity="0.6" />
                                <polygon points="300,30 320,70 280,70" fill="currentColor" opacity="0.3" />
                                <line x1="0" y1="150" x2="120" y2="200" stroke="currentColor" stroke-width="2"
                                    opacity="0.4" />
                                <circle cx="200" cy="100" r="3" fill="currentColor" opacity="0.5" />
                                <rect x="150" y="120" width="30" height="15" fill="none" stroke="currentColor"
                                    stroke-width="1" opacity="0.3" />
                            </svg>
                        </div>
                    </div>

                    <!-- Résumé de l'événement -->
                    <div class="p-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            {{ texts.registrationTitle }} {{ event.title }}
                        </h1>

                        <div class="grid md:grid-cols-3 gap-4 text-sm">
                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <div v-if="displayDate.single">{{ displayDate.date }}</div>
                                <div v-else>Du {{ displayDate.start }} au {{ displayDate.end }}</div>
                            </div>

                            <div v-if="fullAddress" class="flex items-center text-gray-600 dark:text-gray-400">
                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ fullAddress.cityPostal ? fullAddress.cityPostal : fullAddress.country }}
                            </div>

                            <div v-if="event.organizer" class="flex items-center text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ event.organizer.firstname }} {{ event.organizer.lastname }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire d'inscription -->
                <form @submit.prevent="submitRegistration" class="space-y-6">

                    <!-- Informations personnelles (seulement si nécessaires) -->
                    <div v-if="missingUserFields.length > 0"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-lg mr-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Informations personnelles
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Complétez les informations manquantes pour finaliser votre inscription
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div v-if="missingUserFields.includes('firstname')">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Prénom *
                                </label>
                                <input v-model="form.firstname" type="text" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <p v-if="form.errors.firstname" class="mt-1 text-red-600 text-sm">{{
                                    form.errors.firstname }}
                                </p>
                            </div>

                            <div v-if="missingUserFields.includes('lastname')">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nom *
                                </label>
                                <input v-model="form.lastname" type="text" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <p v-if="form.errors.lastname" class="mt-1 text-red-600 text-sm">{{ form.errors.lastname
                                    }}</p>
                            </div>

                            <div v-if="missingUserFields.includes('email')">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Email *
                                </label>
                                <input v-model="form.email" type="email" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <p v-if="form.errors.email" class="mt-1 text-red-600 text-sm">{{ form.errors.email }}
                                </p>
                            </div>

                            <div v-if="missingUserFields.includes('phone')">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Téléphone *
                                </label>
                                <input v-model="form.phone" type="tel" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <p v-if="form.errors.phone" class="mt-1 text-red-600 text-sm">{{ form.errors.phone }}
                                </p>
                            </div>

                            <div v-if="missingUserFields.includes('birth_date')" class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Date de naissance *
                                </label>
                                <input v-model="form.birth_date" type="date" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <p v-if="form.errors.birth_date" class="mt-1 text-red-600 text-sm">{{
                                    form.errors.birth_date }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Message si toutes les infos sont disponibles -->
                    <div v-else
                        class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6">
                        <div class="flex items-center">
                            <div class="bg-green-100 dark:bg-green-900/30 p-2 rounded-lg mr-3">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-green-900 dark:text-green-200">Informations personnelles
                                    complètes
                                </h3>
                                <p class="text-sm text-green-700 dark:text-green-300">
                                    Nous utiliserons les informations de votre profil : {{ user.firstname }} {{
                                        user.lastname }}
                                    ({{ user.email }})
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Certificat médical (si requis) -->
                    <div v-if="event.requires_medical_certificate"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-lg mr-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v8a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm8 2a1 1 0 00-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2v-2z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Certificat médical requis
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Cet événement nécessite un certificat médical valide
                                </p>
                            </div>
                        </div>

                        <div v-if="selectedCertificate"
                            class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <h4 class="font-medium text-green-800 dark:text-green-200">{{
                                            selectedCertificate.title
                                            }}</h4>
                                        <p class="text-sm text-green-600 dark:text-green-400">
                                            <span v-if="selectedCertificate.expiration_date">
                                                Expire le {{ formatDate(selectedCertificate.expiration_date) }}
                                            </span>
                                            <span v-else>Certificat permanent</span>
                                        </p>
                                    </div>
                                </div>
                                <button type="button"
                                    @click="selectedCertificate = null; form.medical_certificate_id = null"
                                    class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 p-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-else class="space-y-4">
                            <div class="grid gap-3 md:grid-cols-2">
                                <button v-if="medical_certificates.length > 0" type="button"
                                    @click="showCertificateModal = true"
                                    class="flex items-center justify-center px-4 py-3 border border-blue-300 dark:border-blue-600 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Choisir un certificat ({{ medical_certificates.length }})
                                </button>

                                <button type="button" @click="showUploadModal = true"
                                    class="flex items-center justify-center px-4 py-3 border border-green-300 dark:border-green-600 rounded-lg text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    Déposer un nouveau certificat
                                </button>
                            </div>
                        </div>

                        <p v-if="form.errors.medical_certificate_id" class="mt-2 text-red-600 text-sm">{{
                            form.errors.medical_certificate_id }}</p>
                    </div>

                    <!-- Conditions de participation -->
                    <div v-if="event.members_only || event.requires_medical_certificate"
                        class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mr-3 mt-0.5 flex-shrink-0"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div>
                                <h3 class="font-medium text-amber-900 dark:text-amber-200 mb-2">Conditions de
                                    participation</h3>
                                <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1">
                                    <li v-if="event.members_only" class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                        </svg>
                                        Adhésion à l'association requise
                                    </li>
                                    <li v-if="event.requires_medical_certificate" class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v8a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm8 2a1 1 0 00-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2v-2z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Certificat médical valide obligatoire
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton de soumission -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Finaliser l'inscription
                            </h3>

                            <div class="space-y-4">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Événement :</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ event.title }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm mt-2">
                                        <span class="text-gray-600 dark:text-gray-400">Prix :</span>
                                        <span class="font-bold text-lg"
                                            :class="displayPrice === 'Gratuit' ? 'text-green-600' : 'text-blue-600'">
                                            {{ displayPrice }}
                                        </span>
                                    </div>
                                </div>

                                <button type="submit" :disabled="!isFormValid || form.processing"
                                    class="w-full flex items-center justify-center px-8 py-4 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold text-lg rounded-lg transition-all duration-200 shadow-sm hover:shadow-md disabled:shadow-none">

                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>

                                    <svg v-else-if="displayPrice === 'Gratuit'" class="w-5 h-5 mr-2" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>

                                    <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>

                                    <span v-if="form.processing">Traitement en cours...</span>
                                    <span v-else-if="displayPrice === 'Gratuit'">Confirmer l'inscription gratuite</span>
                                    <span v-else>Procéder au paiement ({{ displayPrice }})</span>
                                </button>

                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    En cliquant sur ce bouton, vous confirmez votre inscription à cet événement.
                                    <span v-if="displayPrice !== 'Gratuit'">
                                        Vous serez redirigé vers notre plateforme de paiement sécurisée.
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal de sélection de certificat -->
        <div v-if="showCertificateModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-2xl max-h-[80vh] overflow-hidden">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Sélectionner un certificat médical
                    </h3>
                    <button @click="showCertificateModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-96">
                    <div class="space-y-3">
                        <div v-for="certificate in medical_certificates" :key="certificate.id"
                            @click="selectCertificate(certificate)"
                            class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ certificate.title }}</h4>
                                    <p class="text-sm mt-1" :class="getCertificateStatusColor(certificate)">
                                        <span v-if="certificate.expiration_date">
                                            Expire le {{ formatDate(certificate.expiration_date) }}
                                        </span>
                                        <span v-else>Certificat permanent</span>
                                    </p>
                                </div>
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal d'upload de certificat -->
        <div v-if="showUploadModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-lg">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Déposer un certificat médical
                    </h3>
                    <button @click="showUploadModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="uploadCertificate" class="p-6 space-y-4">
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
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
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

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" @click="showUploadModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Annuler
                        </button>
                        <button type="submit" :disabled="uploadForm.processing"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg transition-colors">
                            {{ uploadForm.processing ? 'Dépôt en cours...' : 'Déposer le certificat' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>