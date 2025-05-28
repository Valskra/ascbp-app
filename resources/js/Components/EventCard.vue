<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { useDateFormat } from '@vueuse/core'
import { computed, ref } from 'vue'

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

const formatDateTime = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'DD/MM/YYYY à HH:mm', { locales: 'fr' }).value
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

const isUpcoming = computed(() => {
    if (!props.event.start_date) return false
    const eventDate = new Date(props.event.start_date)
    const now = new Date()
    const weekFromNow = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000)
    return eventDate >= now && eventDate <= weekFromNow
})
</script>

<template>
    <article
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 group"
        :class="{ 'ring-2 ring-orange-200 dark:ring-orange-800': isUpcoming }">

        <!-- Image d'illustration -->
        <div
            class="relative h-48 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
            <img v-if="event.illustration?.url" :src="event.illustration.url" :alt="event.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-5xl">
                {{ getCategoryIcon(event.category) }}
            </div>

            <!-- Overlay gradient -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent"></div>

            <!-- Badge catégorie -->
            <div class="absolute top-4 left-4">
                <span
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border backdrop-blur-sm"
                    :class="getCategoryColor(event.category)">
                    <span class="mr-1">{{ getCategoryIcon(event.category) }}</span>
                    <span class="capitalize">{{ event.category }}</span>
                </span>
            </div>

            <!-- Badge auteur -->
            <div v-if="isAuthor" class="absolute bottom-4 left-4">
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-500 text-white shadow-sm">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    Votre événement
                </span>
            </div>

            <!-- Prix -->
            <div class="absolute top-4 right-4">
                <span
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-white/90 dark:bg-gray-800/90 text-gray-900 dark:text-white shadow-sm border border-white/20 backdrop-blur-sm">
                    {{ displayPrice }}
                </span>
            </div>

            <!-- Badge événement bientôt -->
            <div v-if="isUpcoming" class="absolute top-14 left-1/2 transform -translate-x-1/2">
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-500 text-white shadow-sm animate-pulse">
                    🔥 Bientôt
                </span>
            </div>

            <!-- Indicateur inscription si inscrit -->
            <div v-if="event.is_registered" class="absolute bottom-4 right-4">
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-500 text-white shadow-sm">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                    Inscrit
                </span>
            </div>
        </div>

        <!-- Contenu -->
        <div class="p-6">
            <!-- Titre et description -->
            <div class="mb-4">
                <h3
                    class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {{ event.title }}
                </h3>
                <p v-if="event.description"
                    class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 leading-relaxed">
                    {{ event.description }}
                </p>
            </div>

            <!-- Informations principales -->
            <div class="space-y-3 mb-5">
                <!-- Date -->
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

                <!-- Lieu (uniquement si présent) -->
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
                    <span>
                        {{ event.participants_count || 0 }} participant{{ (event.participants_count || 0) !== 1 ?
                            's' : '' }}
                        <span v-if="event.max_participants" class="text-gray-400">/ {{ event.max_participants
                            }}</span>
                    </span>

                    <!-- Barre de progression si max participants défini -->
                    <div v-if="event.max_participants" class="ml-2 flex-1 max-w-[64px]">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all duration-300"
                                :class="((event.participants_count || 0) / event.max_participants) >= 0.8 ? 'bg-orange-500' : 'bg-blue-600'"
                                :style="{ width: `${Math.min(100, ((event.participants_count || 0) / event.max_participants) * 100)}%` }">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Badges d'information -->
            <div class="flex flex-wrap gap-2 mb-4">
                <span v-if="event.members_only"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                    </svg>
                    Adhérents uniquement
                </span>
                <span v-if="event.requires_medical_certificate"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v8a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm8 2a1 1 0 00-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2v-2z"
                            clip-rule="evenodd" />
                    </svg>
                    Certificat médical requis
                </span>
            </div>

            <!-- Status d'inscription -->
            <div v-if="registrationInfo.message" class="mb-4">
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border"
                    :class="getStatusColor(registrationInfo.color)">
                    {{ registrationInfo.message }}
                </span>
            </div>

            <!-- Actions -->
            <div v-if="showActions"
                class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                <Link :href="route('events.show', event.id)"
                    class="inline-flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 text-sm font-medium transition-colors group">
                <span>Voir les détails</span>
                <svg class="w-4 h-4 ml-1 transition-transform group-hover:translate-x-1" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
                </Link>

                <div class="flex items-center space-x-2">
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
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 min-w-[120px]"
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
                        class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
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
    </article>
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

/* Animation pour les cartes */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

article {
    animation: fadeInUp 0.3s ease-out;
}

/* Responsive improvements */
@media (max-width: 640px) {
    .min-w-\[120px\] {
        min-width: auto;
        width: 100%;
    }
}
</style>