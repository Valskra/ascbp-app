<script setup>
import { ref, computed } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useDateFormat } from '@vueuse/core';
import DownloadFileIcon from '@/Components/svg/downloadFileIcon.vue';

const props = defineProps({
    events: Object,
    stats: Object,
    filters: Object
});

// États des filtres
const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');
const selectedStatus = ref(props.filters.status || '');
const selectedParticipants = ref(props.filters.participants || '');
const sortBy = ref(props.filters.sort || 'start_date');
const sortOrder = ref(props.filters.order || 'desc');

const statTitles = {
    total: "Total événements",
    upcoming: "À venir",
    ongoing: "En cours",
    past: "Terminés",
    total_participants: "Total participants",
    competitions: "Compétitions",
    entrainements: "Entraînements",
    manifestations: "Manifestations",
};

const categoryOptions = [
    { value: '', label: 'Toutes les catégories' },
    { value: 'competition', label: '🏆 Compétitions' },
    { value: 'entrainement', label: '💪 Entraînements' },
    { value: 'manifestation', label: '🎉 Manifestations' },
];

const statusOptions = [
    { value: '', label: 'Tous les statuts' },
    { value: 'upcoming', label: 'À venir' },
    { value: 'ongoing', label: 'En cours' },
    { value: 'past', label: 'Terminés' },
    { value: 'registration_open', label: 'Inscriptions ouvertes' },
    { value: 'registration_closed', label: 'Inscriptions fermées' },
];

const participantsOptions = [
    { value: '', label: 'Tous' },
    { value: 'empty', label: 'Sans participants' },
    { value: 'partial', label: 'Partiellement remplis' },
    { value: 'full', label: 'Complets' },
];

const sortOptions = [
    { value: 'start_date', label: 'Date de début' },
    { value: 'title', label: 'Titre' },
    { value: 'category', label: 'Catégorie' },
    { value: 'organizer', label: 'Organisateur' },
    { value: 'registration_open', label: 'Ouverture inscriptions' },
    { value: 'registration_close', label: 'Fermeture inscriptions' },
    { value: 'participants_count', label: 'Nombre de participants' },
    { value: 'created_at', label: 'Date de création' },
];

function fetchEvents() {
    const params = {
        search: search.value || undefined,
        category: selectedCategory.value || undefined,
        status: selectedStatus.value || undefined,
        participants: selectedParticipants.value || undefined,
        sort: sortBy.value !== 'start_date' ? sortBy.value : undefined,
        order: sortOrder.value !== 'desc' ? sortOrder.value : undefined,
    };

    // Supprimer les paramètres undefined
    Object.keys(params).forEach(key => params[key] === undefined && delete params[key]);

    router.get(route('admin.events'), params, {
        preserveState: true,
        replace: true
    });
}

function resetFilters() {
    search.value = '';
    selectedCategory.value = '';
    selectedStatus.value = '';
    selectedParticipants.value = '';
    sortBy.value = 'start_date';
    sortOrder.value = 'desc';
    fetchEvents();
}

function loadMore() {
    if (!props.events.next_page_url) return;
    router.get(props.events.next_page_url, {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['events']
    });
}

function exportCSV() {
    window.location.href = route('admin.events.export');
}

function toggleSort(column) {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
    fetchEvents();
}

function formatDate(date) {
    if (!date) return '—';
    return useDateFormat(date, 'DD/MM/YYYY HH:mm').value;
}

function formatDateShort(date) {
    if (!date) return '—';
    return useDateFormat(date, 'DD/MM/YY').value;
}

function getStatusColor(event) {
    const now = new Date();
    const start = new Date(event.start_date);
    const end = new Date(event.end_date);

    if (end < now) return 'bg-gray-500';
    if (start <= now && end >= now) return 'bg-green-500';
    return 'bg-blue-500';
}

