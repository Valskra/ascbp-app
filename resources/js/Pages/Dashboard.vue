<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import NotificationItem from '@/Components/NotificationItem.vue';
import EventsDisplay from '@/Components/EventsDisplay.vue';

const user = usePage().props.auth.user;

// Simuler des données pour les notifications (en production, viendront du backend)
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
    },
    {
        id: 4,
        type: 'event_reminder',
        title: 'Rappel événement',
        message: 'L\'entraînement collectif a lieu demain à 14h',
        created_at: new Date(Date.now() - 6 * 60 * 60 * 1000).toISOString(),
        read_at: null,
        action_url: '/events/2'
    }
]);

// Simuler des événements à venir
const upcomingEvents = ref([
    {
        id: 1,
        title: 'Compétition Régionale de Natation',
        start_date: '2025-06-15T14:00:00',
        category: 'competition',
        participants_count: 12
    },
    {
        id: 2,
        title: 'Entraînement Collectif',
        start_date: '2025-06-10T18:30:00',
        category: 'entrainement',
        participants_count: 8
    },
    {
        id: 3,
        title: 'Assemblée Générale Annuelle',
        start_date: '2025-06-20T19:00:00',
        category: 'manifestation',
        participants_count: 25
    },
    {
        id: 4,
        title: 'Championnat Départemental',
        start_date: '2025-06-25T09:00:00',
        category: 'competition',
        participants_count: 15
    },
    {
        id: 5,
        title: 'Stage d\'été',
        start_date: '2025-07-05T10:00:00',
        category: 'entrainement',
        participants_count: 20
    }
]);

// Calculer les données de cotisation
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

function markNotificationAsRead(id) {
    const notification = notifications.value.find(n => n.id === id);
    if (notification && !notification.read_at) {
        notification.read_at = new Date().toISOString();
        // Ici, vous feriez un appel API pour marquer comme lu
        // router.patch(`/notifications/${id}/mark-as-read`);
    }
}

function dismissNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index !== -1) {
        notifications.value.splice(index, 1);
        // Ici, vous feriez un appel API pour supprimer
        // router.delete(`/notifications/${id}`);
    }
}

function getFirstMembershipYear() {
    if (user.first_membership_date) {
        return new Date(user.first_membership_date).getFullYear();
    }
    return '—';
}
</script>

