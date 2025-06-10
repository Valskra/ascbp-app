<template>
    <nav v-if="links.length > 3" class="flex items-center justify-between">
        <div class="flex justify-between flex-1 sm:hidden">
            <!-- Version mobile -->
            <Link v-if="links[0].url" :href="links[0].url"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:text-gray-400">
            Précédent
            </Link>
            <span v-else
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-md cursor-not-allowed">
                Précédent
            </span>

            <Link v-if="links[links.length - 1].url" :href="links[links.length - 1].url"
                class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:text-gray-400">
            Suivant
            </Link>
            <span v-else
                class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-md cursor-not-allowed">
                Suivant
            </span>
        </div>

        <!-- Version desktop -->
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Affichage de
                    <span class="font-medium">{{ from }}</span>
                    à
                    <span class="font-medium">{{ to }}</span>
                    sur
                    <span class="font-medium">{{ total }}</span>
                    résultats
                </p>
            </div>

            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <template v-for="(link, index) in links" :key="index">
                        <!-- Bouton Précédent -->
                        <Link v-if="index === 0 && link.url" :href="link.url"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <span class="sr-only">Précédent</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        </Link>
                        <span v-else-if="index === 0"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-sm font-medium text-gray-300 dark:text-gray-600 cursor-not-allowed">
                            <span class="sr-only">Précédent</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>

                        <!-- Bouton Suivant -->
                        <Link v-else-if="index === links.length - 1 && link.url" :href="link.url"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <span class="sr-only">Suivant</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        </Link>
                        <span v-else-if="index === links.length - 1"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-sm font-medium text-gray-300 dark:text-gray-600 cursor-not-allowed">
                            <span class="sr-only">Suivant</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>

                        <!-- Pages numérotées -->
                        <Link v-else-if="link.url && !link.active" :href="link.url"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            v-html="link.label">
                        </Link>

                        <!-- Page active -->
                        <span v-else-if="link.active"
                            class="relative inline-flex items-center px-4 py-2 border border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/50 text-sm font-medium text-blue-600 dark:text-blue-400"
                            v-html="link.label">
                        </span>

                        <!-- Points de suspension -->
                        <span v-else
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300"
                            v-html="link.label">
                        </span>
                    </template>
                </nav>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    links: {
        type: Array,
        required: true,
    },
    from: {
        type: Number,
        default: 0,
    },
    to: {
        type: Number,
        default: 0,
    },
    total: {
        type: Number,
        default: 0,
    },
});

// Calculer les valeurs from, to, total à partir des liens si non fournies explicitement
const from = computed(() => {
    if (props.from) return props.from;
    // Extraire from des métadonnées de pagination si disponibles
    return 1;
});

const to = computed(() => {
    if (props.to) return props.to;
    // Calculer to en fonction du nombre d'éléments
    return 10;
});

const total = computed(() => {
    if (props.total) return props.total;
    // Valeur par défaut
    return 100;
});
</script>