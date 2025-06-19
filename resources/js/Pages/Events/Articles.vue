<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Articles - {{ event.title }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ formatDate(event.start_date) }}
                        <span v-if="event.end_date"> - {{ formatDate(event.end_date) }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="route('events.show', event.id)" class="text-blue-600 hover:text-blue-800 text-sm">
                    ← Retour à l'événement
                    </Link>

                    <Link v-if="canCreateArticle" :href="route('articles.create', { event_id: event.id })"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                    Nouvel article
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Articles épinglés -->
                <div v-if="pinnedArticles.length > 0">
                    <div class="flex items-center gap-2 mb-4">
                        <PinIcon class="w-5 h-5 text-yellow-600" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Articles épinglés
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <article v-for="article in pinnedArticles" :key="`pinned-${article.id}`"
                            class="bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 border border-yellow-200 dark:border-yellow-700 overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow">
                            <!-- Image mise en avant -->
                            <div v-if="article.featured_image" class="aspect-video overflow-hidden">
                                <img :src="article.featured_image.url" :alt="article.title"
                                    class="w-full h-full object-cover hover:scale-105 transition-transform cursor-pointer"
                                    @click="$inertia.visit(route('articles.show', article.id))" />
                            </div>

                            <!-- Contenu -->
                            <div class="p-6">
                                <!-- Badge épinglé -->
                                <div
                                    class="flex items-center gap-1 text-yellow-700 dark:text-yellow-300 text-xs font-medium mb-2">
                                    <PinIcon class="w-3 h-3" />
                                    Épinglé
                                </div>

                                <!-- Titre -->
                                <h4 class="font-semibold text-lg text-gray-800 dark:text-gray-200 mb-2">
                                    <Link :href="route('articles.show', article.id)"
                                        class="hover:text-blue-600 dark:hover:text-blue-400">
                                    {{ article.title }}
                                    </Link>
                                </h4>

                                <!-- Extrait -->
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                                    {{ article.excerpt }}
                                </p>

                                <!-- Métadonnées et actions -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ article.author.firstname }} {{ article.author.lastname }}</span>
                                        <span>{{ formatDate(article.publish_date) }}</span>
                                        <span>{{ article.views_count }} vues</span>
                                    </div>

                                    <ArticleActions :article="article" />
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <!-- Articles normaux -->
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ pinnedArticles.length > 0 ? 'Autres articles' : 'Articles' }}
                        </h3>

                        <!-- Filtres futurs -->
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ articles.total }} article{{ articles.total > 1 ? 's' : '' }}
                        </div>
                    </div>

                    <!-- Liste des articles -->
                    <div v-if="articles.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                                <h4 class="font-semibold text-lg text-gray-800 dark:text-gray-200 mb-2">
                                    <Link :href="route('articles.show', article.id)"
                                        class="hover:text-blue-600 dark:hover:text-blue-400">
                                    {{ article.title }}
                                    </Link>
                                </h4>

                                <!-- Extrait -->
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                                    {{ article.excerpt }}
                                </p>

                                <!-- Métadonnées -->
                                <div
                                    class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
                                    <div class="flex items-center gap-2">
                                        <span>{{ article.author.firstname }} {{ article.author.lastname }}</span>
                                        <span>•</span>
                                        <span>{{ formatDate(article.publish_date) }}</span>
                                    </div>
                                    <span>{{ article.views_count }} vues</span>
                                </div>

                                <!-- Actions -->
                                <ArticleActions :article="article" />
                            </div>
                        </article>
                    </div>

                    <!-- Message si aucun article normal -->
                    <div v-else-if="pinnedArticles.length === 0" class="text-center py-12">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8">
                            <DocumentTextIcon class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Aucun article pour cet événement
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Soyez le premier à partager votre expérience !
                            </p>
                            <Link v-if="canCreateArticle" :href="route('articles.create', { event_id: event.id })"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Créer le premier article
                            </Link>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>Aucun autre article pour le moment.</p>
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

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ArticleActions from '@/Components/ArticleActions.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    event: Object,
    pinnedArticles: Array,
    articles: Object,
    canCreateArticle: Boolean,
    canManageArticles: Boolean,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>