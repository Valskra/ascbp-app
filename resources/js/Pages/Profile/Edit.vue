<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import ProfileUpdateForm from './Partials/ProfileUpdateForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import ProfileContact from './Partials/ProfileContact.vue';
import ProfileInformationDisplay from './Partials/ProfileInformationDisplay.vue';
import ProfileCard from './Partials/ProfileCard.vue';
import NavLink from '@/Components/NavLink.vue';
import UpdateEmailForm from './Partials/UpdateEmailForm.vue';
import UpdateAddressForm from './Partials/UpdateAddressForm.vue';
import UpdateBirthForm from './Partials/UpdateBirthForm.vue';
import UpdatePhoneForm from './Partials/UpdatePhoneForm.vue';

const user = usePage().props.auth.user;

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

    <Head title="Édition de Profil" />

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

        <div class="py-8 ">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 lg:grid-cols-2">
                <div class="space-y-6">
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <div class="space-y-6">
                            <ProfileCard :name="user.firstname + ' ' + user.lastname" status="Statut"
                                :birthday="String(user.birth_date).replaceAll('-', '/')"
                                :mobileNumbers="[String(user.phone).replaceAll(' ', '.'), String(user.phone_secondary).replaceAll(' ', '.')]" />
                        </div>
                        <div class="mt-8">
                            <UpdateEmailForm :user="user" :must-verify-email="mustVerifyEmail" :status="status"
                                labelWidth="25%" />
                            <hr class="my-6 mx-3 border-gray-300 dark:border-gray-600" />

                            <UpdateAddressForm :user="user" labelWidth="25%" />
                            <hr class="my-6 mx-3 border-gray-300 dark:border-gray-600" />

                            <UpdateBirthForm :user="user" labelWidth="32%" />
                            <hr class="my-6 mx-3 border-gray-300 dark:border-gray-600" />
                            <UpdatePhoneForm :user="user" labelWidth="32%" />
                        </div>
                    </div>
                </div>
                <!-- Partie gauche : Contenu existant -->

                <!-- Partie droite -->
                <div class="space-y-6">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow sm:p-8 dark:bg-gray-800">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Contacts
                            </h2>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Update your emergency contact information
                            </p>
                        </header>

                        <ProfileContact />
                        <ProfileContact />
                    </div>
                    <div class="mx-auto max-w-7xl">
                        <!-- Partie droite : Formulaires pour chaque catégorie -->

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
                    </div>

                </div>

            </div>
        </div>

    </AuthenticatedLayout>
</template>