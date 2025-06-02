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
        'competition': 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
        'entrainement': 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
        'manifestation': 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800'
    }
    return colors[category] || 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:border-gray-800'
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
        'already_registered': 'Déjà inscrit',
        'event_full': 'Complet',
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
        canRegister: false,
        message: messages[reason] || 'Inscription impossible',
        color: colors[reason] || 'gray',
        reason
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

    // Déclencher un re-layout après un changement de taille
    nextTick(() => {
        setTimeout(() => {
            // Déclencher un événement personnalisé pour informer le parent
            const event = new CustomEvent('masonry-update', {
                bubbles: true,
                detail: {
                    reason: 'description-toggle',
                    eventId: props.event.id,
                    expanded: showFullDescription.value
                }
            })

            // Trouver l'élément parent et déclencher l'événement
            const cardElement = document.querySelector(`[data-event-id="${props.event.id}"]`)
            if (cardElement) {
                cardElement.dispatchEvent(event)
            } else {
                // Fallback : déclencher resize sur window
                window.dispatchEvent(new Event('resize'))
            }
        }, 0) // Petit délai pour que la transition CSS soit prise en compte
    })
}
</script>

<template>
    <article
        class="event-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 group"
        :data-event-id="event.id">

        <!-- Image d'illustration avec hauteur fixe -->
        <div class="relative overflow-hidden h-48">
            <!-- Image si présente -->
            <img v-if="event.illustration?.url" :src="event.illustration.url" :alt="event.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

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
                    <!-- Cercles concentriques -->
                    <circle cx="50" cy="50" r="30" fill="none" stroke="currentColor" stroke-width="2" opacity="0.6" />
                    <circle cx="50" cy="50" r="20" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.4" />
                    <circle cx="50" cy="50" r="10" fill="none" stroke="currentColor" stroke-width="1" opacity="0.3" />

                    <!-- Triangles -->
                    <polygon points="150,30 170,70 130,70" fill="currentColor" opacity="0.3" />
                    <polygon points="160,90 175,120 145,120" fill="currentColor" opacity="0.2" />

                    <!-- Lignes diagonales -->
                    <line x1="0" y1="150" x2="80" y2="200" stroke="currentColor" stroke-width="2" opacity="0.4" />
                    <line x1="20" y1="130" x2="100" y2="180" stroke="currentColor" stroke-width="1.5" opacity="0.3" />
                    <line x1="40" y1="110" x2="120" y2="160" stroke="currentColor" stroke-width="1" opacity="0.2" />

                    <!-- Hexagones -->
                    <polygon points="160,160 175,170 175,190 160,200 145,190 145,170" fill="none" stroke="currentColor"
                        stroke-width="1.5" opacity="0.4" />

                    <!-- Points décoratifs -->
                    <circle cx="30" cy="120" r="3" fill="currentColor" opacity="0.5" />
                    <circle cx="70" cy="100" r="2" fill="currentColor" opacity="0.4" />
                    <circle cx="90" cy="40" r="2.5" fill="currentColor" opacity="0.3" />
                    <circle cx="120" cy="80" r="1.5" fill="currentColor" opacity="0.6" />
                </svg>

                <!-- Overlay pour adoucir -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/5 via-transparent to-transparent"></div>
            </div>

            <!-- Overlay gradient (seulement si image) -->
            <div v-if="event.illustration?.url"
                class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent"></div>

            <!-- Badge catégorie (haut gauche) -->
            <div class="absolute top-4 left-4">
                <span
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border backdrop-blur-sm"
                    :class="getCategoryColor(event.category)">
                    <span class="mr-1">{{ getCategoryIcon(event.category) }}</span>
                    <span class="capitalize">{{ event.category }}</span>
                </span>
            </div>

            <!-- Badge inscrit ou prix (haut droite) -->
            <div class="absolute top-4 right-4">
                <!-- Si l'utilisateur est inscrit, afficher le badge "Inscrit" -->
                <span v-if="event.is_registered"
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-green-500 text-white shadow-sm">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                    Inscrit
                </span>
                <!-- Sinon, afficher le prix -->
                <span v-else
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-white/90 dark:bg-gray-800/90 text-gray-900 dark:text-white shadow-sm border border-white/20 backdrop-blur-sm">
                    {{ displayPrice }}
                </span>
            </div>
        </div>

        <!-- Contenu -->
        <div class="p-6">
            <!-- Titre -->
            <div class="mb-4">
                <h3
                    class="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2">
                    {{ event.title }}
                </h3>

                <!-- Description avec expansion -->
                <div v-if="event.description">
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed" :class="{
                        'line-clamp-2': !showFullDescription && event.description.length <= 150,
                        'line-clamp-3': !showFullDescription && event.description.length > 150
                    }">
                        {{ event.description }}
                    </p>
                    <button v-if="event.description && event.description.length > 100" @click="toggleDescription"
                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-1 block">
                        {{ showFullDescription ? 'Voir moins' : 'Voir plus' }}
                    </button>
                </div>
            </div>

            <!-- Informations principales -->
            <div class="space-y-3 mb-5">
                <!-- Date et heure -->
                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="font-medium">{{ displayDate }}</span>
                    <span v-if="formatTime(event.start_date)" class="ml-2 text-gray-500">
                        à {{ formatTime(event.start_date) }}
                    </span>
                </div>

                <!-- Lieu -->
                <div v-if="hasLocation" class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ displayLocation }}</span>
                </div>

                <!-- Organisateur -->
                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>{{ event.organizer.firstname }} {{ event.organizer.lastname }}</span>
                    <span v-if="isAuthor" class="ml-1 text-blue-600 dark:text-blue-400 font-medium">(vous)</span>
                </div>

                <!-- Participants -->
                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-3 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>{{ participantsText }}</span>
                </div>
            </div>

            <!-- Status d'inscription -->
            <div v-if="registrationInfo.message" class="mb-4">
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border"
                    :class="getStatusColor(registrationInfo.color)">
                    {{ registrationInfo.message }}
                </span>
            </div>

            <!-- Actions -->
            <div v-if="showActions" class="border-t border-gray-100 dark:border-gray-700 pt-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <Link :href="route('events.show', event.id)"
                        class="inline-flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 text-sm font-medium transition-colors group w-fit">
                    <span>Voir les détails</span>
                    <svg class="w-4 h-4 ml-1 transition-transform group-hover:translate-x-1" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                    </Link>

                    <div class="flex items-center space-x-2 flex-wrap justify-end">
                        <!-- Actions pour l'auteur -->
                        <div v-if="isAuthor && showActions" class="flex items-center space-x-2">
                            <!-- Bouton modifier -->
                            <Link v-if="canEdit" :href="route('events.edit', event.id)"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Modifier
                            </Link>

                            <!-- Bouton supprimer -->
                            <button v-if="canDelete" @click="handleDelete"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                :class="showDeleteConfirm
                                    ? 'bg-red-600 text-white hover:bg-red-700'
                                    : 'bg-gray-100 text-red-600 hover:bg-red-50 dark:bg-gray-700 dark:text-red-400 dark:hover:bg-gray-600'">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                {{ showDeleteConfirm ? 'Confirmer' : 'Supprimer' }}
                            </button>
                        </div>

                        <!-- Bouton d'inscription -->
                        <button v-if="!event.is_registered && !isAuthor" @click="handleRegistration"
                            :disabled="!registrationInfo.canRegister && registrationInfo.reason !== 'members_only'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex-shrink-0"
                            :class="registrationInfo.canRegister
                                ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm hover:shadow-md'
                                : registrationInfo.reason === 'members_only'
                                    ? 'bg-orange-600 text-white hover:bg-orange-700 shadow-sm hover:shadow-md'
                                    : 'bg-gray-200 text-gray-500 cursor-not-allowed dark:bg-gray-700 dark:text-gray-400'">

                            <span v-if="registrationInfo.reason === 'members_only'" class="inline-flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                </svg>
                                Adhérer
                            </span>
                            <span v-else-if="registrationInfo.canRegister" class="inline-flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                S'inscrire
                            </span>
                            <span v-else class="text-xs">
                                {{ registrationInfo.message }}
                            </span>
                        </button>

                        <!-- Statut inscrit -->
                        <div v-else-if="event.is_registered && !isAuthor"
                            class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800 flex-shrink-0">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            Inscrit
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>

<style scoped>
/* Classes spéciales pour le Masonry */
/* Classes spéciales pour le Masonry */
.event-card {
    /* Éviter la fragmentation lors de la création des colonnes */
    break-inside: avoid;
    page-break-inside: avoid;

    /* Optimisation des transitions pour le repositionnement */
    backface-visibility: hidden;
    transform: translateZ(0);

    /* Box-sizing pour inclure padding et border */
    box-sizing: border-box;
}

/* Classes utilitaires pour la flexibilité */
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

/* Responsive pour mobile */
@media (max-width: 640px) {
    .flex-shrink-0 {
        width: 100%;
        flex-shrink: 1;
    }

    .space-x-2 {
        gap: 0.5rem;
    }
}

/* Transitions fluides lors du repositionnement - SANS WIDTH */
.event-card {
    /* Transition spécifique excluant width */
    transition:
        transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
        opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
        box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1),
        border-color 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    /* Pas de transition sur left, top et width pour un placement instantané */
}

/* Éviter les problèmes de débordement */
.event-card {
    overflow: hidden;
    contain: layout style paint;
}
</style>
