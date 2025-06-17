<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { useDateFormat } from '@vueuse/core'
import { computed, ref, nextTick } from 'vue'

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

const emit = defineEmits(['register', 'delete'])

const page = usePage()
const showDeleteConfirm = ref(false)
const showFullDescription = ref(false)

const isAuthor = computed(() => {
    const user = page.props.auth?.user
    return user && (user.id === props.event.organizer.id || user.is_admin)
})

const canEdit = computed(() => {
    if (!isAuthor.value) return false
    const now = new Date()
    const eventStart = new Date(props.event.start_date)
    return eventStart > now
})

const canDelete = computed(() => {
    if (!isAuthor.value) return false
    const now = new Date()
    const eventStart = new Date(props.event.start_date)
    const hasParticipants = (props.event.participants_count || 0) > 0
    return eventStart > now && !hasParticipants
})

// Calculer l'état de l'événement
const eventStatus = computed(() => {
    const now = new Date()
    const startDate = new Date(props.event.start_date)
    const endDate = props.event.end_date ? new Date(props.event.end_date) : startDate

    if (startDate > now) {
        return 'upcoming'
    } else if (startDate <= now && endDate >= now) {
        return 'ongoing'
    } else {
        return 'past'
    }
})

// Calculer le temps restant pour les événements à venir
const timeUntilEvent = computed(() => {
    if (eventStatus.value !== 'upcoming') return null

    const now = new Date()
    const startDate = new Date(props.event.start_date)
    const diff = startDate.getTime() - now.getTime()

    const days = Math.floor(diff / (1000 * 60 * 60 * 24))
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))

    if (days > 0) {
        return `Dans ${days} jour${days > 1 ? 's' : ''}`
    } else if (hours > 0) {
        return `Dans ${hours}h`
    } else {
        return 'Bientôt'
    }
})

const formatDate = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'DD/MM/YYYY', { locales: 'fr' }).value
}

const formatTime = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'HH:mm').value
}

const getCategoryColor = (category) => {
    const colors = {
        'competition': 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20',
        'entrainement': 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20',
        'manifestation': 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20'
    }
    return colors[category] || 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20'
}

const getCategoryIcon = (category) => {
    const icons = {
        'competition': '🏆',
        'entrainement': '💪',
        'manifestation': '🎉'
    }
    return icons[category] || '📅'
}

const registrationInfo = computed(() => {
    if (!props.event.registration_status) {
        return { canRegister: false, message: 'Statut inconnu', color: 'gray' }
    }

    const { can_register, reason } = props.event.registration_status

    if (can_register) {
        return { canRegister: true, message: null, color: 'green' }
    }

    const messages = {
        'registration_not_open': 'Inscriptions pas encore ouvertes',
        'registration_closed': 'Inscriptions fermées',
        'event_started': 'Événement commencé',
        'event_finished': 'Événement terminé',
        'already_registered': 'Déjà inscrit',
        'event_full': 'Complet',
        'members_only': 'Réservé aux adhérents',
        'requires_medical_certificate': 'Certificat médical requis'
    }

    const colors = {
        'members_only': 'orange',
        'event_full': 'red',
        'registration_closed': 'gray',
        'event_finished': 'gray',
        'already_registered': 'blue',
        'registration_not_open': 'yellow'
    }

    return {
        canRegister: false,
        message: messages[reason] || 'Inscription impossible',
        color: colors[reason] || 'gray',
        reason
    }
})

const handleRegistration = () => {
    const { reason } = props.event.registration_status || {}

    if (reason === 'members_only') {
        router.visit(route('membership.create'))
        return
    }

    if (registrationInfo.value.canRegister) {
        router.visit(route('events.registration', props.event.id))
    }
}

const handleDelete = () => {
    if (showDeleteConfirm.value) {
        router.delete(route('events.destroy', props.event.id), {
            onSuccess: () => {
                showDeleteConfirm.value = false
            },
            onError: () => {
                showDeleteConfirm.value = false
            }
        })
    } else {
        showDeleteConfirm.value = true
        setTimeout(() => {
            showDeleteConfirm.value = false
        }, 5000)
    }
}

