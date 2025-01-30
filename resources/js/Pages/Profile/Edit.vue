<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import ProfileContact from './Partials/ProfileContact.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profil" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Profil
            </h2>
        </template>

        <div class="py-8">
            <div
                class="mx-auto grid max-w-7xl grid-cols-1 gap-8 lg:grid-cols-2"
            >
                <!-- Partie gauche : Contenu existant -->
                <div class="space-y-6">
                    <div
                        class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                    >
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="max-w-xl"
                        />
                    </div>

                    <div
                        class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                    >
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>
                </div>

                <!-- Partie droite : Carré rouge -->
                <div class="space-y-6">
                    <div
                        class="space-y-4 rounded-lg bg-white p-4 shadow sm:p-8 dark:bg-gray-800"
                    >
                        <header>
                            <h2
                                class="text-lg font-medium capitalize text-gray-900 dark:text-gray-100"
                            >
                                informations complémentaires
                            </h2>
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                                class="max-w-xl"
                            />
                        </header>
                    </div>

                    <div
                        class="space-y-4 rounded-lg bg-white p-4 shadow sm:p-8 dark:bg-gray-800"
                    >
                        <header>
                            <h2
                                class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            >
                                Contacts
                            </h2>

                            <p
                                class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                            >
                                Update your emergency contact information
                            </p>
                        </header>

                        <ProfileContact />
                        <ProfileContact />
                    </div>

                    <div
                        class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                    >
                        <DeleteUserForm class="max-w-xl" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
