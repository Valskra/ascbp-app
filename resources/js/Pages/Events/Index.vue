<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EventCard from '@/Components/EventCard.vue'
import ReloadButton from '@/Components/svg/ReloadButton.vue'
import { ref, computed, watch, onMounted, nextTick, onUnmounted } from 'vue'

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

const $user = usePage().props.auth.user;
const eventsContainer = ref(null)
const isLayouting = ref(false)

// États pour les filtres
const selectedCategory = ref(props.filters?.category || '')
const selectedSort = ref(props.filters?.sort || 'date')

// Configuration Masonry simplifiée
const masonryConfig = ref({
    columnWidth: 320,
    gutter: 24,
    containerPadding: 0
})

// Fonction pour filtrer les événements
const filteredEvents = computed(() => {
    let filtered = [...props.events]

    // Filtrage par catégorie
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
        default: // 'date'
            filtered.sort((a, b) => new Date(a.start_date) - new Date(b.start_date))
    }

    return filtered
})

// Fonction Masonry Layout optimisée
const layoutMasonry = async () => {
    if (!eventsContainer.value || isLayouting.value) return

    isLayouting.value = true

    // Attendre que le DOM soit à jour
    await nextTick()
    await new Promise(resolve => setTimeout(resolve, 50))

    const container = eventsContainer.value
    const items = Array.from(container.querySelectorAll('.event-card'))


    if (items.length === 0) {
        isLayouting.value = false
        return
    }

    // Calculer le nombre de colonnes
    const containerWidth = container.offsetWidth
    const { columnWidth, gutter } = masonryConfig.value


    // S'assurer qu'on a une largeur valide
    if (containerWidth === 0) {
        setTimeout(layoutMasonry, 100)
        isLayouting.value = false
        return
    }

    const columnCount = Math.max(1, Math.floor(containerWidth / (columnWidth + gutter)))
    const actualColumnWidth = Math.floor((containerWidth - (gutter * (columnCount + 1))) / columnCount)


    // Initialiser les hauteurs des colonnes
    const columnHeights = new Array(columnCount).fill(gutter)

    // Assurer que le conteneur a la bonne position
    container.style.position = 'relative'
    container.style.width = '100%'

    // Réinitialiser toutes les cartes d'abord
    items.forEach(item => {
        item.style.position = 'absolute'
        item.style.width = `${actualColumnWidth}px`
        item.style.transition = 'all 0.3s ease'
        item.style.visibility = 'hidden' // Cacher temporairement
    })

    // Positionner chaque élément après un court délai pour permettre le calcul des hauteurs
    await new Promise(resolve => setTimeout(resolve, 50))

    items.forEach((item, index) => {
        // Rendre visible pour calculer la hauteur
        item.style.visibility = 'visible'

        // Forcer un reflow pour obtenir la vraie hauteur
        item.offsetHeight

        // Trouver la colonne la plus courte
        const shortestColumnIndex = columnHeights.indexOf(Math.min(...columnHeights))

        // Calculer la position
        const x = shortestColumnIndex * (actualColumnWidth + gutter) + gutter
        const y = columnHeights[shortestColumnIndex]


        // Appliquer les positions
        item.style.left = `${x}px`
        item.style.top = `${y}px`

        // Mesurer la hauteur après positionnement
        const itemHeight = item.offsetHeight || 350 // fallback

        // Mettre à jour la hauteur de la colonne
        columnHeights[shortestColumnIndex] += itemHeight + gutter
    })

    // Ajuster la hauteur du conteneur
    const maxHeight = Math.max(...columnHeights)
    container.style.height = `${maxHeight}px`


    isLayouting.value = false
}

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

// Réinitialiser les filtres
const resetFilters = () => {
    selectedCategory.value = ''
    selectedSort.value = 'date'
    router.get(route('events.index'), {}, {
        preserveState: true,
        replace: true
    })
}

// Vérifier si l'utilisateur peut créer des événements
const canCreateEvent = computed(() => {
    const user = $user
    return user && (user.is_admin || user.is_animator)
})

// Statistiques des événements
const eventStats = computed(() => {
    const stats = {
        total: props.events.length,
        competitions: 0,
        entrainements: 0,
        manifestations: 0
    }

    props.events.forEach(event => {
        switch (event.category) {
            case 'competition':
                stats.competitions++
                break
            case 'entrainement':
                stats.entrainements++
                break
            case 'manifestation':
                stats.manifestations++
                break
        }
    })

    return stats
})

// Observer les changements pour relancer le layout
watch(filteredEvents, async () => {
    await nextTick()
    setTimeout(layoutMasonry, 200)
}, { flush: 'post' })

// ResizeObserver pour relancer le layout lors du redimensionnement
let resizeObserver = null

onMounted(async () => {

    // Attendre que le DOM soit complètement rendu
    await nextTick()

    // Lancer le layout initial avec un délai plus long
    setTimeout(() => {
        layoutMasonry()
    }, 300)

    // Observer les changements de taille
    if (eventsContainer.value) {
        resizeObserver = new ResizeObserver(() => {
            setTimeout(layoutMasonry, 100)
        })
        resizeObserver.observe(eventsContainer.value)

        // Écouter les événements personnalisés de mise à jour Masonry
        eventsContainer.value.addEventListener('masonry-update', () => {
            setTimeout(layoutMasonry, 150)
        })
    }

    // Écouter les événements de resize globaux (fallback)
    const handleResize = () => {
        setTimeout(layoutMasonry, 100)
    }
    window.addEventListener('resize', handleResize)

    // Nettoyer les listeners lors de la destruction
    onUnmounted(() => {
        window.removeEventListener('resize', handleResize)
        if (resizeObserver) {
            resizeObserver.disconnect()
        }
    })
})