function getStatusText(event) {
    const now = new Date();
    const start = new Date(event.start_date);
    const end = new Date(event.end_date);

    if (end < now) return 'Terminé';
    if (start <= now && end >= now) return 'En cours';
    return 'À venir';
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

function getRegistrationStatus(event) {
    const now = new Date();
    const regOpen = event.registration_open ? new Date(event.registration_open) : null;
    const regClose = event.registration_close ? new Date(event.registration_close) : null;
    const start = new Date(event.start_date);

    if (start <= now) return { text: 'Événement commencé', color: 'gray' };
    if (regOpen && now < regOpen) return { text: 'Pas encore ouvert', color: 'yellow' };
    if (regClose && now > regClose) return { text: 'Fermé', color: 'red' };
    return { text: 'Ouvert', color: 'green' };
}

function getSortIcon(column) {
    if (sortBy.value !== column) return '-';
    return sortOrder.value === 'asc' ? '▲' : '▼';
}

const hasActiveFilters = computed(() => {
    return search.value || selectedCategory.value || selectedStatus.value ||
        selectedParticipants.value || sortBy.value !== 'start_date' || sortOrder.value !== 'desc';
});
</script>

<template>

    <Head title="Gestion des événements" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Gestion des événements
                </h2>
                <div class="flex items-center gap-3">
                    <button @click="exportCSV"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 h-10 flex items-center gap-2">
                        <DownloadFileIcon class="w-5 stroke-white" />
                        Exporter CSV
                    </button>
                    <Link :href="route('events.create')"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 h-10 flex items-center gap-2">
                    ➕ Nouvel événement
                    </Link>
                </div>
            </div>
        </template>

        <div class="p-6 space-y-6">
            <!-- Statistiques -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
                <div v-for="(value, key) in stats" :key="key" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ value }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ statTitles[key] }}</div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- Recherche -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Rechercher
                        </label>
                        <input v-model="search" @input="fetchEvents" type="text"
                            placeholder="Titre, catégorie, organisateur..."
                            class="w-full border rounded p-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                    </div>

                    <!-- Catégorie -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Catégorie
                        </label>
                        <select v-model="selectedCategory" @change="fetchEvents"
                            class="w-full border rounded p-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option v-for="option in categoryOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Statut -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Statut
                        </label>
                        <select v-model="selectedStatus" @change="fetchEvents"
                            class="w-full border rounded p-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Participants -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Participants
                        </label>
                        <select v-model="selectedParticipants" @change="fetchEvents"
                            class="w-full border rounded p-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option v-for="option in participantsOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <!-- Tri -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Trier par :</label>
                        <select v-model="sortBy" @change="fetchEvents"
                            class="border rounded p-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <button @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'; fetchEvents()"
                            class="px-3 py-2 border rounded text-sm hover:bg-gray-50 dark:hover:bg-gray-700 dark:border-gray-600">
                            {{ sortOrder === 'asc' ? '▲' : '▼' }}

                        </button>
                    </div>

                    <!-- Réinitialiser -->
                    <button v-if="hasActiveFilters" @click="resetFilters"
                        class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                        Réinitialiser
                    </button>
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

            <!-- Tableau des événements -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th @click="toggleSort('title')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div class="flex items-center gap-1">
                                        Événement {{ getSortIcon('title') }}
                                    </div>
                                </th>
                                <th @click="toggleSort('category')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div class="flex items-center gap-1">
                                        Catégorie {{ getSortIcon('category') }}
                                    </div>
                                </th>
                                <th @click="toggleSort('start_date')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div class="flex items-center gap-1">
                                        Dates {{ getSortIcon('start_date') }}
                                    </div>
                                </th>
                                <th @click="toggleSort('registration_open')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div class="flex items-center gap-1">
                                        Inscriptions {{ getSortIcon('registration_open') }}
                                    </div>
                                </th>
                                <th @click="toggleSort('participants_count')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div class="flex items-center gap-1">
                                        Participants {{ getSortIcon('participants_count') }}
                                    </div>
                                </th>
                                <th @click="toggleSort('organizer')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <div class="flex items-center gap-1">
                                        Organisateur {{ getSortIcon('organizer') }}
                                    </div>
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
                            <tr v-for="event in events.data" :key="event.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <!-- Événement -->
                                <td class="px-6 py-4">
                                    <div class="flex items-start space-x-3">
                                        <span class="text-lg">{{ getCategoryIcon(event.category) }}</span>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ event.title }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                ID: {{ event.id }}
                                            </div>
                                            <div v-if="event.price" class="text-xs text-green-600 dark:text-green-400">
                                                {{ event.price }}€
                                            </div>
                                            <div v-else class="text-xs text-gray-500 dark:text-gray-400">
                                                Gratuit
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Catégorie -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getCategoryColor(event.category)">
                                        {{ event.category }}
                                    </span>
                                </td>

                                <!-- Dates -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-gray-300">
                                        <div class="font-medium">{{ formatDateShort(event.start_date) }}</div>
                                        <div v-if="event.end_date && formatDateShort(event.end_date) !== formatDateShort(event.start_date)"
                                            class="text-xs text-gray-500 dark:text-gray-400">
                                            → {{ formatDateShort(event.end_date) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Inscriptions -->
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <div v-if="event.registration_open" class="text-gray-900 dark:text-gray-300">
                                            📅 {{ formatDateShort(event.registration_open) }}
                                        </div>
                                        <div v-if="event.registration_close"
                                            class="text-gray-500 dark:text-gray-400 text-xs">
                                            🔒 {{ formatDateShort(event.registration_close) }}
                                        </div>
                                        <div class="mt-1">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                :class="{
                                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': getRegistrationStatus(event).color === 'green',
                                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': getRegistrationStatus(event).color === 'yellow',
                                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': getRegistrationStatus(event).color === 'red',
                                                    'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': getRegistrationStatus(event).color === 'gray'
                                                }">
                                                {{ getRegistrationStatus(event).text }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Participants -->
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ event.participants_count }}
                                            <span v-if="event.max_participants" class="text-gray-500">
                                                / {{ event.max_participants }}
                                            </span>
                                        </div>
                                        <div v-if="event.max_participants"
                                            class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1 mt-1">
                                            <div class="h-1 rounded-full transition-all" :class="{
                                                'bg-green-500': (event.participants_count / event.max_participants) <= 0.7,
                                                'bg-yellow-500': (event.participants_count / event.max_participants) > 0.7 && (event.participants_count / event.max_participants) < 1,
                                                'bg-red-500': (event.participants_count / event.max_participants) >= 1
                                            }"
                                                :style="{ width: `${Math.min(100, (event.participants_count / event.max_participants) * 100)}%` }">
                                            </div>
                                        </div>
                                        <div class="flex gap-1 mt-1">
                                            <span v-if="event.members_only"
                                                class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 rounded"
                                                title="Adhérents uniquement">
                                                👥
                                            </span>
                                            <span v-if="event.requires_medical_certificate"
                                                class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded"
                                                title="Certificat médical requis">
                                                🏥
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Organisateur -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-gray-300">
                                        {{ event.organizer.firstname }} {{ event.organizer.lastname }}
                                    </div>
                                </td>

                                <!-- Statut -->
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full text-white"
                                        :class="getStatusColor(event)">
                                        {{ getStatusText(event) }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
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
                                        <Link :href="route('admin.events.participants', event.id)"
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                            title="Gérer les participants">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                        </svg>
                                        </Link>

                                        <!-- Modifier -->
                                        <Link :href="route('events.edit', event.id)"
                                            class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300"
                                            title="Modifier l'événement">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Message si aucun événement -->
                <div v-if="events.data.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun événement trouvé</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ hasActiveFilters ? 'Essayez de modifier vos filtres.' : 'Aucun événement n\'a été créé.' }}
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="events.next_page_url" class="bg-gray-50 dark:bg-gray-700 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            Affichage de {{ events.from }} à {{ events.to }} sur {{ events.total }} événements
                        </div>
                        <button @click="loadMore"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                            Charger plus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>