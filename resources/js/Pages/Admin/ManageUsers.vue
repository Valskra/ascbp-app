<script setup>
import { ref, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useDateFormat } from '@vueuse/core'
import DownloadFileIcon from '@/Components/svg/downloadFileIcon.vue';

const props = defineProps({
    users: {
        type: Object,
        default: () => ({ data: [] })
    },
    stats: Object,
    filters: Object,
    auth: Object,
    roles: Array
});

const search = ref(props.filters?.search || '');

// État de la modal
const isModalOpen = ref(false);
const selectedUser = ref(null);
const selectedRoles = ref([]);
const hoveredRole = ref(null);
const isCreatingRole = ref(false);

// État pour la création de rôle
const newRole = ref({
    name: '',
    display_name: '',
    description: '',
    permissions: {
        manage_admin: false,
        admin_access: false,
        manage_events: false,
        create_events: false,
        manage_members: false,
        manage_articles: false,
        create_articles: false
    }
});

const statTitles = {
    active: "Adhérents actifs",
    expired_recently: "Expirés récents",
    expired_long: "Expirés > 1 an",
    disabled: "Désactivés",
    total: "Total membres",
};

// Permissions de l'utilisateur connecté
const canManageAdmins = computed(() => props.auth?.user?.is_owner || false);
const canManageAnimators = computed(() => props.auth?.user?.is_admin || props.auth?.user?.is_owner || false);

// Informations de pagination
const currentPage = computed(() => props.users?.current_page || 1);
const lastPage = computed(() => props.users?.last_page || 1);
const total = computed(() => props.users?.total || 0);

// Rôles triés par ordre alphabétique et dédupliqués
const sortedUniqueRoles = computed(() => {
    const uniqueRoles = new Map();

    props.roles?.forEach(role => {
        if (!uniqueRoles.has(role.name)) {
            uniqueRoles.set(role.name, role);
        }
    });

    return Array.from(uniqueRoles.values())
        .sort((a, b) => a.display_name.localeCompare(b.display_name));
});

function fetchUsers() {
    router.get(route('admin.users'), { search: search.value }, {
        preserveState: true,
        replace: true
    });
}

function goToPage(page) {
    if (page < 1 || page > lastPage.value) return;

    router.get(route('admin.users'), {
        search: search.value,
        page: page
    }, {
        preserveState: true,
        replace: true
    });
}

function previousPage() {
    if (currentPage.value > 1) {
        goToPage(currentPage.value - 1);
    }
}

function nextPage() {
    if (currentPage.value < lastPage.value) {
        goToPage(currentPage.value + 1);
    }
}

function exportCSV() {
    window.location.href = route('admin.export_users');
}

function statusColor(user) {
    if (user?.status === 'disabled') return 'bg-gray-500';
    switch (user?.membership_status) {
        case 1: return 'bg-green-500';
        case 2: return 'bg-orange-400';
        case 0: return 'bg-red-500';
        default: return 'bg-gray-300';
    }
}

function openRoleModal(user) {
    if (props.auth?.user?.id === user?.id) return;

    selectedUser.value = user;
    selectedRoles.value = user.roles ? user.roles.map(role => role.id) : [];
    isModalOpen.value = true;
    isCreatingRole.value = false;
}

function closeModal() {
    isModalOpen.value = false;
    selectedUser.value = null;
    selectedRoles.value = [];
    hoveredRole.value = null;
    isCreatingRole.value = false;
    resetNewRole();
}

function toggleRole(roleId) {
    const index = selectedRoles.value.indexOf(roleId);
    if (index > -1) {
        selectedRoles.value.splice(index, 1);
    } else {
        selectedRoles.value.push(roleId);
    }
}

function canModifyRole(role) {
    const currentUser = props.auth?.user;

    if (currentUser?.is_owner) return true;

    if (currentUser?.is_admin) {
        return !['admin', 'owner'].includes(role.name);
    }

    return false;
}

function saveRoles() {
    if (!selectedUser.value) return;

    router.post(route('admin.users.update-roles'), {
        user_id: selectedUser.value.id,
        role_ids: selectedRoles.value
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            goToPage(currentPage.value);
        }
    });
}

function showCreateRole() {
    isCreatingRole.value = true;
}

function resetNewRole() {
    newRole.value = {
        name: '',
        display_name: '',
        description: '',
        permissions: {
            manage_admin: false,
            admin_access: false,
            manage_events: false,
            create_events: false,
            manage_members: false,
            manage_articles: false,
            create_articles: false
        }
    };
}

