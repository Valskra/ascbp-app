<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import ProfileContact from './Partials/ProfileContact.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import ProfileInformationDisplay from './Partials/ProfileInformationDisplay.vue';
import ProfileCard from './Partials/ProfileCard.vue';
import NavLink from '@/Components/NavLink.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;


</script>

<template>

    <Head title="Profil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Profil
                </h2>
                <div class="space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <NavLink :href="route('profile.edit')" :active="route().current('profile.edit')">
                        Modifier
                    </NavLink>
                </div>

            </div>
        </template>

        <div class="py-8 ">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 lg:grid-cols-2">
                <div class="space-y-6">
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <div class="space-y-6">
                            <ProfileCard :name="user.firstname + ' ' + user.lastname" status="Statut"
                                birthday="12/05/1985" :mobileNumbers="[user.phone, user.phone_secondary]" />
                        </div>
                        <div class="mt-8">
                            <ProfileInformationDisplay title="Email" :labels="['Email Perso.', 'Email Pro.']"
                                labelWidth="25%" :data="[user.email, user.email_pro]" />
                            <hr class="my-6 mx-3 border-gray-300 dark:border-gray-600" />
                            <ProfileInformationDisplay title="Domicile" :labels="['Adresse', 'Ville', 'Pays']"
                                labelWidth="25%" :data="[
                                    `${user.home_address?.house_number ?? ''} ${user.home_address?.street_name ?? '-'}`,
                                    `${user.home_address?.city ?? '-'} ${user.home_address?.postal_code ? ',' : ''} ${user.home_address?.postal_code ?? ''}`,
                                    `${user.home_address?.country ?? ''}`
                                ]" />
                            <hr class="my-6 mx-3 border-gray-300 dark:border-gray-600" />

                            <ProfileInformationDisplay title="Naissance" :labels="['Date', 'Ville', 'Pays']"
                                labelWidth="25%" :data="[]" />

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
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
