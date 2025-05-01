<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdateContactsForm from './Partials/UpdateContactsForm.vue';
import UpdateProfileCard from './Partials/UpdateProfileCard.vue';
import UpdateEmailForm from './Partials/UpdateEmailForm.vue';
import UpdateAddressForm from './Partials/UpdateAddressForm.vue';
import UpdateBirthForm from './Partials/UpdateBirthForm.vue';
import ProfileLayout from './Partials/ProfileLayout.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    user: {
        type: Object,
    },
});

</script>

<template>

    <Head title="Édition de Profil" />

    <AuthenticatedLayout>
        <template #header>
            <ProfileLayout>
            </ProfileLayout>
        </template>

        <div class="py-8 ">

            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 lg:grid-cols-2">
                <div class="space-y-6">
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <div class="space-y-6">
                            <UpdateProfileCard :user="user" />

                        </div>
                        <div class="mt-8">
                            <UpdateEmailForm :user="user" :must-verify-email="mustVerifyEmail" :status="status"
                                labelWidth="25%" />
                            <hr class="my-6 mx-3 border-gray-300 dark:border-gray-600" />

                            <UpdateAddressForm :user="user" labelWidth="25%" />
                            <hr class="my-6 mx-3 border-gray-300 dark:border-gray-600" />
                            <UpdateBirthForm :user="user" labelWidth="32%" />
                        </div>
                    </div>
                </div>

                <!-- Partie gauche : Contenu existant -->

                <!-- Partie droite -->
                <div class="space-y-6">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow sm:p-8 dark:bg-gray-800">
                        <UpdateContactsForm :contacts="user.contacts" />
                    </div>
                    <div class="mx-auto max-w-7xl">
                        <!-- Partie droite : Formulaires pour chaque catégorie -->

                        <!-- Partie gauche : Formulaires de mise à jour -->
                        <div class="space-y-6">

                            <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                                <UpdatePasswordForm class="max-w-xl" />
                            </div>
                            <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                                <DeleteUserForm />
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </AuthenticatedLayout>
</template>