<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import NotificationItem from '@/Components/NotificationItem.vue';
import EventsDisplay from '@/Components/EventsDisplay.vue';

const user = usePage().props.auth.user;

const props = defineProps({
    upcoming_events: {
        type: Array,
    },
    published_articles_count: {
        type: Number,
    },
    total_likes: {
        type: Number,
    },
    total_comments: {
        type: Number,
    },
});


// État des données
const isLoading = ref(true);
const currentEventIndex = ref(0);
const autoPlayInterval = ref(null);
const isAutoPlaying = ref(true);

// Nouvelles données pour les articles
const recentArticles = ref([]);

const popularArticles = ref([]);

// Simuler des notifications pour le moment
const notifications = ref([
    {
        id: 1,
        type: 'event_updated',
        title: 'Événement modifié',
        message: 'L\'horaire de la compétition régionale a été modifié',
        created_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
        read_at: null,
        action_url: '/events/1'
    },
    {
        id: 2,
        type: 'document_expiring',
        title: 'Certificat médical',
        message: 'Votre certificat médical expire dans 15 jours',
        created_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
        read_at: null,
        action_url: '/certificats'
    },
    {
        id: 3,
        type: 'membership_expiring',
        title: 'Cotisation à renouveler',
        message: 'Votre adhésion expire dans 30 jours',
        created_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
        read_at: new Date().toISOString(),
        action_url: '/membership'
    }
]);

// Calculer les données d'adhésion
const membershipData = computed(() => {
    const status = user.membership_status;
    const timeLeft = user.membership_time_left;

    let statusText = '';
    let statusClasses = '';
    let nextDate = '';
    let bgClasses = '';

    switch (status) {
        case 1:
            statusText = 'Actif';
            statusClasses = 'text-green-700 dark:text-green-300';
            bgClasses = 'from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-200 dark:border-green-800';
            if (timeLeft) {
                const months = Math.floor(timeLeft / 30);
                const days = timeLeft % 30;
                nextDate = months > 0 ? `${months}m ${days}j` : `${days}j`;
            }
            break;
        case 2:
            statusText = 'Expiré';
            statusClasses = 'text-orange-700 dark:text-orange-300';
            bgClasses = 'from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 border-orange-200 dark:border-orange-800';
            nextDate = 'Récent';
            break;
        case 0:
            statusText = 'Inactif';
            statusClasses = 'text-gray-700 dark:text-gray-300';
            bgClasses = 'from-gray-50 to-gray-100 dark:from-gray-900/20 dark:to-gray-800/20 border-gray-200 dark:border-gray-700';
            nextDate = 'Ancien';
            break;
        default:
            statusText = 'Inconnu';
            statusClasses = 'text-gray-700 dark:text-gray-300';
            bgClasses = 'from-gray-50 to-gray-100 dark:from-gray-900/20 dark:to-gray-800/20 border-gray-200 dark:border-gray-700';
            nextDate = '—';
    }

    return { statusText, statusClasses, nextDate, bgClasses };
});

const unreadNotifications = computed(() =>
    notifications.value.filter(n => !n.read_at).length
);

const recentNotifications = computed(() =>
    notifications.value.slice(0, 4)
);

// Fonctions utilitaires pour les événements
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

// Fonctions du carrousel
function nextEvent() {
    currentEventIndex.value = (currentEventIndex.value + 1) % props.upcoming_events.length;
}

function prevEvent() {
    currentEventIndex.value = currentEventIndex.value === 0
        ? props.upcoming_events.length - 1
        : currentEventIndex.value - 1;
}

function goToEvent(index) {
    currentEventIndex.value = index;
}

function startAutoPlay() {
    if (props.upcoming_events.length > 1) {
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

function getFirstMembershipYear() {
    if (user.first_membership_date) {
        return new Date(user.first_membership_date).getFullYear();
    }
    return '—';
}

// Fonctions de gestion des notifications
function markNotificationAsRead(id) {
    const notification = notifications.value.find(n => n.id === id);
    if (notification && !notification.read_at) {
        notification.read_at = new Date().toISOString();
        // TODO: Appel API pour marquer comme lu
    }
}

function dismissNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index !== -1) {
        notifications.value.splice(index, 1);
        // TODO: Appel API pour supprimer
    }
}
</script>

