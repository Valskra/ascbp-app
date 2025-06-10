<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Users from '@/Components/svg/usersIcon.vue';
const props = defineProps({
    events: {
        type: Array,
        default: () => []
    }
});

const currentIndex = ref(0);
const autoPlayInterval = ref(null);
const isAutoPlaying = ref(true);
const totalSlides = computed(() => Math.max(1, props.events.length));

function getEventIcon(category) {
    const icons = {
        'competition': '🏆',
        'entrainement': '💪',
        'manifestation': '🎉'
    };
    return icons[category] || '📅';
}

function getCategoryColor(category) {
    const colors = {
        'competition': 'bg-red-50 text-red-700 border-red-200',
        'entrainement': 'bg-blue-50 text-blue-700 border-blue-200',
        'manifestation': 'bg-green-50 text-green-700 border-green-200'
    };
    return colors[category] || 'bg-gray-50 text-gray-700 border-gray-200';
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
            month: 'long',
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

function nextEvent() {
    currentIndex.value = (currentIndex.value + 1) % props.events.length;
}

function prevEvent() {
    currentIndex.value = currentIndex.value === 0
        ? props.events.length - 1
        : currentIndex.value - 1;
}

function goToEvent(index) {
    currentIndex.value = index;
}

function startAutoPlay() {
    if (props.events.length > 1) {
        autoPlayInterval.value = setInterval(nextEvent, 5000);
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

onUnmounted(() => {
    stopAutoPlay();
});
</script>

<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl lg:rounded-2xl sm:rounded-none shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden w-full">

        <!-- En-tête -->
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Événements à venir </h3>
            <div v-if="props.events.length > 1" class="flex items-center space-x-2">
                <button @click="prevEvent"
                    class="p-2 rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
                    title="Précédent">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button @click="nextEvent"
                    class="p-2 rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
                    title="Suivant">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <button @click="toggleAutoPlay"
                    class="p-2 rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
                    :title="isAutoPlaying ? 'Pause' : 'Lecture'">
                    <svg v-if="isAutoPlaying" class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
                    </svg>
                    <svg v-else class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Contenu principal du carrousel -->
        <div class="relative h-96 overflow-hidden" @mouseenter="stopAutoPlay" @mouseleave="startAutoPlay">

            <!-- Message si aucun événement -->
            <div v-if="props.events.length === 0"
                class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-800 p-6">
                <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun événement prévu</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-4">Les prochains événements
                    apparaîtront ici dès qu'ils seront programmés.</p>
                <Link :href="route('events.index')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                Voir tous les événements
                </Link>
            </div>

            <!-- Carrousel des événements -->
            <div v-else class="relative h-full">
                <!-- Slides -->
                <div class="flex transition-transform duration-500 ease-in-out h-full"
                    :style="{ transform: `translateX(-${currentIndex * 100}%)` }">
                    <div v-for="event in props.events" :key="event.id" class="w-full flex-shrink-0 relative">

                        <Link :href="route('events.show', event.id)" class="block h-full group">

                        <!-- Image de fond -->
                        <div class="absolute inset-0">
                            <img v-if="event.illustration?.url" :src="event.illustration.url" :alt="event.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                            <div v-else
                                class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                                <div class="text-6xl opacity-20">{{ getEventIcon(event.category) }}</div>
                            </div>

                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent">
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="absolute inset-0 flex flex-col justify-end p-6 text-white">

                            <!-- Badges du haut -->
                            <div class="absolute top-6 left-6 flex items-center space-x-2">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur-sm border border-white/30 capitalize">
                                    {{ event.category }}
                                </span>
                                <span v-if="isEventSoon(event.start_date)"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-500/90 backdrop-blur-sm">
                                    Bientôt
                                </span>
                            </div>

                            <!-- Informations principales -->
                            <div class="space-y-3">

                                <!-- Titre -->
                                <h4
                                    class="text-2xl font-bold leading-tight group-hover:text-blue-200 transition-colors">
                                    {{ event.title }}
                                </h4>

                                <!-- Description -->
                                <p v-if="event.description" class="text-sm text-gray-200 leading-relaxed line-clamp-2">
                                    {{ event.description }}
                                </p>

                                <!-- Métadonnées -->
                                <div class="grid grid-cols-2 gap-4 text-sm">

                                    <!-- Date et heure -->
                                    <div class="flex items-center space-x-2">
                                        <div
                                            class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ formatDate(event.start_date) }}</div>
                                            <div class="text-xs text-gray-300">{{ formatTime(event.start_date) }}</div>
                                        </div>
                                    </div>

                                    <!-- Participants -->
                                    <div class="flex items-center space-x-2">
                                        <div
                                            class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                                            <Users class="w-4 h-4 text-gray-200" />
                                            <!--svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg-->
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ event.participants_count || 0 }} inscrits</div>
                                            <div v-if="event.max_participants" class="text-xs text-gray-300">
                                                sur {{ event.max_participants }}
                                            </div>
                                            <div v-else class="text-xs text-gray-300">Illimité</div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Prix et action -->
                                <div class="flex items-center justify-between pt-2">
                                    <div class="text-lg font-bold">
                                        {{ event.price ? event.price + '€' : 'Gratuit' }}
                                    </div>
                                    <div
                                        class="flex items-center text-blue-200 group-hover:text-blue-100 transition-colors">
                                        <span class="text-sm font-medium mr-2">En savoir plus</span>
                                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>

                            </div>
                        </div>

                        </Link>
                    </div>
                </div>

                <!-- Indicateurs de pagination -->
                <div v-if="props.events.length > 1" class="absolute bottom-4 left-1/2 transform -translate-x-1/2">
                    <div class="flex items-center space-x-2">
                        <button v-for="(event, index) in props.events" :key="index" @click="goToEvent(index)"
                            class="transition-all duration-300"
                            :class="index === currentIndex ? 'w-8 h-2 bg-white rounded-full' : 'w-2 h-2 bg-white/40 rounded-full hover:bg-white/60'">
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer avec lien vers tous les événements -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
            <Link :href="route('events.index')"
                class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors group">
            Voir tous les événements
            <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
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