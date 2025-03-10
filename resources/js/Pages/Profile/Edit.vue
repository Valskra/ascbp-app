<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import ProfileUpdateForm from './Partials/ProfileUpdateForm.vue';

const user = usePage().props.auth.user;
</script>

<template>

    <Head title="Modifier le Profil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    Édition du profil
                </h2>
                <a href="/profile"
                    class="px-4 py-2 bg-gray-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-gray-700 transition">
                    Retour
                </a>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Partie gauche : Formulaires de mise à jour -->
                <div class="space-y-6">
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status"
                            class="max-w-xl" />
                    </div>
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>
                </div>

                <!-- Partie droite : Formulaires pour chaque catégorie -->
                <div class="space-y-6">
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <ProfileUpdateForm title="Email" :fields="['email', 'email_pro']"
                            :labels="['Email Perso', 'Email Pro']" :values="[user.email, user.email_pro]" />
                    </div>
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <ProfileUpdateForm title="Téléphone" :fields="['phone', 'phone_secondary']"
                            :labels="['Tél Perso', 'Tél Pro']" :values="[user.phone, user.phone_secondary]" />
                    </div>
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <ProfileUpdateForm title="Naissance" :fields="['birth_date']" :labels="['Date de Naissance']"
                            :values="[user.birth_date]" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
