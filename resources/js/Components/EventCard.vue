<script setup>
import { Link } from '@inertiajs/vue3'
import { useDateFormat } from '@vueuse/core'
import { computed } from 'vue'

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    showActions: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['register'])

// Formatage des dates
const formatDate = (date) => {
    return useDateFormat(date, 'DD MMM YYYY', { locales: 'fr' }).value
}

const formatTime = (date) => {
    return useDateFormat(date, 'HH:mm').value
}

// Couleurs selon la catégorie
const getCategoryColor = (category) => {
    const colors = {
        'competition': 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
        'entrainement': 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
        'manifestation': 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400'
    }
    return colors[category] || 'bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400'
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

// Status de l'inscription
const registrationStatusText = computed(() => {
    if (!props.event.registration_status) return null

    const { can_register, reason } = props.event.registration_status

    if (can_register) return null

    const messages = {
        'registration_not_open': 'Inscriptions pas encore ouvertes',
        'registration_closed': 'Inscriptions fermées',
        'event_started': 'Événement commencé',
        'already_registered': 'Déjà inscrit',
        'event_full': 'Complet',
        'members_only': 'Réservé aux adhérents',
        'requires_medical_certificate': 'Certificat médical requis'
    }

    return messages[reason] || 'Inscription impossible'
})

const registrationStatusColor = computed(() => {
    if (!props.event.registration_status) return ''

    const { can_register, reason } = props.event.registration_status

    if (can_register) return 'text-green-600 bg-green-50 border-green-200'

    const colors = {
        'members_only': 'text-orange-600 bg-orange-50 border-orange-200',
        'event_full': 'text-red-600 bg-red-50 border-red-200',
        'registration_closed': 'text-gray-600 bg-gray-50 border-gray-200',
        'already_registered': 'text-blue-600 bg-blue-50 border-blue-200'
    }

    return colors[reason] || 'text-gray-600 bg-gray-50 border-gray-200'
})

// Fonction pour gérer l'inscription
const handleRegistration = () => {
    const { can_register, reason } = props.event.registration_status

    if (reason === 'members_only') {
        // Rediriger vers la page d'adhésion
        window.location.href = route('membership.create')
        return
    }

    if (can_register) {
        // Rediriger vers la page d'inscription
        window.location.href = route('events.registration', props.event.id)
    }
}
</script>

<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
        <!-- Image d'illustration -->
        <div class="relative h-48 bg-gray-200 dark:bg-gray-700 overflow-hidden">
            <img v-if="event.illustration" :src="event.illustration.url" :alt="event.title"
                class="w-full h-full object-cover">
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-4xl">
                {{ getCategoryIcon(event.category) }}
            </div>

            <!-- Badge catégorie -->
            <div class="absolute top-3 left-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getCategoryColor(event.category)">
                    {{ getCategoryIcon(event.category) }} {{ event.category }}
                </span>
            </div>

            <!-- Prix -->
            <div v-if="event.price" class="absolute top-3 right-3">
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
                    <span v-if="event.price > 0">{{ event.price }}€</span>
                    <span v-else class="text-green-600">Gratuit</span>
                </span>
            </div>
        </div>

        <!-- Contenu -->
        <div class="p-6">
            <!-- Titre et description -->
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                    {{ event.title }}
                </h3>
                <p v-if="event.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3">
                    {{ event.description }}
                </p>
            </div>

            <!-- Informations principales -->
            <div class="space-y-2 mb-4">
                <!-- Date -->
                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ formatDate(event.start_date) }}</span>
                    <span v-if="event.end_date && event.end_date !== event.start_date" class="ml-1">
                        - {{ formatDate(event.end_date) }}
                    </span>
                </div>

                <!-- Lieu -->
                <div v-if="event.address" class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ event.address.city }}</span>
                </div>

                <!-- Organisateur -->
                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>{{ event.organizer.firstname }} {{ event.organizer.lastname }}</span>
                </div>

                <!-- Participants -->
                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>
                        {{ event.participants_count }} participant{{ event.participants_count > 1 ? 's' : '' }}
                        <span v-if="event.max_participants">/ {{ event.max_participants }}</span>
                    </span>
                </div>
            </div>

            <!-- Badges d'information -->
            <div class="flex flex-wrap gap-2 mb-4">
                <span v-if="event.members_only"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-400">
                    👥 Adhérents uniquement
                </span>
                <span v-if="event.requires_medical_certificate"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400">
                    🏥 Certificat médical requis
                </span>
            </div>

            <!-- Status d'inscription -->
            <div v-if="registrationStatusText" class="mb-4">
                <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium border"
                    :class="registrationStatusColor">
                    {{ registrationStatusText }}
                </span>
            </div>

            <!-- Actions -->
            <div v-if="showActions" class="flex justify-between items-center">
                <Link :href="route('events.show', event.id)"
                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 text-sm font-medium">
                Voir les détails →
                </Link>

                <div class="flex space-x-2">
                    <!-- Bouton d'inscription -->
                    <button v-if="!event.is_registered" @click="handleRegistration"
                        :disabled="!event.registration_status?.can_register && event.registration_status?.reason !== 'members_only'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors" :class="event.registration_status?.can_register
                            ? 'bg-blue-600 text-white hover:bg-blue-700'
                            : event.registration_status?.reason === 'members_only'
                                ? 'bg-orange-600 text-white hover:bg-orange-700'
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'">
                        <span v-if="event.registration_status?.reason === 'members_only'">
                            Devenir adhérent
                        </span>
                        <span v-else-if="event.registration_status?.can_register">
                            S'inscrire
                        </span>
                        <span v-else>
                            {{ registrationStatusText }}
                        </span>
                    </button>

                    <!-- Statut inscrit -->
                    <span v-else
                        class="px-4 py-2 rounded-lg text-sm font-medium bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400">
                        ✓ Inscrit
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>