function createRole() {
    // Debug pour voir les données envoyées
    console.log('Données du nouveau rôle:', newRole.value);

    // Validation côté client
    if (!newRole.value.name.trim()) {
        alert('Le nom du rôle est requis');
        return;
    }

    if (!newRole.value.display_name.trim()) {
        alert('Le nom d\'affichage est requis');
        return;
    }

    router.post(route('admin.roles.create'), newRole.value, {
        preserveState: true,
        onSuccess: (page) => {
            console.log('Rôle créé avec succès');
            closeModal();
            // Actualiser la page pour voir le nouveau rôle
            window.location.reload();
        },
        onError: (errors) => {
            console.error('Erreurs de validation:', errors);
            alert('Erreur lors de la création du rôle. Vérifiez la console pour plus de détails.');
        },
        onFinish: () => {
            console.log('Requête terminée');
        }
    });
}

function getRoleDisplay(user) {
    if (user?.is_admin) return 'Admin';
    if (user?.is_animator) return 'Animateur';
    return 'Membre';
}

function canManageUserRoles(user) {
    const currentUser = props.auth?.user;

    if (currentUser?.id === user?.id) return false;
    if (currentUser?.is_owner) return true;
    if (currentUser?.is_admin && !user?.is_admin) return true;

    return false;
}

function getPermissionsList(role) {
    if (!role.permissions) return [];

    const permissions = [];
    const permissionLabels = {
        manage_admin: 'Gérer les administrateurs',
        admin_access: 'Accès administrateur',
        manage_events: 'Gérer les événements',
        create_events: 'Créer des événements',
        manage_members: 'Gérer les membres',
        manage_articles: 'Gérer les articles',
        create_articles: 'Créer des articles'
    };

    Object.keys(permissionLabels).forEach(key => {
        if (role.permissions[key]) {
            permissions.push(permissionLabels[key]);
        }
    });

    return permissions;
}

