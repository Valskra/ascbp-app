<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useDateFormat } from '@vueuse/core'
import { computed } from 'vue'

const props = defineProps({
    event: {
        type: Object,
        required: true
    }
})

// Formatage des dates
const formatDate = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'dddd DD MMMM YYYY', { locales: 'fr' }).value
}

const formatTime = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'HH:mm').value
}

const formatDateTime = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'dddd DD MMMM YYYY à HH:mm', { locales: 'fr' }).value
}

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

// Calcul du prix d'affichage
const displayPrice = computed(() => {
    if (!props.event.price) return 'Gratuit'

    const priceMatch = props.event.price.toString().match(/\d+/)
    const numericPrice = priceMatch ? parseInt(priceMatch[0]) : 0

    return numericPrice > 0 ? `${numericPrice}€` : 'Gratuit'
})

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

// Gestion du statut d'inscription
const registrationInfo = computed(() => {
    if (!props.event.can_register) {
        return { canRegister: false, message: 'Inscription fermée', color: 'gray' }
    }

    const messages = {
        'registration_not_open': 'Inscriptions pas encore ouvertes',
        'registration_closed': 'Inscriptions fermées',
        'event_started': 'Événement commencé',
        'already_registered': 'Vous êtes déjà inscrit',
        'event_full': 'Événement complet',
        'members_only': 'Réservé aux adhérents',
        'requires_medical_certificate': 'Certificat médical requis'
    }

    const colors = {
        'members_only': 'orange',
        'event_full': 'red',
        'registration_closed': 'gray',
        'already_registered': 'blue',
        'registration_not_open': 'yellow'
    }

    return {
        canRegister: true,
        message: null,
        color: 'green'
    }
})

const getStatusColor = (color) => {
    const colors = {
        'green': 'text-green-700 bg-green-100 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800',
        'orange': 'text-orange-700 bg-orange-100 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-800',
        'red': 'text-red-700 bg-red-100 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
        'blue': 'text-blue-700 bg-blue-100 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
        'yellow': 'text-yellow-700 bg-yellow-100 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800',
        'gray': 'text-gray-700 bg-gray-100 border-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:border-gray-800'
    }
    return colors[color] || colors.gray
}

// Progression des inscriptions
const registrationProgress = computed(() => {
    if (!props.event.max_participants) return null

    const current = props.event.participants_count || 0
    const max = props.event.max_participants
    const percentage = Math.min(100, (current / max) * 100)

    return { current, max, percentage }
})

