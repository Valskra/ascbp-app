<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayoutout from '@/Layouts/AuthenticatedLayoutout.vue'
import { useDateFormat } from '@vueuse/core'

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    participants: {
        type: Array,
        default: () => []
    }
})

// Fonction pour formater les dates
const formatDate = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'DD/MM/YYYY').value
}

// Fonction pour obtenir les initiales
const getInitials = (firstname, lastname) => {
    return `${firstname?.charAt(0) || ''}${lastname?.charAt(0) || ''}`.toUpperCase()
}

// Fonction pour obtenir une couleur basée sur le nom
const getAvatarColor = (firstname, lastname) => {
    const colors = [
        'bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500',
        'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-gray-500'
    ]
    const name = `${firstname}${lastname}`
    const hash = name.split('').reduce((a, b) => {
        a = ((a << 5) - a) + b.charCodeAt(0)
        return a & a
    }, 0)
    return colors[Math.abs(hash) % colors.length]
}
</script>

<template>

    <Head :title="`Participants - ${event.title}`" />

    <AuthenticatedLayoutout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Participants - {{ event.title }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ participants.length }} participant{{ participants.length > 1 ? 's' : '' }} inscrit{{
                            participants.length > 1 ? 's' : '' }}
                    </p>
                </div>
                <div class="flex space-x-2">
                    <Link :href="route('events.manage')"
                        class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                    ← Retour à mes événements
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

                <!-- Informations sur l'événement -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Date de l'événement</h3>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ formatDate(event.start_date) }}
                                <span v-if="event.end_date && event.end_date !== event.start_date">
                                    - {{ formatDate(event.end_date) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Catégorie</h3>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white capitalize">{{ event.category
                            }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Taux de remplissage</h3>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ participants.length }}{{ event.max_participants ? `/${event.max_participants}` : ''
                                }}
                                <span v-if="event.max_participants" class="text-sm text-gray-500">
                                    ({{ Math.round((participants.length / event.max_participants) * 100) }}%)
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Liste des participants -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Liste des participants</h3>
                            <button
                                class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                                Exporter la liste
                            </button>
                        </div>
                    </div>

                    <div v-if="participants.length > 0">
                        <!-- Vue grille pour les écrans larges -->
                        <div class="hidden md:block">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-6">
                                <div v-for="participant in participants" :key="participant.id"
                                    class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-medium"
                                        :class="getAvatarColor(participant.firstname, participant.lastname)">
                                        {{ getInitials(participant.firstname, participant.lastname) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ participant.firstname }} {{ participant.lastname }}
                                        </p>
                                        <p v-if="participant.pivot.registration_date"
                                            class="text-xs text-gray-500 dark:text-gray-400">
                                            Inscrit le {{ formatDate(participant.pivot.registration_date) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vue liste pour les écrans mobiles -->
                        <div class="md:hidden">
                            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                                <li v-for="participant in participants" :key="participant.id"
                                    class="flex items-center space-x-4 p-4">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium"
                                        :class="getAvatarColor(participant.firstname, participant.lastname)">
                                        {{ getInitials(participant.firstname, participant.lastname) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ participant.firstname }} {{ participant.lastname }}
                                        </p>
                                        <div
                                            class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span v-if="participant.pivot.registration_date">
                                                Inscrit le {{ formatDate(participant.pivot.registration_date) }}
                                            </span>
                                            <span v-if="participant.pivot.certificate_medical"
                                                class="text-green-600 dark:text-green-400">
                                                • Certificat médical
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span v-if="participant.pivot.amount && participant.pivot.amount > 0"
                                            class="text-sm text-gray-900 dark:text-white font-medium">
                                            {{ participant.pivot.amount }}€
                                        </span>
                                        <span v-else class="text-sm text-green-600 dark:text-green-400">
                                            Gratuit
                                        </span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Message si aucun participant -->
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun participant</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Personne ne s'est encore inscrit à cet
                            événement.</p>
                    </div>
                </div>

                <!-- Statistiques détaillées -->
                <div v-if="participants.length > 0" class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Inscriptions par jour</h4>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ Math.round(participants.length / Math.max(1, Math.ceil((new
                                Date(event.registration_close)
                                - new
                                    Date(event.registration_open)) / (1000 * 60 * 60 * 24)))) }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">inscriptions/jour en moyenne</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Avec certificat médical
                        </h4>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                            {{participants.filter(p => p.pivot.certificate_medical).length}}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{Math.round((participants.filter(p => p.pivot.certificate_medical).length /
                                participants.length) *
                                100)}}% des participants
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Revenus générés</h4>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                            {{participants.reduce((sum, p) => sum + (p.pivot.amount || 0), 0)}}€
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{participants.filter(p => p.pivot.amount > 0).length}} inscription{{
                                participants.filter(p =>
                                    p.pivot.amount > 0).length > 1 ? 's' : ''}} payante{{participants.filter(p =>
                                p.pivot.amount >
                                0).length > 1 ? 's' : ''}}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayoutout>
</template>