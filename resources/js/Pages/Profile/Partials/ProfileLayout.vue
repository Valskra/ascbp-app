<script setup>
import NavLink from '@/Components/NavLink.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const routeTitles = {
    'profile.profile': 'Profil',
    'profile.edit': 'Édition',
    'documents': 'Documents',
};

const title = computed(() => {
    return Object.entries(routeTitles).find(([name]) => route().current(name))?.[1] || 'Tableau de bord';
});
</script>

<template>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ title }}
        </h2>

        <div class="space-x-8 sm:-my-px sm:ms-10 sm:flex">
            <NavLink :href="route('profile.profile')" :active="route().current('profile.profile')">
                Profil
            </NavLink>
            <NavLink :href="route('profile.edit')" :active="route().current('profile.edit')">
                Édition
            </NavLink>
            <NavLink>
                Documents
            </NavLink>
            <!--
                <NavLink :href=" route('documents')" :active="route().current('documents')">
                Documents
            </NavLink>
            -->
        </div>
    </div>
</template>
