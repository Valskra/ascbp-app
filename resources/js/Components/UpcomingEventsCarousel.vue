<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    events: {
        type: Array,
        default: () => []
    }
});

const currentIndex = ref(0);
const autoPlayInterval = ref(null);
const isAutoPlaying = ref(true);

// Filtrer les événements pour le mois à venir + 3 suivants
const filteredEvents = computed(() => {
    const now = new Date();
    const oneMonthFromNow = new Date(now.getFullYear(), now.getMonth() + 1, now.getDate());

    // Événements du mois à venir
    const eventsThisMonth = props.events.filter(event => {
        const eventDate = new Date(event.start_date);
        return eventDate >= now && eventDate <= oneMonthFromNow;
    });

    // Si on a moins de 3 événements ce mois, on ajoute les suivants
    const remainingSlots = Math.max(0, 3 - eventsThisMonth.length);
    const eventsAfterMonth = props.events
        .filter(event => new Date(event.start_date) > oneMonthFromNow)
        .slice(0, remainingSlots);

    return [...eventsThisMonth, ...eventsAfterMonth].slice(0, 6); // Max 6 événements
});

const totalSlides = computed(() => Math.max(1, filteredEvents.value.length));

function getEventIcon(category) {
    const icons = {
        'competition': '🏆',
        'entrainement': '💪',
        'manifestation': '🎉'
    };
    return icons[category] || '📅';
}

function getEventColor(category) {
    const colors = {
        'competition': 'border-l-red-400 bg-red-50 dark:bg-red-900/10',
        'entrainement': 'border-l-blue-400 bg-blue-50 dark:bg-blue-900/10',
        'manifestation': 'border-l-green-400 bg-green-50 dark:bg-green-900/10'
    };
    return colors[category] || 'border-l-gray-400 bg-gray-50 dark:bg-gray-900/10';
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    if (date.toDateString() === today.toDateString()) {
        return "Aujourd'hui";
    } else if (date.toDateString() === tomorrow.toDateString()) {
        return "Demain";
    } else {
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: date.getFullYear() !== today.getFullYear() ? 'numeric' : undefined
        });
    }
}

function formatTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function isEventSoon(dateStr) {
    const eventDate = new Date(dateStr);
    const now = new Date();
    const diffDays = Math.ceil((eventDate - now) / (1000 * 60 * 60 * 24));
    return diffDays <= 7;
}

function nextSlide() {
    currentIndex.value = (currentIndex.value + 1) % totalSlides.value;
}

function prevSlide() {
    currentIndex.value = currentIndex.value === 0 ? totalSlides.value - 1 : currentIndex.value - 1;
}

function goToSlide(index) {
    currentIndex.value = index;
}

function startAutoPlay() {
    if (filteredEvents.value.length > 1) {
        autoPlayInterval.value = setInterval(nextSlide, 5000);
        isAutoPlaying.value = true;
    }
}

function stopAutoPlay() {
    if (autoPlayInterval.value) {
        clearInterval(autoPlayInterval.value);
        autoPlayInterval.value = null;
        isAutoPlaying.value = false;
    }
}

function toggleAutoPlay() {
    if (isAutoPlaying.value) {
        stopAutoPlay();
    } else {
        startAutoPlay();
    }
}

onMounted(() => {
    startAutoPlay();
});

onUnmounted(() => {
    stopAutoPlay();
});
</script>

<template>
    <div class="relative">
        <!-- En-tête avec contrôles -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">À venir</h3>

            <div class="flex items-center space-x-2">
                <!-- Bouton play/pause -->
                <button v-if="filteredEvents.length > 1" @click="toggleAutoPlay"
                    class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg v-if="isAutoPlaying" class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </button>

                <!-- Contrôles navigation -->
                <button v-if="filteredEvents.length > 1" @click="prevSlide"
                    class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <button v-if="filteredEvents.length > 1" @click="nextSlide"
                    class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Conteneur du carrousel -->
        <div class="relative overflow-hidden rounded-lg bg-gray-50 dark:bg-gray-800/50 min-h-48"
            @mouseenter="stopAutoPlay" @mouseleave="startAutoPlay">

            <!-- Message si aucun événement -->
            <div v-if="filteredEvents.length === 0"
                class="flex flex-col items-center justify-center h-48 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm font-medium">Aucun événement à venir</p>
                <p class="text-xs mt-1">Les prochains événements s'afficheront ici</p>
            </div>

            <!-- Slides -->
            <div v-else class="flex transition-transform duration-500 ease-in-out"
                :style="{ transform: `translateX(-${currentIndex * 100}%)` }">

                <div v-for="event in filteredEvents" :key="event.id" class="w-full flex-shrink-0 p-4">

                    <Link :href="route('events.show', event.id)"
                        class="block h-full border-l-4 rounded-lg p-4 hover:shadow-md transition-all duration-200"
                        :class="getEventColor(event.category)">

                    <div class="flex items-start justify-between h-full">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="text-xl">{{ getEventIcon(event.category) }}</span>
                                <span v-if="isEventSoon(event.start_date)"
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                    Bientôt
                                </span>
                            </div>

                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1 line-clamp-2">
                                {{ event.title }}
                            </h4>

                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <div class="flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ formatDate(event.start_date) }}</span>
                                </div>

                                <div class="flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ formatTime(event.start_date) }}</span>
                                </div>

                                <div v-if="event.participants_count !== undefined" class="flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>{{ event.participants_count }} participant{{ event.participants_count !== 1 ?
                                        's' : '' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="ml-4 flex flex-col items-end justify-between h-full">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium capitalize"
                                :class="{
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': event.category === 'competition',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': event.category === 'entrainement',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': event.category === 'manifestation'
                                }">
                                {{ event.category }}
                            </span>

                            <svg class="w-5 h-5 text-gray-400 mt-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Indicateurs de pagination -->
        <div v-if="filteredEvents.length > 1" class="flex justify-center space-x-2 mt-4">
            <button v-for="(event, index) in filteredEvents" :key="index" @click="goToSlide(index)"
                class="w-2 h-2 rounded-full transition-all duration-200" :class="index === currentIndex
                    ? 'bg-blue-600 w-6'
                    : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500'">
            </button>
        </div>

        <!-- Lien vers tous les événements -->
        <div class="mt-4 text-center">
            <Link :href="route('events.index')"
                class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
            Voir tous les événements
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            </Link>
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
</style>