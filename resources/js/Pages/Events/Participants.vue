<script setup>
import { ref } from 'vue';
import { router, Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useDateFormat } from '@vueuse/core';
import DownloadFileIcon from '@/Components/svg/downloadFileIcon.vue';

const props = defineProps({
    event: Object,
    registrations: Object,
    stats: Object,
    filters: Object
});

// États des filtres
const search = ref(props.filters.search || '');
const sortBy = ref(props.filters.sort || 'registration_date');
const sortOrder = ref(props.filters.order || 'desc');

// Modal de désinscription
const showUnregisterModal = ref(false);
const selectedRegistration = ref(null);

const unregisterForm = useForm({});

const sortOptions = [
    { value: 'registration_date', label: 'Date d\'inscription' },
    { value: 'name', label: 'Nom' },
    { value: 'email', label: 'Email' },
    { value: 'amount', label: 'Montant payé' },
];

function fetchParticipants() {
    const params = {
        search: search.value || undefined,
        sort: sortBy.value !== 'registration_date' ? sortBy.value : undefined,
        order: sortOrder.value !== 'desc' ? sortOrder.value : undefined,
    };

    // Supprimer les paramètres undefined
    Object.keys(params).forEach(key => params[key] === undefined && delete params[key]);

    router.get(route('admin.events.participants', props.event.id), params, {
        preserveState: true,
        replace: true
    });
}

function toggleSort(column) {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
    fetchParticipants();
}

function getSortIcon(column) {
    if (sortBy.value !== column) return '-';
    return sortOrder.value === 'asc' ? '▲' : '▼';
}
function loadMore() {
    if (!props.registrations.next_page_url) return;
    router.get(props.registrations.next_page_url, {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['registrations']
    });
}

function exportParticipants() {
    window.location.href = route('admin.events.participants.export', props.event.id);
}

function formatDate(date) {
    if (!date) return '—';
    return useDateFormat(date, 'DD/MM/YYYY').value;
}

function formatDateTime(date) {
    if (!date) return '—';
    return useDateFormat(date, 'DD/MM/YYYY HH:mm').value;
}

function getMembershipStatusColor(status) {
    const colors = {
        '-1': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        '0': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        '1': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        '2': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    };
    return colors[status] || colors['0'];
}

function getMembershipStatusText(status) {
    const texts = {
        '-1': 'Désactivé',
        '0': 'Ancien > 1 an',
        '1': 'Adhérent actif',
        '2': 'Ancien < 1 an',
    };
    return texts[status] || 'Inconnu';
}

function confirmUnregister(registration) {
    selectedRegistration.value = registration;
    showUnregisterModal.value = true;
}

function unregisterUser() {
    if (!selectedRegistration.value) return;

    unregisterForm.delete(route('admin.events.participants.unregister', {
        event: props.event.id,
        registration: selectedRegistration.value.id
    }), {
        onSuccess: () => {
            showUnregisterModal.value = false;
            selectedRegistration.value = null;
        },
        onFinish: () => {
            unregisterForm.reset();
        }
    });
}

function cancelUnregister() {
    showUnregisterModal.value = false;
    selectedRegistration.value = null;
}

function getCategoryColor(category) {
    const colors = {
        'competition': 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900',
        'entrainement': 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-900',
        'manifestation': 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900'
    };
    return colors[category] || 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-900';
}

function getCategoryIcon(category) {
    const icons = {
        'competition': '🏆',
        'entrainement': '💪',
        'manifestation': '🎉'
    };
    return icons[category] || '📅';
}
</script>