const displayPrice = computed(() => {
    if (!props.event.price) return 'Gratuit'

    const priceMatch = props.event.price.toString().match(/\d+/)
    const numericPrice = priceMatch ? parseInt(priceMatch[0]) : 0

    return numericPrice > 0 ? `${numericPrice}€` : 'Gratuit'
})

const displayDate = computed(() => {
    if (!props.event.start_date) return ''

    const start = formatDate(props.event.start_date)
    const end = props.event.end_date ? formatDate(props.event.end_date) : null

    if (end && end !== start) {
        return `${start} - ${end}`
    }

    return start
})

const hasLocation = computed(() => {
    return props.event.address && (
        props.event.address.city ||
        props.event.address.street_name ||
        props.event.address.postal_code
    )
})

const displayLocation = computed(() => {
    if (!hasLocation.value) return ''

    const parts = []
    if (props.event.address.city) parts.push(props.event.address.city)
    if (props.event.address.postal_code) parts.push(`(${props.event.address.postal_code})`)

    return parts.join(' ')
})

const participantsText = computed(() => {
    const current = props.event.participants_count || 0
    const max = props.event.max_participants

    if (max) {
        return `${current}/${max} participants`
    }

    return `${current} participant${current !== 1 ? 's' : ''}`
})

const toggleDescription = () => {
    showFullDescription.value = !showFullDescription.value

    nextTick(() => {
        setTimeout(() => {
            const event = new CustomEvent('masonry-update', {
                bubbles: true,
                detail: {
                    reason: 'description-toggle',
                    eventId: props.event.id,
                    expanded: showFullDescription.value
                }
            })

            const cardElement = document.querySelector(`[data-event-id="${props.event.id}"]`)
            if (cardElement) {
                cardElement.dispatchEvent(event)
            } else {
                window.dispatchEvent(new Event('resize'))
            }
        }, 0)
    })
}

// Vérifier si on doit afficher le prix
const shouldShowPrice = computed(() => {
    return eventStatus.value === 'upcoming' && !props.event.is_registered
})
</script>

