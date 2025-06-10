<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    articles: Object,
    filters: Object,
});

const searchForm = useForm({
    search: props.filters.search || '',
    sort: props.filters.sort || 'recent',
});

const handleSearch = () => {
    searchForm.get(route('articles.index'), {
        preserveState: true,
        replace: true,
    });
};

const handleSort = () => {
    searchForm.get(route('articles.index'), {
        preserveState: true,
        replace: true,
    });
};

const toggleLike = async (article) => {
    try {
        const response = await axios.post(route('articles.like', article.id));
        article.is_liked = response.data.liked;
        article.likes_count = response.data.likes_count;
    } catch (error) {
        console.error('Erreur lors du like:', error);
    }
};

const deleteArticle = (article) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        router.delete(route('articles.destroy', article.id), {
            onSuccess: () => {
                // L'article sera retiré automatiquement de la liste
            }
        });
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Articles de la communauté
                </h2>
                <Link :href="route('articles.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Nouvel article
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtres et recherche -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row gap-4">
                            <!-- Recherche -->
                            <div class="flex-1">
                                <TextInput v-model="searchForm.search" placeholder="Rechercher des articles..."
                                    @input="handleSearch" class="w-full" />
                            </div>

                            <!-- Tri -->
                            <div class="md:w-48">
                                <select v-model="searchForm.sort" @change="handleSort"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="recent">Plus récents</option>
                                    <option value="popular">Plus populaires</option>
                                    <option value="commented">Plus commentés</option>
                                    <option value="views">Plus vus</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des articles -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <article v-for="article in articles.data" :key="article.id"
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
                        <!-- Image mise en avant -->
                        <div v-if="article.featured_image" class="aspect-video overflow-hidden">
                            <img :src="article.featured_image.url" :alt="article.title"
                                class="w-full h-full object-cover hover:scale-105 transition-transform cursor-pointer"
                                @click="$inertia.visit(route('articles.show', article.id))" />
                        </div>

                        <!-- Contenu -->
                        <div class="p-6">
                            <!-- Titre -->
                            <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200 mb-2">
                                <Link :href="route('articles.show', article.id)"
                                    class="hover:text-blue-600 dark:hover:text-blue-400">
                                {{ article.title }}
                                </Link>
                            </h3>

                            <!-- Extrait -->
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                                {{ article.excerpt }}
                            </p>

                            <!-- Métadonnées -->
                            <div
                                class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
                                <div class="flex items-center gap-2">
                                    <span>Par {{ article.author.firstname }} {{ article.author.lastname }}</span>
                                    <span>•</span>
                                    <span>{{ formatDate(article.publish_date) }}</span>
                                </div>
                                <span>{{ article.views_count }} vues</span>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                    <!-- Likes -->
                                    <button @click="toggleLike(article)"
                                        class="flex items-center gap-1 hover:text-red-500 transition-colors"
                                        :class="{ 'text-red-500': article.is_liked }">
                                        <!-- Icône cœur -->
                                        <svg class="w-4 h-4" :class="{ 'fill-current': article.is_liked }" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        {{ article.likes_count }}
                                    </button>

                                    <!-- Commentaires -->
                                    <div class="flex items-center gap-1">
                                        <!-- Icône commentaire -->
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        {{ article.comments_count }}
                                    </div>
                                </div>

                                <!-- Actions d'édition -->
                                <div v-if="article.can_edit" class="flex items-center gap-2">
                                    <Link :href="route('articles.edit', article.id)"
                                        class="text-blue-600 hover:text-blue-800 text-xs">
                                    Modifier
                                    </Link>
                                    <button @click="deleteArticle(article)"
                                        class="text-red-600 hover:text-red-800 text-xs">
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Message si aucun article -->
                <div v-if="articles.data.length === 0" class="text-center py-12">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8">
                        <!-- Icône document -->
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>

                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            Aucun article trouvé
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            {{ filters.search ? 'Aucun article ne correspond à votre recherche.' :
                                'Soyez le premier à publier un article !' }}
                        </p>
                        <Link v-if="!filters.search" :href="route('articles.create')"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                        Créer le premier article
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="articles.data.length > 0" class="mt-8">
                    <Pagination :links="articles.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>