<template>

    <Head :title="`Participants - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('admin.events')"
                        class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux événements
                    </Link>

                    <div class="text-gray-300 dark:text-gray-600">•</div>

                    <div class="flex items-center space-x-2">
                        <span class="text-lg">{{ getCategoryIcon(event.category) }}</span>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                            {{ event.title }}
                        </h2>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                            :class="getCategoryColor(event.category)">
                            {{ event.category }}
                        </span>
                    </div>
                </div>

                <button @click="exportParticipants"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 h-10 flex items-center gap-2">
                    <DownloadFileIcon class="w-5 stroke-white" />
                    Exporter participants
                </button>
            </div>
        </template>

        <div class="p-6 space-y-6">
            <!-- Informations sur l'événement -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Date de l'événement</h3>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ formatDateTime(event.start_date) }}
                            <span v-if="event.end_date && formatDate(event.end_date) !== formatDate(event.start_date)">
                                <br>→ {{ formatDateTime(event.end_date) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Capacité</h3>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ event.max_participants || 'Illimitée' }}
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Prix</h3>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ event.price ? event.price + '€' : 'Gratuit' }}
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Statut</h3>
                        <p class="mt-1 text-sm">
                            <span v-if="new Date(event.end_date || event.start_date) < new Date()"
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                Terminé
                            </span>
                            <span
                                v-else-if="new Date(event.start_date) <= new Date() && new Date(event.end_date || event.start_date) >= new Date()"
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                En cours
                            </span>
                            <span v-else
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                À venir
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Statistiques des participants -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total participants</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total_participants
                                }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Participants payants</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.paid_participants
                                }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Participants gratuits</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.free_participants
                                }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenus totaux</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ stats.total_revenue ? stats.total_revenue.toFixed(2) + '€' : '0€' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages flash -->
            <div v-if="$page.props.flash?.success"
                class="p-4 bg-green-100 dark:bg-green-900 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-800 dark:text-green-200">{{ $page.props.flash.success }}</p>
            </div>

            <div v-if="$page.props.flash?.error"
                class="p-4 bg-red-100 dark:bg-red-900 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-red-800 dark:text-red-200">{{ $page.props.flash.error }}</p>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <!-- Recherche -->
                    <div class="flex-1 min-w-64">
                        <input v-model="search" @input="fetchParticipants" type="text"
                            placeholder="Rechercher par nom ou email..."
                            class="w-full border rounded p-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                    </div>

                    <!-- Tri -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Trier par :</label>
                        <select v-model="sortBy" @change="fetchParticipants"
                            class="border rounded p-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <button @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'; fetchParticipants()"
                            class="px-3 py-2 border rounded text-sm hover:bg-gray-50 dark:hover:bg-gray-700 dark:border-gray-600">
                            {{ sortOrder === 'asc' ? '▲' : '▼' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tableau des participants -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th @click="toggleSort('name')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div class="flex items-center gap-1">
                                        Participant {{ getSortIcon('name') }}
                                    </div>
                                </th>
                                <th @click="toggleSort('email')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div class="flex items-center gap-1">
                                        Contact {{ getSortIcon('email') }}
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Statut adhésion
                                </th>
                                <th @click="toggleSort('registration_date')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div class="flex items-center gap-1">
                                        Date inscription {{ getSortIcon('registration_date') }}
                                    </div>
                                </th>
                                <th @click="toggleSort('amount')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div class="flex items-center gap-1">
                                        Montant {{ getSortIcon('amount') }}
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="registration in registrations.data" :key="registration.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <!-- Participant -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                                    {{ registration.user.firstname.charAt(0) }}{{
                                                        registration.user.lastname.charAt(0) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ registration.user.firstname }} {{ registration.user.lastname }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                ID: {{ registration.user.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-gray-300">
                                        <div>{{ registration.user.email }}</div>
                                        <div v-if="registration.user.phone" class="text-gray-500 dark:text-gray-400">
                                            {{ registration.user.phone }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Statut adhésion -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getMembershipStatusColor(registration.user.membership_status)">
                                        {{ getMembershipStatusText(registration.user.membership_status) }}
                                    </span>
                                </td>

                                <!-- Date inscription -->
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                    {{ formatDate(registration.registration_date) }}
                                </td>

                                <!-- Montant -->
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <span v-if="registration.amount > 0"
                                            class="text-green-600 dark:text-green-400 font-medium">
                                            {{ registration.amount }}€
                                        </span>
                                        <span v-else class="text-gray-500 dark:text-gray-400">
                                            Gratuit
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <!-- Voir profil -->
                                        <Link :href="route('admin.users')"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Voir le profil utilisateur">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        </Link>

                                        <!-- Désinscrire -->
                                        <button v-if="registration.can_unregister"
                                            @click="confirmUnregister(registration)"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            title="Désinscrire de l'événement">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>

                                        <!-- Impossible de désinscrire -->
                                        <span v-else class="text-gray-400 dark:text-gray-600"
                                            title="Impossible de désinscrire (événement commencé ou paiement effectué)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                                            </svg>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Message si aucun participant -->
                <div v-if="registrations.data.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun participant</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ search ?
                            'Aucun participant ne correspond à votre recherche.' :
                            'Aucun participant inscrit à cet événement.' }}
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="registrations.next_page_url" class="bg-gray-50 dark:bg-gray-700 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            Affichage de {{ registrations.from }} à {{ registrations.to }} sur {{ registrations.total }}
                            participants
                        </div>
                        <button @click="loadMore"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                            Charger plus
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation de désinscription -->
        <div v-if="showUnregisterModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
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
                            désinscription
                        </h3>
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                                Êtes-vous sûr de vouloir désinscrire "<strong>{{ selectedRegistration?.user.firstname }}
                                    {{
                                        selectedRegistration?.user.lastname }}</strong>" de cet événement ?
                                Cette action est irréversible.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center gap-3 px-4 py-3">
                    <button @click="cancelUnregister"
                        class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-400 dark:hover:bg-gray-500">
                        Annuler
                    </button>
                    <button @click="unregisterUser" :disabled="unregisterForm.processing"
                        class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 disabled:opacity-50">
                        {{ unregisterForm.processing ? 'Désinscription...' : 'Désinscrire' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>