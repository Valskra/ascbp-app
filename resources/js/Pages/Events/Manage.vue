<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManagingLayout from '@/Layouts/ManagingLayout.vue'
import { useDateFormat } from '@vueuse/core'
import { ref } from 'vue'

const props = defineProps({
    events: {
        type: Array,
        default: () => []
    }
})

const showDeleteModal = ref(false)
const eventToDelete = ref(null)
const deleteForm = useForm({})

// Fonction pour formater les dates
const formatDate = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'DD/MM/YYYY à HH:mm').value
}

// Fonction pour obtenir la couleur de statut
const getStatusColor = (event) => {
    const now = new Date()
    const startDate = new Date(event.start_date)
    const endDate = new Date(event.end_date)

    if (endDate < now) return 'bg-gray-500' // Terminé
    if (startDate <= now && endDate >= now) return 'bg-green-500' // En cours
    if (startDate > now) return 'bg-blue-500' // À venir

    return 'bg-gray-500'
}

const getStatusText = (event) => {
    const now = new Date()
    const startDate = new Date(event.start_date)
    const endDate = new Date(event.end_date)

    if (endDate < now) return 'Terminé'
    if (startDate <= now && endDate >= now) return 'En cours'
    if (startDate > now) return 'À venir'

    return 'Inconnu'
}

// Fonction pour obtenir la couleur de catégorie
const getCategoryColor = (category) => {
    const colors = {
        'competition': 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900',
        'entrainement': 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-900',
        'manifestation': 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900'
    }
    return colors[category] || 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-900'
}

// Gestion de la suppression
const confirmDelete = (event) => {
    eventToDelete.value = event
    showDeleteModal.value = true
}

const deleteEvent = () => {
    if (!eventToDelete.value) return

    deleteForm.delete(route('events.destroy', eventToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false
            eventToDelete.value = null
        },
        onFinish: () => {
            deleteForm.reset()
        }
    })
}

const cancelDelete = () => {
    showDeleteModal.value = false
    eventToDelete.value = null
}
</script>

<template>

    <Head title="Gestion des événements" />

    <ManagingLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Gestion des événements
                </h2>
                <Link :href="route('events.create')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium">
                Créer un événement
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

                <!-- Messages de statut -->
                <div v-if="$page.props.flash?.success"
                    class="mb-6 p-4 bg-green-100 dark:bg-green-900 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-200">{{ $page.props.flash.success }}</p>
                </div>

                <div v-if="$page.props.flash?.error"
                    class="mb-6 p-4 bg-red-100 dark:bg-red-900 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-800 dark:text-red-200">{{ $page.props.flash.error }}</p>
                </div>

                <!-- Statistiques rapides -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total
                                        événements
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">{{ events.length }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">À venir
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{events.filter(e => new Date(e.start_date) > new Date()).length}}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">En cours
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{events.filter(e => {
                                            const now = new Date();
                                            return new Date(e.start_date) <= now && new Date(e.end_date) >= now;
                                        }).length}}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total
                                        participants
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{events.reduce((sum, e) => sum + e.participants_count, 0)}}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des événements -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Mes événements</h3>
                    </div>

                    <div v-if="events.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Événement
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Catégorie
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Participants
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="event in events" :key="event.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ event.title }}
                                            </div>
                                            <div v-if="event.organizer"
                                                class="text-sm text-gray-500 dark:text-gray-400">
                                                Par {{ event.organizer.firstname }} {{ event.organizer.lastname }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getCategoryColor(event.category)">
                                            {{ event.category }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ formatDate(event.start_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                        {{ event.participants_count }}
                                        <span v-if="event.max_participants">/ {{ event.max_participants }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full text-white"
                                            :class="getStatusColor(event)">
                                            {{ getStatusText(event) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <!-- Voir -->
                                            <Link :href="route('events.show', event.id)"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                title="Voir l'événement">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            </Link>

                                            <!-- Participants -->
                                            <Link :href="route('events.participants', event.id)"
                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                title="Voir les participants">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                            </svg>
                                            </Link>

                                            <!-- Modifier -->
                                            <Link v-if="event.can_edit" :href="route('events.edit', event.id)"
                                                class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300"
                                                title="Modifier l'événement">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            </Link>

                                            <!-- Supprimer -->
                                            <button v-if="event.can_delete" @click="confirmDelete(event)"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="Supprimer l'événement">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Message si aucun événement -->
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun événement</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Commencez par créer votre premier
                            événement.
                        </p>
                        <div class="mt-6">
                            <Link :href="route('events.create')"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Créer un événement
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation de suppression -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <div
                        class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <div class="mt-2 px-7 py-3">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white text-center">Confirmer la
                            suppression</h3>
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                                Êtes-vous sûr de vouloir supprimer l'événement "<strong>{{ eventToDelete?.title
                                }}</strong>" ?
                                Cette action est irréversible.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center gap-3 px-4 py-3">
                    <button @click="cancelDelete"
                        class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-400 dark:hover:bg-gray-500">
                        Annuler
                    </button>
                    <button @click="deleteEvent" :disabled="deleteForm.processing"
                        class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 disabled:opacity-50">
                        {{ deleteForm.processing ? 'Suppression...' : 'Supprimer' }}
                    </button>
                </div>
            </div>
        </div>
    </ManagingLayout>
</template>