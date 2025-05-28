<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EventCard from '@/Components/EventCard.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    events: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    }
})

// États pour les filtres
const selectedCategory = ref(props.filters?.category || '')
const selectedSort = ref(props.filters?.sort || 'date')

// Fonction pour filtrer les événements
const filteredEvents = computed(() => {
    let filtered = [...props.events]

    if (selectedCategory.value) {
        filtered = filtered.filter(event => event.category === selectedCategory.value)
    }

    // Tri des événements
    switch (selectedSort.value) {
        case 'date_desc':
            filtered.sort((a, b) => new Date(b.start_date) - new Date(a.start_date))
            break
        case 'title':
            filtered.sort((a, b) => a.title.localeCompare(b.title))
            break
        default:
            filtered.sort((a, b) => new Date(a.start_date) - new Date(b.start_date))
    }

    return filtered
})

// Fonction pour changer le filtre de catégorie
const setCategory = (category) => {
    selectedCategory.value = category
    updateFilters()
}

// Fonction pour changer le tri
const setSort = (sort) => {
    selectedSort.value = sort
    updateFilters()
}

// Fonction pour mettre à jour les filtres dans l'URL
const updateFilters = () => {
    const params = {}
    if (selectedCategory.value) params.category = selectedCategory.value
    if (selectedSort.value !== 'date') params.sort = selectedSort.value

    router.get(route('events.index'), params, {
        preserveState: true,
        replace: true
    })
}

// Gestion de l'inscription
const registerForm = useForm({})

const handleRegister = (eventId) => {
    registerForm.post(route('events.register', eventId), {
        onSuccess: () => {
            // Recharger la page pour mettre à jour les données
            router.reload({ only: ['events'] })
        },
        onError: (errors) => {
            console.error('Erreur d\'inscription:', errors)
        }
    })
}
</script>

<template>

    <Head title="Liste des événements" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Liste des événements
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ filteredEvents.length }} événement{{ filteredEvents.length > 1 ? 's' : '' }} disponible{{
                            filteredEvents.length > 1 ? 's' : '' }}
                    </p>
                </div>

                <!-- Bouton créer événement pour les animateurs -->
                <Link v-if="$page.props.auth.user.role === 'animator' || $page.props.auth.user.role === 'admin'"
                    :href="route('events.create')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium">
                Créer un événement
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

                <!-- Messages flash -->
                <div v-if="$page.props.flash?.success"
                    class="mb-6 p-4 bg-green-100 dark:bg-green-900 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-green-800 dark:text-green-200">{{ $page.props.flash.success }}</p>
                </div>

                <div v-if="$page.props.flash?.error"
                    class="mb-6 p-4 bg-red-100 dark:bg-red-900 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-800 dark:text-red-200">{{ $page.props.flash.error }}</p>
                </div>

                <!-- Filtres et tri -->
                <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Filtres par catégorie -->
                    <div class="flex flex-wrap gap-2">
                        <button @click="setCategory('')"
                            class="px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
                            :class="selectedCategory === '' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                            Tous
                        </button>
                        <button @click="setCategory('competition')"
                            class="px-4 py-2 rounded-lg transition-colors text-sm font-medium"
                            :class="selectedCategory === 'competition' ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                            🏆 Compétitions
                        </button>
                        <button @click="setCategory('entrainement')"
                            class="px-4 py-2 rounded-lg transition-colors text-sm font-medium"
                            :class="selectedCategory === 'entrainement' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                            💪 Entraînements
                        </button>
                        <button @click="setCategory('manifestation')"
                            class="px-4 py-2 rounded-lg transition-colors text-sm font-medium"
                            :class="selectedCategory === 'manifestation' ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                            🎉 Manifestations
                        </button>
                    </div>

                    <!-- Tri -->
                    <div class="flex gap-2">
                        <select v-model="selectedSort" @change="setSort(selectedSort)"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="date">Plus proche</option>
                            <option value="date_desc">Plus éloigné</option>
                            <option value="title">Titre A-Z</option>
                        </select>
                    </div>
                </div>

                <!-- Grille d'événements -->
                <div v-if="filteredEvents.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <EventCard v-for="event in filteredEvents" :key="event.id" :event="event" :show-actions="true"
                        @register="handleRegister" />
                </div>

                <!-- Message si aucun événement -->
                <div v-else class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        {{ selectedCategory ? 'Aucun événement dans cette catégorie' : 'Aucun événement' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ selectedCategory
                            ? 'Essayez de changer le filtre de catégorie.'
                            : 'Aucun événement n\'est disponible pour le moment.'
                        }}
                    </p>

                    <!-- Bouton pour créer un événement si l'utilisateur est animateur -->
                    <div v-if="($page.props.auth.user.role === 'animator' || $page.props.auth.user.role === 'admin') && !selectedCategory"
                        class="mt-6">
                        <Link :href="route('events.create')"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Créer le premier événement
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>