onUnmounted(() => {
    if (resizeObserver) {
        resizeObserver.disconnect()
    }
})
</script>

<template>

    <Head title="Liste des événements" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Événements
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ filteredEvents.length }} événement{{ filteredEvents.length !== 1 ? 's' : '' }}
                        {{ selectedCategory ? `dans la catégorie "${selectedCategory}"` : 'au total' }}
                    </p>
                </div>

                <!-- Bouton créer événement pour les animateurs -->
                <Link v-if="canCreateEvent" :href="route('events.create')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 text-sm font-medium shadow-sm">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Créer un événement
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Messages flash -->
                <div v-if="$page.props.flash?.success"
                    class="mb-6 p-4 bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-green-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-green-800 dark:text-green-200 text-sm">{{ $page.props.flash.success }}</p>
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
                        <p class="text-red-800 dark:text-red-200 text-sm">{{ $page.props.flash.error }}</p>
                    </div>
                </div>

                <!-- Statistiques rapides -->
                <div v-if="eventStats.total > 0" class="mb-8 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div
                        class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ eventStats.total }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total</div>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-2xl font-bold text-red-600">{{ eventStats.competitions }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">🏆 Compétitions</div>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-2xl font-bold text-blue-600">{{ eventStats.entrainements }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">💪 Entraînements</div>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-2xl font-bold text-green-600">{{ eventStats.manifestations }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">🎉 Manifestations</div>
                    </div>
                </div>

                <!-- Filtres et tri -->
                <!-- Indicateur de layout en cours -->


                <div
                    class="mb-8 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Filtres par catégorie -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Catégorie :</label>
                            <div class="flex flex-wrap gap-2">
                                <button @click="setCategory('')"
                                    class="px-4 py-2 rounded-lg transition-colors text-sm font-medium"
                                    :class="selectedCategory === ''
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                    Tous
                                </button>
                                <button @click="setCategory('competition')"
                                    class="px-4 py-2 rounded-lg transition-colors text-sm font-medium"
                                    :class="selectedCategory === 'competition'
                                        ? 'bg-red-600 text-white shadow-sm'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                    🏆 Compétitions
                                </button>
                                <button @click="setCategory('entrainement')"
                                    class="px-4 py-2 rounded-lg transition-colors text-sm font-medium"
                                    :class="selectedCategory === 'entrainement'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                    💪 Entraînements
                                </button>
                                <button @click="setCategory('manifestation')"
                                    class="px-4 py-2 rounded-lg transition-colors text-sm font-medium"
                                    :class="selectedCategory === 'manifestation'
                                        ? 'bg-green-600 text-white shadow-sm'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                    🎉 Manifestations
                                </button>
                            </div>
                        </div>

                        <!-- Tri et reset -->
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <label for="sort" class="text-sm font-medium text-gray-700 dark:text-gray-300">Trier par
                                    :</label>
                                <select id="sort" v-model="selectedSort" @change="setSort(selectedSort)"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="date">Date (plus proche)</option>
                                    <option value="date_desc">Date (plus éloigné)</option>
                                    <option value="title">Titre (A-Z)</option>
                                </select>
                            </div>

                            <button v-if="selectedCategory || selectedSort !== 'date'" @click="resetFilters"
                                class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Réinitialiser
                            </button>
                            <ReloadButton :loading="isLayouting" @click="layoutMasonry" />
                        </div>
                    </div>
                </div>



                <!-- MASONRY CONTAINER - Positionnement absolu -->
                <div v-if="filteredEvents.length > 0" ref="eventsContainer" class="masonry-container"
                    style="position: relative; width: 100%; min-height: 400px;">
                    <EventCard v-for="event in filteredEvents" :key="event.id" :event="event" :show-actions="true"
                        :data-event-id="event.id" class="event-card" />
                </div>

                <!-- Message si aucun événement -->
                <div v-else class="text-center py-16">
                    <div class="mx-auto max-w-md">
                        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>

                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            {{ selectedCategory ? 'Aucun événement dans cette catégorie' : 'Aucun événement disponible'
                            }}
                        </h3>

                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            {{ selectedCategory
                                ? 'Essayez de changer le filtre de catégorie ou consultez tous les événements.'
                                : 'Aucun événement n\'est programmé pour le moment.'
                            }}
                        </p>

                        <!-- Actions pour l'état vide -->
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <button v-if="selectedCategory" @click="resetFilters"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Voir tous les événements
                            </button>

                            <Link v-if="canCreateEvent" :href="route('events.create')"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
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
    </AuthenticatedLayout>
</template>

<style scoped>
/* Container Masonry avec positionnement relatif */
.masonry-container {
    min-height: 400px;
    transition: height 0.3s ease;
}

/* Les cartes seront positionnées en absolu par le JavaScript */
.event-card {
    transition: all 0.3s ease;
    transform: translateZ(0);
}

/* Animation pour l'apparition des cartes */
.event-card {
    animation: fadeInUp 0.6s ease-out;
}

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

/* Animation pour l'apparition du conteneur */
.masonry-container {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

/* Responsive pour très petits écrans */
@media (max-width: 360px) {
    .masonry-container {
        padding: 0 0.5rem;
    }
}
</style>