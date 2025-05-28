<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { computed } from 'vue'
import { useDateFormat } from '@vueuse/core'

const props = defineProps({
    events: {
        type: Array,
        default: () => []
    }
})

// Fonction pour formater les dates
const formatDate = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'DD/MM/YYYY').value
}

// Fonction pour obtenir la couleur de catégorie
const getCategoryColor = (category) => {
    const colors = {
        'competition': 'bg-red-500',
        'entrainement': 'bg-blue-500',
        'manifestation': 'bg-green-500'
    }
    return colors[category] || 'bg-gray-500'
}

// Fonction pour obtenir l'image par défaut selon la catégorie
const getDefaultImage = (category) => {
    const images = {
        'competition': 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&h=300&fit=crop',
        'entrainement': 'https://images.unsplash.com/photo-1544717297-fa95b6ee9643?w=400&h=300&fit=crop',
        'manifestation': 'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?w=400&h=300&fit=crop'
    }
    return images[category] || 'https://images.unsplash.com/photo-1526676037777-05a232c2f1a8?w=400&h=300&fit=crop'
}
</script>

<template>

    <Head title="Liste des événements" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Liste des événements
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- En-tête avec filtres -->
                <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex flex-wrap gap-2">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Tous
                        </button>
                        <button
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            Compétitions
                        </button>
                        <button
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            Entraînements
                        </button>
                        <button
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            Manifestations
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <select class="px-4 py-2 border rounded-lg bg-white dark:bg-gray-800">
                            <option>Trier par date</option>
                            <option>Plus récent</option>
                            <option>Plus ancien</option>
                        </select>
                    </div>
                </div>

                <!-- Grille d'événements -->
                <div v-if="events.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="event in events" :key="event.id"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <!-- Image de l'événement -->
                        <div class="relative h-48 overflow-hidden">
                            <img :src="event.illustration?.url || getDefaultImage(event.category)" :alt="event.title"
                                class="w-full h-full object-cover transition-transform duration-300 hover:scale-105" />

                            <!-- Badge de catégorie -->
                            <div class="absolute top-3 left-3 px-3 py-1 rounded-full text-white text-sm font-medium"
                                :class="getCategoryColor(event.category)">
                                {{ event.category }}
                            </div>

                            <!-- Prix (si applicable) -->
                            <div v-if="event.price"
                                class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-lg text-sm font-medium text-gray-800">
                                {{ event.price }}
                            </div>
                        </div>

                        <!-- Contenu de la carte -->
                        <div class="p-6">
                            <!-- Titre et description -->
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                {{ event.title }}
                            </h3>

                            <p v-if="event.description"
                                class="text-gray-600 dark:text-gray-300 text-sm mb-4 line-clamp-2">
                                {{ event.description }}
                            </p>

                            <!-- Informations sur les dates -->
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>
                                        {{ formatDate(event.start_date) }}
                                        <span v-if="event.end_date && event.end_date !== event.start_date">
                                            - {{ formatDate(event.end_date) }}
                                        </span>
                                    </span>
                                </div>

                                <!-- Lieu -->
                                <div v-if="event.address"
                                    class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>{{ event.address?.city || 'Lieu à définir' }}</span>
                                </div>

                                <!-- Participants -->
                                <div v-if="event.max_participants"
                                    class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                    </svg>
                                    <span>Max {{ event.max_participants }} participants</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2">
                                <button
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium">
                                    S'inscrire
                                </button>
                                <button
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message si aucun événement -->
                <div v-else class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun événement</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aucun événement n'est disponible pour le
                        moment.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>