<template>
    <article
        class="event-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-all duration-300 group w-full"
        :class="{
            'opacity-80': eventStatus === 'past'
        }" :data-event-id="event.id">

        <!-- Image d'illustration -->
        <div class="relative overflow-hidden h-40 sm:h-48 md:h-52 lg:h-48">
            <!-- Image si présente -->
            <img v-if="event.illustration?.url" :src="event.illustration.url" :alt="event.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                :class="{ 'grayscale': eventStatus === 'past' }">

            <!-- Motif géométrique si pas d'image -->
            <div v-else class="w-full h-full relative" :class="{
                'bg-gradient-to-br from-red-200 via-red-300 to-red-400 dark:from-red-800 dark:via-red-700 dark:to-red-600': event.category === 'competition',
                'bg-gradient-to-br from-blue-200 via-blue-300 to-blue-400 dark:from-blue-800 dark:via-blue-700 dark:to-blue-600': event.category === 'entrainement',
                'bg-gradient-to-br from-green-200 via-green-300 to-green-400 dark:from-green-800 dark:via-green-700 dark:to-green-600': event.category === 'manifestation',
                'bg-gradient-to-br from-gray-200 via-gray-300 to-gray-400 dark:from-gray-800 dark:via-gray-700 dark:to-gray-600': !event.category || !['competition', 'entrainement', 'manifestation'].includes(event.category)
            }">

                <!-- Motifs géométriques SVG -->
                <svg class="absolute inset-0 w-full h-full opacity-20" viewBox="0 0 200 200"
                    xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="30" fill="none" stroke="currentColor" stroke-width="2" opacity="0.6" />
                    <circle cx="50" cy="50" r="20" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.4" />
                    <circle cx="50" cy="50" r="10" fill="none" stroke="currentColor" stroke-width="1" opacity="0.3" />
                    <polygon points="150,30 170,70 130,70" fill="currentColor" opacity="0.3" />
                    <polygon points="160,90 175,120 145,120" fill="currentColor" opacity="0.2" />
                    <line x1="0" y1="150" x2="80" y2="200" stroke="currentColor" stroke-width="2" opacity="0.4" />
                    <line x1="20" y1="130" x2="100" y2="180" stroke="currentColor" stroke-width="1.5" opacity="0.3" />
                    <line x1="40" y1="110" x2="120" y2="160" stroke="currentColor" stroke-width="1" opacity="0.2" />
                    <polygon points="160,160 175,170 175,190 160,200 145,190 145,170" fill="none" stroke="currentColor"
                        stroke-width="1.5" opacity="0.4" />
                    <circle cx="30" cy="120" r="3" fill="currentColor" opacity="0.5" />
                    <circle cx="70" cy="100" r="2" fill="currentColor" opacity="0.4" />
                    <circle cx="90" cy="40" r="2.5" fill="currentColor" opacity="0.3" />
                    <circle cx="120" cy="80" r="1.5" fill="currentColor" opacity="0.6" />
                </svg>

                <div class="absolute inset-0 bg-gradient-to-t from-black/5 via-transparent to-transparent"></div>
            </div>

            <!-- Overlay gradient -->
            <div v-if="event.illustration?.url"
                class="absolute inset-0 bg-gradient-to-t from-black/10 via-transparent to-transparent"></div>

            <!-- Badge prix en haut à droite de l'image -->
            <div v-if="shouldShowPrice" class="absolute top-3 right-3">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-white/95 dark:bg-gray-800/95 text-gray-900 dark:text-white shadow-md backdrop-blur-sm">
                    {{ displayPrice }}
                </span>
            </div>

            <!-- Badge événement en cours au centre bas -->
            <div v-if="eventStatus === 'ongoing'" class="absolute bottom-3 left-1/2 transform -translate-x-1/2">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-500/90 text-white backdrop-blur-sm shadow-lg animate-pulse">
                    <div class="w-2 h-2 bg-white rounded-full mr-2"></div>
                    En cours
                </span>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="p-5">
            <!-- Badge catégorie en haut à gauche et compte à rebours/prix en haut à droite -->
            <div class="flex justify-between items-start mb-3">
                <!-- Badge catégorie -->
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
                    :class="getCategoryColor(event.category)">
                    <span class="mr-1">{{ getCategoryIcon(event.category) }}</span>
                    <span class="capitalize">{{ event.category }}</span>
                </span>

                <!-- Badge temps restant (haut droite) - seulement si pas de prix affiché -->
                <span v-if="timeUntilEvent && !shouldShowPrice"
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ timeUntilEvent }}
                </span>
            </div>

            <!-- Titre -->
            <h3
                class="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
                {{ event.title }}
            </h3>

            <!-- Description juste après le titre -->
            <div v-if="event.description" class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed"
                    :class="{ 'line-clamp-3': !showFullDescription }">
                    {{ event.description }}
                </p>
                <button v-if="event.description && event.description.length > 120" @click="toggleDescription"
                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-2 font-medium">
                    {{ showFullDescription ? 'Voir moins' : 'Voir plus' }}
                </button>
            </div>

            <!-- Badge d'inscription si applicable -->
            <div v-if="event.is_registered" class="mb-3">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                    Inscrit
                </span>
            </div>

            <!-- Message d'inscription (pour les non-inscrits) -->
            <div v-else-if="!isAuthor && registrationInfo.message" class="mb-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" :class="{
                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': registrationInfo.color === 'red',
                    'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400': registrationInfo.color === 'orange',
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': registrationInfo.color === 'yellow',
                    'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400': registrationInfo.color === 'gray'
                }">
                    {{ registrationInfo.message }}
                </span>
            </div>

            <!-- Informations clés dans un grid -->
            <div class="grid grid-cols-1 gap-3 mb-4">
                <!-- Dates d'inscription (seulement pour les événements à venir) -->
                <div v-if="eventStatus === 'upcoming' && event.registration_start_date"
                    class="flex items-center text-sm bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                    <svg class="w-5 h-5 text-purple-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <div class="flex-1">
                        <div class="text-xs text-purple-600 dark:text-purple-400 font-medium mb-1">Inscriptions
                        </div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ formatDate(event.registration_start_date) }}
                            <span v-if="event.registration_end_date"> - {{ formatDate(event.registration_end_date)
                                }}</span>
                        </div>
                    </div>
                </div>

                <!-- Date et heure de l'événement -->
                <div class="flex items-center text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <svg class="w-5 h-5 text-blue-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <div class="flex-1">
                        <div class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Événement</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ displayDate }}</div>
                        <div v-if="formatTime(event.start_date)" class="text-gray-600 dark:text-gray-400 text-xs mt-1">
                            {{ formatTime(event.start_date) }}
                        </div>
                    </div>
                </div>

                <!-- Lieu (si présent) -->
                <div v-if="hasLocation" class="flex items-center text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div class="font-semibold text-gray-900 dark:text-white">{{ displayLocation }}</div>
                </div>

                <!-- Participants et organisateur (masquer participants pour événements passés) -->
                <div class="flex items-center justify-between text-sm bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <!-- Participants (seulement si événement pas terminé) -->
                    <div v-if="eventStatus !== 'past'" class="flex items-center">
                        <svg class="w-5 h-5 text-purple-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ participantsText }}</span>
                    </div>

                    <!-- Organisateur -->
                    <div class="flex items-center" :class="{ 'w-full': eventStatus === 'past' }">
                        <svg class="w-4 h-4 text-orange-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <div class="text-right" :class="{ 'text-left': eventStatus === 'past' }">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ event.organizer.firstname }} {{ event.organizer.lastname }}
                            </div>
                            <div v-if="isAuthor" class="text-xs text-orange-600 dark:text-orange-400">Organisateur
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div v-if="showActions" class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                <!-- Bouton d'inscription principal (si applicable) -->
                <div v-if="eventStatus === 'upcoming' && !event.is_registered && !isAuthor">
                    <button @click="handleRegistration"
                        :disabled="!registrationInfo.canRegister && registrationInfo.reason !== 'members_only'"
                        class="w-full px-4 py-3 rounded-lg font-semibold transition-all duration-200 text-center"
                        :class="registrationInfo.canRegister
                            ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm hover:shadow-lg transform hover:-translate-y-0.5'
                            : registrationInfo.reason === 'members_only'
                                ? 'bg-orange-600 text-white hover:bg-orange-700 shadow-sm hover:shadow-lg transform hover:-translate-y-0.5'
                                : 'bg-gray-200 text-gray-500 cursor-not-allowed dark:bg-gray-700 dark:text-gray-400'">

                        <span v-if="registrationInfo.reason === 'members_only'"
                            class="inline-flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                            </svg>
                            Devenir adhérent
                        </span>
                        <span v-else-if="registrationInfo.canRegister" class="inline-flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            S'inscrire
                        </span>
                        <span v-else class="text-sm">
                            {{ registrationInfo.message }}
                        </span>
                    </button>
                </div>

                <!-- Actions secondaires -->
                <div class="flex flex-col space-y-2">
                    <!-- Lien "Voir les détails" -->
                    <Link :href="route('events.show', event.id)"
                        class="inline-flex items-center justify-center px-4 py-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 font-medium transition-colors group border border-blue-200 dark:border-blue-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                    <span>Voir les détails</span>
                    <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                    </Link>

                    <!-- Actions pour l'organisateur -->
                    <div v-if="isAuthor" class="flex space-x-2">
                        <!-- Bouton modifier -->
                        <Link v-if="canEdit" :href="route('events.edit', event.id)"
                            class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Modifier
                        </Link>

                        <!-- Bouton supprimer -->
                        <button v-if="canDelete" @click="handleDelete"
                            class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="showDeleteConfirm
                                ? 'bg-red-600 text-white hover:bg-red-700'
                                : 'bg-gray-100 text-red-600 hover:bg-red-50 dark:bg-gray-700 dark:text-red-400 dark:hover:bg-gray-600'">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            {{ showDeleteConfirm ? 'Confirmer' : 'Supprimer' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>

