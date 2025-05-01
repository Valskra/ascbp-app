<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;
</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        Bienvenue, {{ user.firstname }} ! <br>
                        Vous êtes admin ? {{ user.is_admin }} !
                    </div>
                    <div class="p-6">
                        <h2 class="text-xl font-bold mb-4">Rôles</h2>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="role in user.roles" :key="role"
                                class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-sm">
                                {{ role.name }}
                            </span>
                        </div>

                        <h2 class="text-xl font-bold my-6">Permissions</h2>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="permission in user.permissions" :key="permission"
                                class="bg-green-200 text-green-800 px-3 py-1 rounded-full text-sm capitalize">
                                {{ permission.replace('_', ' ') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
