<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
            <!-- Likes -->
            <button @click="toggleLike" class="flex items-center gap-1 hover:text-red-500 transition-colors"
                :class="{ 'text-red-500': article.is_liked }" :disabled="!$page.props.auth.user">
                <!-- <HeartIcon class="w-4 h-4" :class="{ 'fill-current': article.is_liked }" /> -->
                {{ article.likes_count }}
            </button>

            <!-- Commentaires -->
            <Link :href="route('articles.show', article.id) + '#comments'"
                class="flex items-center gap-1 hover:text-blue-500 transition-colors">
            <!-- <ChatBubbleLeftIcon class="w-4 h-4" /> -->
            {{ article.comments_count }}
            </Link>
        </div>

        <!-- Actions d'édition -->
        <div v-if="article.can_edit" class="flex items-center gap-2">
            <Link :href="route('articles.edit', article.id)" class="text-blue-600 hover:text-blue-800 text-xs">
            Modifier
            </Link>
            <button @click="deleteArticle" class="text-red-600 hover:text-red-800 text-xs">
                Supprimer
            </button>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    article: {
        type: Object,
        required: true,
    },
});

const toggleLike = async () => {
    if (!props.article.is_liked === undefined) return;

    try {
        const response = await axios.post(route('articles.like', props.article.id));
        props.article.is_liked = response.data.liked;
        props.article.likes_count = response.data.likes_count;
    } catch (error) {
        console.error('Erreur lors du like:', error);
    }
};

const deleteArticle = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        router.delete(route('articles.destroy', props.article.id), {
            onSuccess: () => {
                // L'article sera retiré automatiquement de la liste
            }
        });
    }
};
</script>