<style scoped>
/* Classes spéciales pour le Masonry */
.event-card {
    break-inside: avoid;
    page-break-inside: avoid;
    backface-visibility: hidden;
    transform: translateZ(0);
    box-sizing: border-box;
    max-width: 100%;
    width: auto;
    min-width: 0;
}

/* Classes utilitaires pour la flexibilité */
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Animation d'apparition optimisée pour Masonry */
.event-card {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px) translateZ(0);
    }

    to {
        opacity: 1;
        transform: translateY(0) translateZ(0);
    }
}

/* Animation de pulsation pour les événements en cours */
@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: .7;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Styles pour les événements passés */
.event-card.opacity-80 img.grayscale {
    filter: grayscale(50%);
}

/* Transitions fluides - SANS width/height pour éviter les problèmes Masonry */
.event-card {
    transition:
        opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
        box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1),
        border-color 0.3s cubic-bezier(0.4, 0, 0.2, 1),
        filter 0.3s cubic-bezier(0.4, 0, 0.2, 1),
        transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Éviter les problèmes de débordement */
.event-card {
    overflow: hidden;
    contain: layout style paint;
}

/* Améliorations pour l'accessibilité */
@media (prefers-reduced-motion: reduce) {

    .event-card,
    .event-card *,
    .group-hover\:scale-105:hover,
    .animate-pulse,
    .hover\:-translate-y-0\.5:hover {
        animation: none !important;
        transition: none !important;
        transform: none !important;
    }
}