<template>

    <Head title="Espace Personnel" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Espace Personnel
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Bienvenue, {{ user.firstname }} {{ user.lastname }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8 lg:px-60 min-h-screen flex items-start justify-center">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Layout en 3 colonnes selon votre croquis -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- Colonne gauche - Accès rapide (3 colonnes) -->
                    <div class="lg:col-span-3 space-y-6">
                        <div
                            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Accès rapide</h3>

                            <div class="space-y-3">
                                <Link :href="route('profile.profile')"
                                    class="group flex items-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all">
                                <div
                                    class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">
                                    Mon Profil
                                </span>
                                </Link>

                                <Link :href="route('certificats')"
                                    class="group flex items-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all">
                                <div
                                    class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-3 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-green-700 dark:group-hover:text-green-300 transition-colors">
                                    Mes Documents
                                </span>
                                </Link>

                                <Link :href="route('events.index')"
                                    class="group flex items-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-orange-300 dark:hover:border-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all">
                                <div
                                    class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mr-3 group-hover:bg-orange-200 dark:group-hover:bg-orange-900/50 transition-colors">
                                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-orange-700 dark:group-hover:text-orange-300 transition-colors">
                                    Tous les événements
                                </span>
                                </Link>

                                <!-- Nouveau lien Articles -->
                                <Link :href="route('articles.index')"
                                    class="group flex items-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all">
                                <div
                                    class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center mr-3 group-hover:bg-indigo-200 dark:group-hover:bg-indigo-900/50 transition-colors">
                                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">
                                    Articles
                                </span>
                                </Link>

                                <Link :href="route('membership.create')"
                                    class="group flex items-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all">
                                <div
                                    class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-purple-700 dark:group-hover:text-purple-300 transition-colors">
                                    Adhésion
                                </span>
                                </Link>

                                <!-- Liens spéciaux pour admin/animateur -->
                                <Link v-if="user.is_admin" :href="route('admin.dashboard')"
                                    class="group flex items-center p-3 rounded-xl border-2 border-purple-300 dark:border-purple-600 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-all">
                                <div
                                    class="w-10 h-10 bg-purple-200 dark:bg-purple-800/50 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-300 dark:group-hover:bg-purple-800/70 transition-colors">
                                    <svg class="w-5 h-5 text-purple-700 dark:text-purple-300" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-purple-700 dark:text-purple-300">
                                    Administration
                                </span>
                                </Link>

                                <Link v-if="user.is_animator" :href="route('events.manage')"
                                    class="group flex items-center p-3 rounded-xl border-2 border-yellow-300 dark:border-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-all">
                                <div
                                    class="w-10 h-10 bg-yellow-200 dark:bg-yellow-800/50 rounded-lg flex items-center justify-center mr-3 group-hover:bg-yellow-300 dark:group-hover:bg-yellow-800/70 transition-colors">
                                    <svg class="w-5 h-5 text-yellow-700 dark:text-yellow-300" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-yellow-700 dark:text-yellow-300">
                                    Mes Événements
                                </span>
                                </Link>
                            </div>
                        </div>

                        <!-- Mes articles récents -->
                        <div
                            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mes articles</h3>
                                <Link :href="route('articles.create')"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Nouveau
                                </Link>
                            </div>

                            <div v-if="recentArticles.length > 0" class="space-y-3">
                                <div v-for="article in recentArticles.slice(0, 3)" :key="article.id"
                                    class="border-b border-gray-200 dark:border-gray-700 pb-3 last:border-b-0">
                                    <Link :href="route('articles.show', article.id)"
                                        class="block hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg p-2 -m-2">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1 text-sm line-clamp-2">
                                        {{ article.title }}
                                    </h4>
                                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ formatDate(article.publish_date) }}</span>
                                        <span>{{ article.views_count }} vues</span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" />
                                            </svg>
                                            {{ article.likes_count }}
                                        </span>
                                    </div>
                                    </Link>
                                </div>
                            </div>

                            <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400">
                                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-sm mb-2">Aucun article publié</p>
                                <Link :href="route('articles.create')"
                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                Créer votre premier article
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne centrale - Contenu principal (6 colonnes) -->
                    <div class="lg:col-span-6 space-y-8 w-full">
                        <!-- Bandeau d'annonces -->
                        <div
                            class="bg-gradient-to-r from-blue-50 to-orange-50 dark:from-blue-900/20 dark:to-orange-900/20 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-6 h-20 flex items-center justify-center">
                            <div class="text-center">
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Espace réservé pour des annonces
                                    importantes
                                </p>
                            </div>
                        </div>

                        <!-- EventsDisplay pleine largeur sur mobile avec breakout -->
                        <div class="w-full lg:w-auto events-breakout lg:events-normal">
                            <EventsDisplay :events="props.upcoming_events" class="w-full"></EventsDisplay>
                        </div>

                        <!-- Articles populaires de la communauté -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        Articles populaires
                                    </h3>
                                    <Link :href="route('articles.index')"
                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                    Voir tous les articles
                                    </Link>
                                </div>

                                <div v-if="popularArticles.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <article v-for="article in popularArticles.slice(0, 4)" :key="article.id"
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                                        <!-- Image mise en avant -->
                                        <div v-if="article.featured_image" class="aspect-video overflow-hidden">
                                            <img :src="article.featured_image.url" :alt="article.title"
                                                class="w-full h-full object-cover hover:scale-105 transition-transform cursor-pointer"
                                                @click="$inertia.visit(route('articles.show', article.id))" />
                                        </div>

                                        <!-- Contenu -->
                                        <div class="p-4">
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2 line-clamp-2">
                                                <Link :href="route('articles.show', article.id)"
                                                    class="hover:text-blue-600 dark:hover:text-blue-400">
                                                {{ article.title }}
                                                </Link>
                                            </h4>

                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                                {{ article.excerpt }}
                                            </p>

                                            <div
                                                class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ article.author.firstname }} {{ article.author.lastname
                                                }}</span>
                                                <div class="flex items-center gap-2">
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path
                                                                d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" />
                                                        </svg>
                                                        {{ article.likes_count }}
                                                    </span>
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                        </svg>
                                                        {{ article.comments_count }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                </div>

                                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p>Aucun article populaire pour le moment</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne droite - Adhésion et Notifications (3 colonnes) -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Statistiques d'adhésion -->
                        <div class="bg-gradient-to-br border rounded-2xl shadow-sm p-6"
                            :class="membershipData.bgClasses">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Adhésion</h3>
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="text-center">
                                    <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Restant</div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{
                                        membershipData.nextDate }}
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Depuis</div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{
                                        getFirstMembershipYear() }}
                                    </div>
                                </div>
                            </div>

                            <div class="text-center p-3 bg-white/60 dark:bg-black/10 rounded-xl">
                                <span class="font-medium text-sm" :class="membershipData.statusClasses">
                                    {{ membershipData.statusText }}
                                </span>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div
                            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h3>
                                <span v-if="unreadNotifications > 0"
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ unreadNotifications }} non lue{{ unreadNotifications > 1 ? 's' : '' }}
                                </span>
                            </div>

                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                <NotificationItem v-for="notification in recentNotifications" :key="notification.id"
                                    :notification="notification" @mark-as-read="markNotificationAsRead"
                                    @dismiss="dismissNotification" />
                            </div>

                            <div v-if="notifications.length === 0"
                                class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-5 5v-5z" />
                                </svg>
                                <p class="text-sm">Aucune notification</p>
                            </div>

                            <div v-if="notifications.length > 4" class="mt-4 text-center">
                                <button
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium">
                                    Voir toutes les notifications
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Scrollbar personnalisée pour les notifications */
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3);
}

/* Animations */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.grid>div {
    animation: slideInUp 0.6s ease-out;
}

.grid>div:nth-child(1) {
    animation-delay: 0.1s;
}

.grid>div:nth-child(2) {
    animation-delay: 0.2s;
}

.grid>div:nth-child(3) {
    animation-delay: 0.3s;
}

/* Responsive */
@media (max-width: 1023px) {

    .lg\:col-span-3,
    .lg\:col-span-6 {
        grid-column: span 12 / span 12;
    }
}
</style>