function getRoleBadgeColor(role) {
    switch (role.name) {
        case 'owner': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'admin': return 'bg-red-100 text-red-800 border-red-200';
        case 'animator': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'moderator': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}
</script>

<template>

    <Head title="Utilisateurs" />

    <AuthenticatedLayout :admin="true">
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

                <!-- Recherche + export -->
                <div class="flex justify-end w-full sm:w-auto flex-1">
                    <div class="flex items-center gap-12">
                        <button @click="exportCSV"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 h-10 flex items-center gap-2">
                            <DownloadFileIcon class="w-5 stroke-white" />
                            Exporter CSV
                        </button>

                        <input v-model="search" @input="fetchUsers" type="text"
                            placeholder="Rechercher par prénom ou nom..."
                            class="border rounded p-2 h-10 min-w-[400px]" />
                    </div>
                </div>
            </div>

            <!-- Informations de pagination en haut -->
            <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400">
                <div>
                    {{ total }} utilisateur(s) au total
                    <span v-if="search">(filtré par "{{ search }}")</span>
                </div>
                <div v-if="lastPage > 1">
                    Page {{ currentPage }} sur {{ lastPage }}
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="overflow-x-auto" v-if="users?.data && users.data.length > 0">
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
                            <th class="p-2 text-gray-900 dark:text-white">Permissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(user, index) in users.data" :key="user?.id || index"
                            class="text-center text-gray-900 dark:text-gray-300 dark:hover:text-white hover:dark:bg-gray-700 hover:bg-gray-100">
                            <td class="p-2">
                                <span :class="statusColor(user) + ' inline-block w-4 h-4 rounded-full'"></span>
                            </td>
                            <td class="p-2">{{ user?.id }}</td>
                            <td class="p-2">{{ user?.firstname }}</td>
                            <td class="p-2">{{ user?.lastname }}</td>
                            <td class="p-2">
                                {{ user?.birth_date ? useDateFormat(user.birth_date, 'DD/MM/YYYY') : '__/__/____' }}
                            </td>
                            <td class="p-2">{{ user?.postal_code }}</td>
                            <td class="p-2">{{ user?.phone }}</td>
                            <td class="p-2">{{ user?.email }}</td>
                            <td class="p-2">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Affichage du rôle actuel -->
                                    <span class="text-sm font-medium px-2 py-1 rounded" :class="{
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': user?.is_admin,
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': user?.is_animator && !user?.is_admin,
                                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': !user?.is_animator && !user?.is_admin
                                    }">
                                        {{ getRoleDisplay(user) }}
                                    </span>

                                    <!-- Bouton pour gérer les rôles -->
                                    <button v-if="canManageUserRoles(user)" @click="openRoleModal(user)"
                                        class="text-xs bg-purple-600 text-white px-2 py-1 rounded hover:bg-purple-700 transition-colors"
                                        title="Gérer les rôles">
                                        ⚙️
                                    </button>

                                    <!-- Indicateur pour l'utilisateur connecté -->
                                    <span v-if="auth?.user?.id === user?.id" class="text-xs text-gray-500">
                                        (Vous)
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Message si aucun utilisateur -->
            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                Aucun utilisateur trouvé.
            </div>

            <!-- Pagination simple -->
            <div v-if="lastPage > 1" class="flex justify-center items-center mt-6 gap-4">
                <button @click="previousPage" :disabled="currentPage === 1"
                    :class="currentPage === 1 ? 'bg-gray-300 cursor-not-allowed text-gray-500' : 'bg-blue-600 hover:bg-blue-700 text-white'"
                    class="px-3 py-2 rounded text-sm font-medium">
                    &lt;
                </button>

                <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded">
                    <span class="text-gray-700 dark:text-gray-300 font-medium">
                        {{ currentPage }} / {{ lastPage }}
                    </span>
                </div>

                <button @click="nextPage" :disabled="currentPage === lastPage"
                    :class="currentPage === lastPage ? 'bg-gray-300 cursor-not-allowed text-gray-500' : 'bg-blue-600 hover:bg-blue-700 text-white'"
                    class="px-3 py-2 rounded text-sm font-medium">
                    &gt;
                </button>
            </div>
        </div>

        <!-- Modal de gestion des rôles avec design amélioré -->
        <div v-if="isModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full max-h-[85vh] overflow-hidden">
                <!-- En-tête de la modal -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold">
                                {{ isCreatingRole ? 'Créer un nouveau rôle' : 'Gérer les rôles' }}
                            </h3>
                            <p v-if="!isCreatingRole" class="text-blue-100 mt-1">
                                {{ selectedUser?.firstname }} {{ selectedUser?.lastname }}
                            </p>
                        </div>
                        <button @click="closeModal" class="text-white hover:text-gray-200 text-2xl font-bold p-1">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Contenu de la modal -->
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <!-- Interface de création de rôle -->
                    <div v-if="isCreatingRole" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nom du rôle (technique)
                            </label>
                            <input v-model="newRole.name" type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="ex: moderator">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nom d'affichage
                            </label>
                            <input v-model="newRole.display_name" type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="ex: Modérateur">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description
                            </label>
                            <textarea v-model="newRole.description" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Description du rôle"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Permissions
                            </label>
                            <div class="space-y-2">
                                <label v-for="(label, key) in {
                                    manage_admin: 'Gérer les administrateurs',
                                    admin_access: 'Accès administrateur',
                                    manage_events: 'Gérer les événements',
                                    create_events: 'Créer des événements',
                                    manage_members: 'Gérer les membres',
                                    manage_articles: 'Gérer les articles',
                                    create_articles: 'Créer des articles'
                                }" :key="key" class="flex items-center">
                                    <input type="checkbox" v-model="newRole.permissions[key]"
                                        class="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Interface de sélection des rôles -->
                    <div v-else>
                        <!-- Bouton pour créer un nouveau rôle -->
                        <button @click="showCreateRole"
                            class="w-full mb-4 p-3 border-2 border-dashed border-blue-300 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors flex items-center justify-center gap-2">
                            <span class="text-xl">+</span>
                            <span class="font-medium">Créer un nouveau rôle</span>
                        </button>

                        <!-- Liste des rôles -->
                        <div class="space-y-3">
                            <div v-for="role in sortedUniqueRoles" :key="role.id" class="relative">
                                <label :class="{
                                    'opacity-50 cursor-not-allowed': !canModifyRole(role),
                                    'cursor-pointer': canModifyRole(role)
                                }" class="block">
                                    <div class="flex items-start p-4 border-2 rounded-xl transition-all duration-200 hover:shadow-md"
                                        :class="[
                                            selectedRoles.includes(role.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300',
                                            !canModifyRole(role) ? 'bg-gray-50' : ''
                                        ]">
                                        <input type="checkbox" :value="role.id"
                                            :checked="selectedRoles.includes(role.id)" :disabled="!canModifyRole(role)"
                                            @change="toggleRole(role.id)"
                                            class="mt-1 mr-3 h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded disabled:opacity-50" />
                                        <div class="flex-1 min-w-0" @mouseenter="hoveredRole = role"
                                            @mouseleave="hoveredRole = null">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-semibold text-gray-900 dark:text-white">
                                                    {{ role.display_name }}
                                                </span>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full border"
                                                    :class="getRoleBadgeColor(role)">
                                                    {{ role.name }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                                {{ role.description }}
                                            </p>
                                            <div v-if="getPermissionsList(role).length > 0" class="mt-2">
                                                <div class="flex flex-wrap gap-1">
                                                    <span v-for="permission in getPermissionsList(role)"
                                                        :key="permission"
                                                        class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-md">
                                                        {{ permission }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pied de modal -->
                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-end gap-3">
                    <button @click="closeModal"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 font-medium">
                        Annuler
                    </button>
                    <button v-if="isCreatingRole" @click="createRole"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors">
                        Créer le rôle
                    </button>
                    <button v-else @click="saveRoles"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>