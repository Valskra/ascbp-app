<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Users from '@/Components/svg/usersIcon.vue';
import Calendar from '@/Components/svg/calendarIcon.vue';

</script>

<template>

    <Head title="Administration" />

    <AuthenticatedLayout admin="true">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Tableau de bord administrateur
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                            <!-- Gestion des utilisateurs -->
                            <Link :href="route('admin.users')"
                                class="block p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <Users class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gestion des
                                        utilisateurs</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Consulter et gérer les membres de l'association
                                    </p>
                                </div>
                            </div>
                            </Link>

                            <!-- Gestion des événements -->
                            <Link :href="route('admin.events')"
                                class="block p-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">

                                    <Calendar class="w-8 h-8 text-green-600 dark:text-green-400" />

                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gestion des événements
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Consulter tous les événements et gérer les participants
                                    </p>
                                </div>
                            </div>
                            </Link>

                            <!-- Créer un événement -->
                            <Link :href="route('events.create')"
                                class="block p-6 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Créer un événement
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Créer un nouvel événement pour l'association
                                    </p>
                                </div>
                            </div>
                            </Link>

                            <!-- Mes événements -->
                            <Link :href="route('events.manage')"
                                class="block p-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M8 5.00005C7.01165 5.00082 6.49359 5.01338 6.09202 5.21799C5.71569 5.40973 5.40973 5.71569 5.21799 6.09202C5 6.51984 5 7.07989 5 8.2V17.8C5 18.9201 5 19.4802 5.21799 19.908C5.40973 20.2843 5.71569 20.5903 6.09202 20.782C6.51984 21 7.07989 21 8.2 21H15.8C16.9201 21 17.4802 21 17.908 20.782C18.2843 20.5903 18.5903 20.2843 18.782 19.908C19 19.4802 19 18.9201 19 17.8V8.2C19 7.07989 19 6.51984 18.782 6.09202C18.5903 5.71569 18.2843 5.40973 17.908 5.21799C17.5064 5.01338 16.9884 5.00082 16 5.00005M8 5.00005V7H16V5.00005M8 5.00005V4.70711C8 4.25435 8.17986 3.82014 8.5 3.5C8.82014 3.17986 9.25435 3 9.70711 3H14.2929C14.7456 3 15.1799 3.17986 15.5 3.5C15.8201 3.82014 16 4.25435 16 4.70711V5.00005M15 12H12M15 16H12M9 12H9.01M9 16H9.01"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Mes événements</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Gérer les événements que j'organise
                                    </p>
                                </div>
                            </div>
                            </Link>

                            <!-- Voir tous les événements -->
                            <Link :href="route('events.index')"
                                class="block p-6 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Voir les événements
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Consulter la liste publique des événements
                                    </p>
                                </div>
                            </div>
                            </Link>

                            <!-- Statistiques -->
                            <div
                                class="block p-6 bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-800 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-gray-600 dark:text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Statistiques</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Bientôt disponible - Tableau de bord analytique
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section des actions rapides -->
                        <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-8">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Actions rapides</h3>
                            <div class="flex flex-wrap gap-4">
                                <Link :href="route('admin.users')"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <Users class="w-4 h-4 mr-2" fill="none" stroke="currentColor" />
                                Exporter les utilisateurs
                                </Link>

                                <Link :href="route('admin.events')"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <Calendar class="w-4 h-4 mr-2" fill="none" stroke="currentColor" />
                                Exporter les événements
                                </Link>

                                <Link :href="route('events.create')"
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Nouvel événement
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>