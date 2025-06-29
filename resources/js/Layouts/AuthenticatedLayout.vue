<script setup>
import ApplicationLogoLong from '@/Components/ApplicationLogoLong.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import KeyIcon from '@/Components/svg/keyIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    admin: {
        type: Boolean,
        default: false,
    },
});

const showingNavigationDropdown = ref(false);
const user = usePage().props.auth.user;

const managingLayout = ref(props.admin)

function switchLayout() {
    managingLayout.value = !managingLayout.value
}
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link href="/">
                                <ApplicationLogoLong
                                    class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div v-if="!managingLayout" class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Accueil
                                </NavLink>
                                <NavLink :href="route('events.index')" :active="route().current('events.index')">
                                    Événements
                                </NavLink>
                                <NavLink :href="route('articles.index')" :active="route().current('articles.index')">
                                    Articles
                                </NavLink>
                            </div>

                            <!-- Managing Navigation Links -->
                            <div v-if="managingLayout" class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <!--NavLink v-if="user.is_admin" :href="route('admin.dashboard')"
                                    :active="route().current('admin.dashboard')">
                                    Admin
                                </!--NavLink-->
                                <NavLink v-if="user.is_admin" :href="route('admin.users')"
                                    :active="route().current('admin.users')">
                                    Utilisateurs
                                </NavLink>
                                <NavLink v-if="user.is_animator" :href="route('events.create')"
                                    :active="route().current('events.create')">
                                    Création d'événement
                                </NavLink>
                                <NavLink :href="route('articles.create')" :active="route().current('articles.create')">
                                    Nouvel article
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <button v-if="user.is_admin && managingLayout"
                                class="inline-flex items-center rounded-md border bg-blue-500 px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                @click="switchLayout">
                                <KeyIcon class="w-5 h-5 text-cyan-50 dark:text-gray-300"></KeyIcon>
                            </button>
                            <button v-if="user.is_admin && !managingLayout"
                                class="inline-flex items-center rounded-md border bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                @click="switchLayout">

                                <KeyIcon class="w-5 h-5 text-black dark:text-gray-300"></KeyIcon>
                            </button>
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300">
                                                {{
                                                    $page.props.auth.user
                                                        .firstname
                                                }}
                                                {{
                                                    $page.props.auth.user
                                                        .lastname
                                                }}

                                                <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.profile')">
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="
                                showingNavigationDropdown =
                                !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex':
                                            !showingNavigationDropdown,
                                    }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex':
                                            showingNavigationDropdown,
                                    }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }" class="sm:hidden">
                    <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-600">
                        <button v-if="user.is_admin" @click="switchLayout"
                            class="w-full flex items-center justify-center px-3 py-2 rounded-md border text-sm font-medium leading-4 transition duration-150 ease-in-out"
                            :class="managingLayout ? 'bg-blue-500 text-white' : 'bg-white text-gray-600 dark:bg-gray-700 dark:text-gray-300'">
                            <KeyIcon class="w-5 h-5 mr-2" />
                            {{ managingLayout ? 'Gestion Admin activée' : 'Activer Gestion Admin' }}
                        </button>
                    </div>
                    <div v-if="!managingLayout" class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Accueil
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('events.index')" :active="route().current('events.index')">
                            Événements
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('articles.index')" :active="route().current('articles.index')">
                            Articles
                        </ResponsiveNavLink>
                    </div>

                    <div v-if="managingLayout" class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink v-if="user.is_admin" :href="route('admin.dashboard')"
                            :active="route().current('admin.dashboard')">
                            Admin
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="user.is_admin" :href="route('admin.users')"
                            :active="route().current('admin.users')">
                            Utilisateurs
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="user.is_animator" :href="route('events.create')"
                            :active="route().current('events.create')">
                            Création d'événement
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('articles.create')"
                            :active="route().current('articles.create')">
                            Nouvel article
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800 dark:text-gray-200">
                                {{ $page.props.auth.user.firstname }}
                                {{ $page.props.auth.user.lastname }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.profile')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>

            </nav>

            <!-- Page Heading -->
            <header class="bg-white shadow dark:bg-gray-800 border-b-2 border-[#00adef] dark:border-[#027daf]"
                v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>

</template>
