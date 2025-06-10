<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Test Articles
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Formulaire simple pour créer un article -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Créer un article de test
                        </h3>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Titre
                                </label>
                                <input id="title" v-model="form.title" type="text" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                    placeholder="Titre de votre article...">
                                <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Contenu
                                </label>
                                <textarea id="content" v-model="form.content" rows="5" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                    placeholder="Contenu de votre article..."></textarea>
                                <div v-if="form.errors.content" class="text-red-600 text-sm mt-1">
                                    {{ form.errors.content }}
                                </div>
                            </div>

                            <button type="submit" :disabled="form.processing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                                {{ form.processing ? 'Création...' : 'Créer l\'article' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Liste des articles -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Articles existants
                        </h3>

                        <div v-if="articles && articles.data && articles.data.length > 0" class="space-y-4">
                            <div v-for="article in articles.data" :key="article.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2">
                                    {{ article.title }}
                                </h4>
                                <p class="text-gray-600 dark:text-gray-400 mb-3">
                                    {{ article.content }}
                                </p>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Par {{ article.author?.firstname }} {{ article.author?.lastname }}
                                    le {{ formatDate(article.publish_date) }}
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p>Aucun article pour le moment</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3';

defineProps({
    articles: {
        type: Object,
        default: () => ({ data: [] })
    },
});

const form = useForm({
    title: '',
    content: '',
});

const submit = () => {
    form.post(route('test.articles.store'), {
        onSuccess: () => form.reset(),
    });
};

const formatDate = (dateString) => {
    if (!dateString) return 'Date inconnue';

    try {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return 'Date invalide';
    }
};
</script>