<template>

    <Head title="Espace Personnel" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Espace Personnel
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Bienvenue, {{ user.firstname }} {{ user.lastname }}
                    </p>
                </div>

                <!-- Actions rapides en en-tête -->
                <div class="flex items-center space-x-3">
                    <!-- Badge Admin -->
                    <div v-if="user.is_admin"
                        class="flex items-center justify-center w-11 h-11 bg-purple-50 dark:bg-purple-900/30 rounded-xl border border-purple-200 dark:border-purple-800"
                        title="Administrateur">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>

                    <!-- Notifications -->
                    <div class="relative">
                        <button
                            class="flex items-center justify-center w-11 h-11 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            title="Notifications">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-5 5v-5zM6 7l3-3 3 3M6 17l3 3 3-3" />
                            </svg>
                        </button>
                        <span v-if="unreadNotifications > 0"
                            class="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 text-xs font-semibold text-white bg-red-500 rounded-full">
                            {{ unreadNotifications }}
                        </span>
                    </div>

                    <!-- Profil -->
                    <Link :href="route('profile.profile')"
                        class="flex items-center justify-center w-11 h-11 bg-blue-50 dark:bg-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-800/50 transition-colors"
                        title="Mon profil">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Layout principal avec nouvelle disposition -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-screen max-h-[calc(100vh-12rem)]">

                    <!-- Colonne gauche - Accès rapide (3 colonnes) -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Accès rapide -->
                        <div
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 h-full">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Accès rapide</h3>

                            <div class="space-y-3">
                                <!-- Mon Profil -->
                                <Link :href="route('profile.profile')"
                                    class="group flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all">
                                <div
                                    class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">Mon
                                    Profil</span>
                                </Link>

                                <!-- Mes Documents -->
                                <Link :href="route('certificats')"
                                    class="group flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all">
                                <div
                                    class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-3 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-green-700 dark:group-hover:text-green-300 transition-colors">Mes
                                    Documents</span>
                                </Link>

                                <!-- Événements -->
                                <Link :href="route('events.index')"
                                    class="group flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-orange-300 dark:hover:border-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all">
                                <div
                                    class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mr-3 group-hover:bg-orange-200 dark:group-hover:bg-orange-900/50 transition-colors">
                                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-orange-700 dark:group-hover:text-orange-300 transition-colors">Tous
                                    les événements</span>
                                </Link>

                                <!-- Adhésion -->
                                <Link :href="route('membership.create')"
                                    class="group flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all">
                                <div
                                    class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-purple-700 dark:group-hover:text-purple-300 transition-colors">Adhésion</span>
                                </Link>

                                <!-- Admin (si admin) -->
                                <Link v-if="user.is_admin" :href="route('admin.dashboard')"
                                    class="group flex items-center p-3 rounded-lg border-2 border-purple-300 dark:border-purple-600 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-all">
                                <div
                                    class="w-10 h-10 bg-purple-200 dark:bg-purple-800/50 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-300 dark:group-hover:bg-purple-800/70 transition-colors">
                                    <svg class="w-5 h-5 text-purple-700 dark:text-purple-300" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium text-purple-700 dark:text-purple-300">Administration</span>
                                </Link>

                                <!-- Gestion événements (si animateur) -->
                                <Link v-if="user.is_animator" :href="route('events.manage')"
                                    class="group flex items-center p-3 rounded-lg border-2 border-yellow-300 dark:border-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-all">
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
                                <span class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Mes
                                    Événements</span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne centrale - Contenu principal (6 colonnes) -->
                    <div class="lg:col-span-6 flex flex-col space-y-6">
                        <!-- Bandeau vide du haut -->
                        <div
                            class="bg-gradient-to-r from-blue-50 to-orange-50 dark:from-blue-900/20 dark:to-orange-900/20 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 h-24 flex items-center justify-center">
                            <div class="text-center">
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Espace réservé pour des annonces
                                    importantes
                                </p>
                            </div>
                        </div>

                        <!-- Événements à venir - Section principale -->
                        <div
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 flex-1">
                            <EventsDisplay />
                        </div>
                    </div>

                    <!-- Colonne droite - Notifications et adhésion (3 colonnes) -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Petit encadré Adhésion -->
                        <div class="bg-gradient-to-br border rounded-xl shadow-sm p-4"
                            :class="membershipData.bgClasses">

                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Adhésion</h3>
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mb-3">
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

                            <div class="text-center p-2 bg-white/60 dark:bg-black/10 rounded text-xs">
                                <span class="font-medium" :class="membershipData.statusClasses">
                                    {{ membershipData.statusText }}
                                </span>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 flex-1">
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
                                        d="M15 17h5l-5 5v-5zM6 7l3-3 3 3M6 17l3 3 3-3" />
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

                <!-- Section informations du compte pour mobile -->
                <div class="mt-6 lg:hidden">
                    <div
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Informations du compte</h3>

                        <!-- Rôles -->
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rôles</h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="role in user.roles" :key="role.id"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ role.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Statut -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-if="user.is_admin"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    Administrateur
                                </span>
                                <span v-if="user.is_animator"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    Animateur
                                </span>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200"
                                    :class="membershipData.statusClasses">
                                    {{ membershipData.statusText }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
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

/* Animation pour les cartes */
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

/* Ajustements pour la hauteur sur grands écrans */
@media (min-width: 1024px) {
    .h-screen {
        height: calc(100vh - 12rem);
    }
}

/* Responsive pour les colonnes */
@media (max-width: 1023px) {

    .lg\:col-span-3,
    .lg\:col-span-6 {
        grid-column: span 12 / span 12;
    }

    .h-screen {
        height: auto;
    }
}
</style>