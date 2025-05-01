<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import ProfileContact from './Partials/ProfileContact.vue';
import ProfileInformationDisplay from './Partials/ProfileInformationDisplay.vue';
import ProfileCard from './Partials/ProfileCard.vue';
import ProfileLayout from './Partials/ProfileLayout.vue';
import { useDateFormat } from '@vueuse/core';
import { computed } from 'vue';

const props = defineProps({
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

const birthDateFormatted = computed(() => {
    return props.user.birth_date
        ? useDateFormat(props.user.birth_date, 'DD/MM/YYYY').value
        : "__/__/____";
});
</script>

<template>

    <Head title="Profil" />

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
                            <ProfileCard :user="user" />
                        </div>
                        <div class="mt-8">

                            <ProfileInformationDisplay title="Email" :labels="['Email Perso.', 'Email Pro.']"
                                labelWidth="25%" :data="[user.email, user.email_pro]" />
                            <hr class="my-6 mx-3 border-gray-300 dark:border-gray-600" />
                            <ProfileInformationDisplay title="Domicile" :labels="['Adresse', 'Ville', 'Pays']"
                                labelWidth="25%" :data="[
                                    `${user.home_address?.house_number ?? ''} ${user.home_address?.street_name ?? '-'}`,
                                    `${user.home_address?.city ?? '-'}${user.home_address?.postal_code ? ',' : ''} ${user.home_address?.postal_code ?? ''}`,
                                    `${user.home_address?.country ?? ''}`
                                ]" />
                            <hr class="my-6 mx-3 border-gray-300 dark:border-gray-600" />
                            <ProfileInformationDisplay title="Naissance" :labels="['Date', 'Ville', 'Pays']"
                                labelWidth="25%" :data="[birthDateFormatted,
                                    `${user.birth_address?.city ?? '-'}${user.birth_address?.postal_code ? ',' : ''} ${user.birth_address?.postal_code ?? ''}`,
                                    user.birth_address?.country ?? '']" />

                        </div>

                    </div>
                </div>
                <!-- Partie gauche : Contenu existant -->

                <!-- Partie droite -->
                <div class="space-y-6">
                    <div class="space-y-4 rounded-lg bg-white p-4 shadow sm:p-8 dark:bg-gray-800">
                        <header>
                            <h1 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                Contacts
                            </h1>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Contacts à contacter en cas de besoin
                            </p>
                        </header>
                        <ProfileContact :contacts="user.contacts" />

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
