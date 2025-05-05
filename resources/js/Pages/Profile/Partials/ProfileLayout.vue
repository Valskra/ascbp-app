<script setup>
import { ref, computed } from 'vue'
import NavLink from '@/Components/NavLink.vue'

const routeTitles = {
    'profile.profile': ['Profil', 'Profil'],
    'profile.edit': ['Édition du Profil', 'Édition'],
    'certificats': ['Gestion des Certificats', 'Certificats'],
}

const navItems = computed(() =>
    Object.entries(routeTitles).map(([name, [long, short]]) => ({
        name,
        long,
        short,
        isActive: route().current(name),
    }))
)

const dropdownItems = computed(() =>
    navItems.value.filter(item => !item.isActive)
)

const currentItem = computed(() =>
    navItems.value.find(item => item.isActive)
)
const titleLong = computed(() => currentItem.value?.long ?? 'Tableau de bord')
const titleShort = computed(() => currentItem.value?.short ?? 'Accueil')

const isOpen = ref(false)
function toggle() { isOpen.value = !isOpen.value }
function close() { isOpen.value = false }
</script>

<template>
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <!-- titre long -->
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                {{ titleLong }}
            </h2>

            <!-- MOBILE : bouton + dropdown -->
            <div class="sm:hidden relative">
                <button @click="toggle" class="inline-flex items-center justify-between w-40 px-3 py-2 border rounded-md
                bg-white text-gray-800 border-gray-300
                dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700
                hover:bg-gray-50 dark:hover:bg-gray-700
                focus:outline-none">
                    <span class="mr-1">{{ titleShort }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" :class="{ 'rotate-180': isOpen }" class="h-6" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <transition name="fade">
                    <ul v-if="isOpen" class="absolute left-0 mt-1  w-40 bg-white border rounded-md shadow-lg z-20
             border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <li v-for="item in dropdownItems" :key="item.name">
                            <NavLink :href="route(item.name)" class=" w-full flex items-center px-4 text-gray-800 dark:text-gray-200
                 hover:bg-gray-100 dark:hover:bg-gray-700" @click="close">
                                <p class="py-1">
                                    {{ item.short }}
                                </p>
                            </NavLink>
                        </li>
                    </ul>
                </transition>
            </div>

            <!-- DESKTOP : barre horizontale -->
            <div class="hidden sm:flex sm:space-x-8 sm:-my-px sm:ms-10">
                <NavLink v-for="item in navItems" :key="item.name" :href="route(item.name)" :active="item.isActive">
                    <!-- titre long -->
                    {{ item.short }}
                </NavLink>
            </div>
        </div>
    </div>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity .2s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
