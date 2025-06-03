<script setup>
import { ref } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useDateFormat } from '@vueuse/core'
import DownloadFileIcon from '@/Components/svg/downloadFileIcon.vue';

const props = defineProps({
    users: Object,
    stats: Object,
    filters: Object
});

const search = ref(props.filters.search || '');

const statTitles = {
    active: "Adhérents actifs",
    expired_recently: "Expirés récents",
    expired_long: "Expirés > 1 an",
    disabled: "Désactivés",
    total: "Total membres",
};

function fetchUsers() {
    router.get(route('admin.users'), { search: search.value }, { preserveState: true, replace: true });
}

function loadMore() {
    if (!props.users.next_page_url) return;
    router.get(props.users.next_page_url, {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['users']
    });
}

function exportCSV() {
    window.location.href = route('admin.export_users');
}


function statusColor(user) {
    if (user.status === 'disabled') return 'bg-gray-500';
    switch (user.membership_status) {
        case 1: return 'bg-green-500';
        case 2: return 'bg-orange-400';
        case 0: return 'bg-red-500';
        default: return 'bg-gray-300';

    }
}
</script>

<template>

    <Head title="Utilisateurs" />

    <AuthenticatedLayout admin="true">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Gestion des membres
                </h2>
            </div>
        </template>

        <div class="p-6 space-y-6">
            <div class="flex flex-wrap items-end justify-between mt-6 gap-4">
                <!-- Bloc statistiques -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 w-full sm:w-64">

                    <div v-for="(value, key) in stats" :key="key"
                        class="flex justify-between items-center py-1 border-b last:border-b-0">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ statTitles[key] }}</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ value }}</span>
                    </div>
                </div>

                <!-- Recherche + export collés à droite -->
                <div class="flex justify-end w-full sm:w-auto flex-1">
                    <div class="flex items-center gap-12">
                        <button @click="exportCSV"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 h-10 flex items-center gap-2">
                            <!-- Icône -->
                            <DownloadFileIcon class="w-5 stroke-white" />
                            Exporter CSV
                        </button>


                        <input v-model="search" @input="fetchUsers" type="text"
                            placeholder="Rechercher par prénom ou nom..."
                            class="border rounded p-2 h-10 min-w-[400px]" />
                    </div>
                </div>
            </div>




            <!-- Liste des utilisateurs -->
            <div class="overflow-x-auto mt-4">
                <table class="min-w-full bg-white dark:bg-gray-800 rounded">
                    <thead>
                        <tr>
                            <th class="p-2 text-gray-900 dark:text-white">Statut</th>
                            <th class="p-2 text-gray-900 dark:text-white">ID</th>
                            <th class="p-2 text-gray-900 dark:text-white">Prénom</th>
                            <th class="p-2 text-gray-900 dark:text-white">Nom</th>
                            <th class="p-2 text-gray-900 dark:text-white">Date de naissance</th>
                            <th class="p-2 text-gray-900 dark:text-white">Code Postal</th>
                            <th class="p-2 text-gray-900 dark:text-white">Téléphone</th>
                            <th class="p-2 text-gray-900 dark:text-white">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id"
                            class="text-center text-gray-900 dark:text-gray-300 dark:hover:text-white hover:dark:bg-gray-700 hover:bg-gray-100">
                            <td class="p-2">
                                <span :class="statusColor(user) + ' inline-block w-4 h-4 rounded-full'"></span>
                            </td>
                            <td class="p-2">{{ user.id }}</td>
                            <td class="p-2">{{ user.firstname }}</td>
                            <td class="p-2">{{ user.lastname }}</td>
                            <td class="p-2">
                                {{ user.birth_date ? useDateFormat(user.birth_date, 'DD/MM/YYYY') : '__/__/____' }}
                            </td>
                            <td class="p-2 ">{{
                                user.postal_code }}
                            </td>
                            <td class="p-2 ">{{ user.phone }}
                            </td>
                            <td class="p-2 ">{{ user.email }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.next_page_url" class="text-center mt-6">
                <button @click="loadMore" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Charger plus
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