/* Focus visible pour l'accessibilité clavier */
.event-card button:focus-visible,
.event-card a:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
    border-radius: 0.375rem;
}

/* Amélioration du contraste en mode sombre */
@media (prefers-color-scheme: dark) {
    .event-card {
        border-color: rgb(55 65 81);
    }

    .event-card:hover {
        border-color: rgb(75 85 99);
    }
}

/* Assurer la lisibilité des badges sur toutes les images */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* Amélioration des boîtes d'information */
.bg-gray-50 {
    background-color: rgb(249 250 251);
}

@media (prefers-color-scheme: dark) {
    .dark\:bg-gray-700\/50 {
        background-color: rgb(55 65 81 / 0.5);
    }
}

/* Responsive pour mobile */
@media (max-width: 640px) {
    .event-card h3 {
        font-size: 1.125rem;
        line-height: 1.375rem;
    }

    .event-card .text-sm {
        font-size: 0.875rem;
        line-height: 1.25rem;
    }

    /* Adapter le grid sur mobile pour une meilleure lisibilité */
    .grid-cols-1 {
        gap: 0.75rem;
    }

    /* Ajuster les boutons sur mobile */
    .flex.space-x-2 {
        flex-direction: column;
        gap: 0.5rem;
    }

    .flex.space-x-2>* {
        flex: none;
    }
}

/* Améliorations visuelles pour les interactions */
.hover\:shadow-lg:hover {
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}

.hover\:-translate-y-0\.5:hover {
    transform: translateY(-0.125rem);
}

/* Animation douce pour les badges */
.inline-flex.items-center {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Amélioration de la hiérarchie visuelle */
.text-xl.font-bold {
    letter-spacing: -0.025em;
}

/* Meilleure séparation visuelle */
.border-t.border-gray-100 {
    margin-top: 0.25rem;
}
</style>