// Construction de l'adresse complète
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

    <Head :title="event.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('events.index')"
                        class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux événements
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

                <!-- Messages flash -->
                <div v-if="$page.props.flash?.success"
                    class="mb-6 p-4 bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-green-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-green-800 dark:text-green-200">{{ $page.props.flash.success }}</p>
                    </div>
                </div>

                <div v-if="$page.props.flash?.error"
                    class="mb-6 p-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-red-800 dark:text-red-200">{{ $page.props.flash.error }}</p>
                    </div>
                </div>

                <!-- Contenu principal -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

                    <!-- Image d'illustration -->
                    <div class="relative h-64 md:h-80 overflow-hidden">
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

                            <!-- Motifs géométriques SVG adaptés pour la page de détail -->
                            <svg class="absolute inset-0 w-full h-full opacity-25" viewBox="0 0 400 300"
                                xmlns="http://www.w3.org/2000/svg">
                                <!-- Cercles concentriques plus grands -->
                                <circle cx="80" cy="80" r="60" fill="none" stroke="currentColor" stroke-width="3"
                                    opacity="0.6" />
                                <circle cx="80" cy="80" r="40" fill="none" stroke="currentColor" stroke-width="2"
                                    opacity="0.4" />
                                <circle cx="80" cy="80" r="20" fill="none" stroke="currentColor" stroke-width="1.5"
                                    opacity="0.3" />

                                <!-- Formes géométriques plus complexes -->
                                <polygon points="300,50 330,100 270,100" fill="currentColor" opacity="0.3" />
                                <polygon points="320,130 340,170 300,170" fill="currentColor" opacity="0.2" />
                                <polygon points="280,180 310,220 250,220" fill="currentColor" opacity="0.25" />

                                <!-- Lignes diagonales élégantes -->
                                <line x1="0" y1="200" x2="150" y2="300" stroke="currentColor" stroke-width="3"
                                    opacity="0.4" />
                                <line x1="30" y1="170" x2="180" y2="270" stroke="currentColor" stroke-width="2"
                                    opacity="0.3" />
                                <line x1="60" y1="140" x2="210" y2="240" stroke="currentColor" stroke-width="1.5"
                                    opacity="0.2" />

                                <!-- Hexagones et formes plus sophistiquées -->
                                <polygon points="320,200 340,210 340,230 320,240 300,230 300,210" fill="none"
                                    stroke="currentColor" stroke-width="2" opacity="0.4" />
                                <polygon points="350,160 365,170 365,190 350,200 335,190 335,170" fill="currentColor"
                                    opacity="0.3" />

                                <!-- Éléments décoratifs dispersés -->
                                <circle cx="60" cy="180" r="5" fill="currentColor" opacity="0.5" />
                                <circle cx="140" cy="120" r="3" fill="currentColor" opacity="0.4" />
                                <circle cx="180" cy="70" r="4" fill="currentColor" opacity="0.3" />
                                <circle cx="240" cy="160" r="3" fill="currentColor" opacity="0.6" />
                                <circle cx="200" cy="30" r="6" fill="currentColor" opacity="0.4" />

                                <!-- Lignes de connexion subtiles -->
                                <line x1="60" y1="180" x2="140" y2="120" stroke="currentColor" stroke-width="1"
                                    opacity="0.2" />
                                <line x1="180" y1="70" x2="240" y2="160" stroke="currentColor" stroke-width="1"
                                    opacity="0.15" />

                                <!-- Formes rectangulaires -->
                                <rect x="150" y="180" width="40" height="20" fill="none" stroke="currentColor"
                                    stroke-width="1.5" opacity="0.3" />
                                <rect x="20" y="30" width="25" height="15" fill="currentColor" opacity="0.25" />
                            </svg>

                            <!-- Overlay pour adoucir -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/10 via-transparent to-transparent">
                            </div>
                        </div>

                        <!-- Overlay gradient (seulement si image) -->
                        <div v-if="event.illustration?.url"
                            class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent">
                        </div>

                        <!-- Prix en overlay -->
                        <div class="absolute top-6 right-6">
                            <span
                                class="inline-flex items-center px-4 py-2 rounded-full text-lg font-bold bg-white/90 dark:bg-gray-800/90 text-gray-900 dark:text-white shadow-lg backdrop-blur-sm border border-white/20">
                                {{ displayPrice }}
                            </span>
                        </div>

                        <!-- Badge inscription si inscrit -->
                        <div v-if="event.is_registered" class="absolute bottom-6 right-6">
                            <span
                                class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-500 text-white shadow-lg">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Vous êtes inscrit
                            </span>
                        </div>
                    </div>

                    <!-- Contenu de l'événement -->
                    <div class="p-6 md:p-8">

                        <!-- Titre et description -->
                        <div class="mb-8">
                            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                                {{ event.title }}
                            </h1>
                            <p v-if="event.description"
                                class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                                {{ event.description }}
                            </p>
                        </div>

                        <!-- Informations essentielles -->
                        <h2
                            class="text-xl font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                            Informations
                        </h2>
                        <div class="grid md:grid-cols-2 gap-8 mb-8 mt-4">

                            <!-- Colonne gauche - Informations temporelles -->
                            <div class="space-y-6">

                                <!-- Dates de l'événement -->
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">Date de l'événement</h3>
                                    <div v-if="displayDate.single" class="text-gray-600 dark:text-gray-400">
                                        {{ displayDate.date }}
                                    </div>
                                    <div v-else class="text-gray-600 dark:text-gray-400">
                                        <div>Du {{ displayDate.start }}</div>
                                        <div>au {{ displayDate.end }}</div>
                                    </div>
                                </div>

                                <!-- Horaires -->
                                <div v-if="formatTime(event.start_date)"
                                    class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">Horaires</h3>
                                    <div class="text-gray-600 dark:text-gray-400">
                                        <div>Début : {{ formatTime(event.start_date) }}</div>
                                        <div v-if="event.end_date">Fin : {{ formatTime(event.end_date) }}</div>
                                    </div>
                                </div>

                                <!-- Période d'inscription -->
                                <div v-if="event.registration_open || event.registration_close"
                                    class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">Période d'inscription
                                    </h3>
                                    <div class="text-gray-600 dark:text-gray-400 space-y-1">
                                        <div v-if="event.registration_open">
                                            Ouverture : {{ formatDateTime(event.registration_open) }}
                                        </div>
                                        <div v-if="event.registration_close">
                                            Fermeture : {{ formatDateTime(event.registration_close) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne droite - Informations pratiques -->
                            <div class="space-y-6">


                                <!-- Lieu -->
                                <div v-if="fullAddress" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Lieu
                                    </h3>
                                    <div class="text-gray-600 dark:text-gray-400 space-y-1">
                                        <div v-if="fullAddress.street">{{ fullAddress.street }}</div>
                                        <div v-if="fullAddress.cityPostal">{{ fullAddress.cityPostal }}</div>
                                        <div v-if="fullAddress.country">{{ fullAddress.country }}</div>
                                    </div>
                                </div>

                                <!-- Organisateur -->
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Organisateur
                                    </h3>
                                    <div class="text-gray-600 dark:text-gray-400">
                                        {{ event.organizer.firstname }} {{ event.organizer.lastname }}
                                    </div>
                                </div>

                                <!-- Participants -->
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Participants
                                    </h3>

                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                {{ event.participants_count || 0 }} inscrit{{ (event.participants_count
                                                    || 0)
                                                    !== 1 ? 's' : '' }}
                                            </span>
                                            <span v-if="event.max_participants"
                                                class="text-gray-500 dark:text-gray-500">
                                                / {{ event.max_participants }} max
                                            </span>
                                        </div>

                                        <!-- Barre de progression -->
                                        <div v-if="registrationProgress" class="space-y-2">
                                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                <div class="h-2 rounded-full transition-all duration-300"
                                                    :class="registrationProgress.percentage >= 80 ? 'bg-orange-500' : 'bg-blue-500'"
                                                    :style="{ width: `${registrationProgress.percentage}%` }">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Conditions et badges -->
                        <div v-if="event.members_only || event.requires_medical_certificate" class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">⚠️ Conditions de
                                participation
                            </h2>
                            <div class="grid gap-4 md:grid-cols-2">

                                <div v-if="event.members_only"
                                    class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                                    <div class="flex items-start">
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 mr-3 mt-0.5 flex-shrink-0"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                        </svg>
                                        <div>
                                            <h3 class="font-medium text-purple-900 dark:text-purple-200 mb-1">Adhésion
                                                requise
                                            </h3>
                                            <p class="text-sm text-purple-700 dark:text-purple-300">
                                                Cet événement est réservé aux membres adhérents de l'association.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="event.requires_medical_certificate"
                                    class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                    <div class="flex items-start">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3 mt-0.5 flex-shrink-0"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v8a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm8 2a1 1 0 00-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2v-2z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <div>
                                            <h3 class="font-medium text-blue-900 dark:text-blue-200 mb-1">Certificat
                                                médical
                                                obligatoire</h3>
                                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                                Un certificat médical valide est requis pour participer à cet événement.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bouton d'inscription -->
                        <div v-if="!event.is_registered"
                            class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 text-center">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Participer à cet
                                événement</h2>

                            <div v-if="registrationInfo.canRegister" class="space-y-4">
                                <p class="text-gray-600 dark:text-gray-400">
                                    Vous souhaitez participer à cet événement ? Cliquez sur le bouton ci-dessous pour
                                    vous
                                    inscrire.
                                </p>
                                <Link :href="route('events.registration', event.id)"
                                    class="inline-flex items-center px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                S'inscrire à l'événement
                                </Link>
                            </div>

                            <div v-else class="space-y-4">
                                <div class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium border"
                                    :class="getStatusColor(registrationInfo.color)">
                                    {{ registrationInfo.message }}
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">
                                    Les inscriptions ne sont pas disponibles pour cet événement.
                                </p>
                            </div>
                        </div>

                        <!-- Message si déjà inscrit -->
                        <div v-else
                            class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 text-center">
                            <div
                                class="inline-flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full mb-4">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-green-900 dark:text-green-200 mb-2">Inscription
                                confirmée</h2>
                            <p class="text-green-700 dark:text-green-300">
                                Vous êtes inscrit à cet événement. Nous avons hâte de vous voir !
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>