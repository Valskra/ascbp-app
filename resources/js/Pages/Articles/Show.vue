<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ article.title }}
                    </h2>
                    <div v-if="article.event" class="text-sm text-blue-600 mt-1">
                        Article lié à l'événement :
                        <Link :href="route('events.show', article.event.id)" class="hover:underline">
                        {{ article.event.title }}
                        </Link>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Épingler (admin seulement) -->
                    <button v-if="article.can_pin" @click="togglePin"
                        class="flex items-center gap-1 px-3 py-1 text-sm rounded"
                        :class="article.is_pinned ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        <PinIcon class="w-4 h-4" />
                        {{ article.is_pinned ? 'Épinglé' : 'Épingler' }}
                    </button>

                    <!-- Actions d'édition -->
                    <div v-if="article.can_edit" class="flex items-center gap-2">
                        <Link :href="route('articles.edit', article.id)"
                            class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded">
                        Modifier
                        </Link>
                        <button @click="deleteArticle"
                            class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded">
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Article principal -->
                <article class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <!-- Image mise en avant -->
                    <div v-if="article.featured_image" class="aspect-video overflow-hidden">
                        <img :src="article.featured_image.url" :alt="article.title"
                            class="w-full h-full object-cover" />
                    </div>

                    <div class="p-6">
                        <!-- Métadonnées -->
                        <div
                            class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-6 pb-4 border-b">
                            <div class="flex items-center gap-4">
                                <span>Par {{ article.author.firstname }} {{ article.author.lastname }}</span>
                                <span>•</span>
                                <span>{{ formatDate(article.publish_date) }}</span>
                                <span>•</span>
                                <span>{{ article.views_count }} vues</span>
                                <span v-if="article.is_pinned"
                                    class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">
                                    <PinIcon class="w-3 h-3" />
                                    Épinglé
                                </span>
                            </div>

                            <!-- Actions sociales -->
                            <div class="flex items-center gap-4">
                                <button @click="toggleLike"
                                    class="flex items-center gap-1 hover:text-red-500 transition-colors"
                                    :class="{ 'text-red-500': article.is_liked }" :disabled="likingArticle">
                                    <HeartIcon class="w-5 h-5" :class="{ 'fill-current': article.is_liked }" />
                                    {{ article.likes_count }}
                                </button>

                                <div class="flex items-center gap-1 text-gray-500">
                                    <ChatBubbleLeftIcon class="w-5 h-5" />
                                    {{ comments.length }}
                                </div>
                            </div>
                        </div>

                        <!-- Contenu de l'article -->
                        <div class="prose prose-lg max-w-none dark:prose-invert"
                            v-html="formatContent(article.content)"></div>
                    </div>
                </article>

                <!-- Section des commentaires -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                            Commentaires ({{ comments.length }})
                        </h3>

                        <!-- Formulaire de nouveau commentaire -->
                        <div class="mb-8">
                            <form @submit.prevent="submitComment" class="space-y-4">
                                <div>
                                    <textarea v-model="commentForm.content" rows="4"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                        placeholder="Ajouter un commentaire..." required
                                        :class="{ 'border-red-500': commentForm.errors.content }"></textarea>
                                    <InputError :message="commentForm.errors.content" class="mt-2" />
                                </div>

                                <div class="flex justify-end">
                                    <PrimaryButton :disabled="commentForm.processing || !commentForm.content.trim()"
                                        :class="{ 'opacity-25': commentForm.processing }">
                                        {{ commentForm.processing ? 'Envoi...' : 'Commenter' }}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>

                        <!-- Liste des commentaires -->
                        <div class="space-y-6">
                            <div v-for="comment in comments" :key="comment.id"
                                class="border-l-4 border-gray-200 dark:border-gray-600 pl-4">
                                <!-- Commentaire principal -->
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">{{ comment.author.firstname }} {{
                                                comment.author.lastname
                                            }}</span>
                                            <span>•</span>
                                            <span>{{ formatDate(comment.created_at) }}</span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <!-- Like du commentaire -->
                                            <button @click="toggleCommentLike(comment)"
                                                class="flex items-center gap-1 text-xs hover:text-red-500 transition-colors"
                                                :class="{ 'text-red-500': comment.is_liked }"
                                                :disabled="likingComments.includes(comment.id)">
                                                <HeartIcon class="w-3 h-3"
                                                    :class="{ 'fill-current': comment.is_liked }" />
                                                {{ comment.likes_count }}
                                            </button>

                                            <!-- Actions d'édition -->
                                            <div v-if="comment.can_edit" class="flex items-center gap-1">
                                                <button @click="startEditComment(comment)"
                                                    class="text-xs text-blue-600 hover:text-blue-800">
                                                    Modifier
                                                </button>
                                                <button @click="deleteComment(comment)"
                                                    class="text-xs text-red-600 hover:text-red-800">
                                                    Supprimer
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contenu du commentaire -->
                                    <div v-if="editingComment?.id !== comment.id">
                                        <p class="text-gray-800 dark:text-gray-200">{{ comment.content }}</p>

                                        <!-- Bouton de réponse -->
                                        <button @click="toggleReplyForm(comment.id)"
                                            class="mt-2 text-xs text-blue-600 hover:text-blue-800">
                                            Répondre
                                        </button>
                                    </div>

                                    <!-- Formulaire d'édition -->
                                    <div v-else class="space-y-2">
                                        <textarea v-model="editCommentForm.content" rows="3"
                                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                                        <div class="flex justify-end gap-2">
                                            <button @click="cancelEditComment"
                                                class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                                Annuler
                                            </button>
                                            <button @click="updateComment(comment)"
                                                class="px-3 py-1 text-xs bg-blue-600 text-white hover:bg-blue-700 rounded"
                                                :disabled="editCommentForm.processing">
                                                {{ editCommentForm.processing ? 'Sauvegarde...' : 'Sauvegarder' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Formulaire de réponse -->
                                <div v-if="replyingTo === comment.id" class="mt-4 ml-6">
                                    <form @submit.prevent="submitReply(comment.id)" class="space-y-2">
                                        <textarea v-model="replyForm.content" rows="3"
                                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            placeholder="Votre réponse..." required></textarea>
                                        <div class="flex justify-end gap-2">
                                            <button @click="replyingTo = null" type="button"
                                                class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">
                                                Annuler
                                            </button>
                                            <button type="submit"
                                                class="px-3 py-1 text-xs bg-blue-600 text-white hover:bg-blue-700 rounded"
                                                :disabled="replyForm.processing">
                                                {{ replyForm.processing ? 'Envoi...' : 'Répondre' }}
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Réponses -->
                                <div v-if="comment.replies.length > 0" class="mt-4 ml-6 space-y-3">
                                    <div v-for="reply in comment.replies" :key="reply.id"
                                        class="bg-gray-100 dark:bg-gray-600 rounded-lg p-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <div
                                                class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                <span class="font-medium">{{ reply.author.firstname }} {{
                                                    reply.author.lastname
                                                }}</span>
                                                <span>•</span>
                                                <span>{{ formatDate(reply.created_at) }}</span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button @click="toggleCommentLike(reply)"
                                                    class="flex items-center gap-1 text-xs hover:text-red-500 transition-colors"
                                                    :class="{ 'text-red-500': reply.is_liked }"
                                                    :disabled="likingComments.includes(reply.id)">
                                                    <HeartIcon class="w-3 h-3"
                                                        :class="{ 'fill-current': reply.is_liked }" />
                                                    {{ reply.likes_count }}
                                                </button>

                                                <div v-if="reply.can_edit" class="flex items-center gap-1">
                                                    <button @click="deleteComment(reply)"
                                                        class="text-xs text-red-600 hover:text-red-800">
                                                        Supprimer
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-800 dark:text-gray-200">{{ reply.content }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message si aucun commentaire -->
                        <div v-if="comments.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <ChatBubbleLeftIcon class="w-12 h-12 mx-auto mb-4 opacity-50" />
                            <p>Aucun commentaire pour le moment.</p>
                            <p class="text-sm">Soyez le premier à commenter cet article !</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    article: Object,
    comments: Array,
});

// États réactifs
const editingComment = ref(null);
const replyingTo = ref(null);
const likingArticle = ref(false);
const likingComments = ref([]);

// Formulaires
const commentForm = useForm({
    content: '',
});

const replyForm = useForm({
    content: '',
});

const editCommentForm = useForm({
    content: '',
});

// Fonctions de gestion des likes
const toggleLike = () => {
    if (likingArticle.value) return;

    likingArticle.value = true;

    router.post(route('articles.like', props.article.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            // Mettre à jour les données de l'article avec la réponse
            const updatedArticle = page.props.article || page.props.flash?.article;
            if (updatedArticle) {
                props.article.is_liked = updatedArticle.is_liked;
                props.article.likes_count = updatedArticle.likes_count;
            }
            likingArticle.value = false;
        },
        onError: () => {
            likingArticle.value = false;
        },
    });
};

const toggleCommentLike = (comment) => {
    if (likingComments.value.includes(comment.id)) return;

    likingComments.value.push(comment.id);

    router.post(route('articles.comments.like', comment.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            // Mettre à jour les données du commentaire avec la réponse
            const updatedComment = page.props.flash?.comment;
            if (updatedComment) {
                comment.is_liked = updatedComment.is_liked;
                comment.likes_count = updatedComment.likes_count;
            }
            likingComments.value = likingComments.value.filter(id => id !== comment.id);
        },
        onError: () => {
            likingComments.value = likingComments.value.filter(id => id !== comment.id);
        },
    });
};

// Fonctions de gestion des commentaires
const submitComment = () => {
    commentForm.post(route('articles.comments.store', props.article.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            const newComment = page.props.flash?.comment;
            if (newComment) {
                props.comments.push(newComment);
            }
            commentForm.reset();
        },
    });
};

const submitReply = (parentId) => {
    const replyData = {
        content: replyForm.content,
        parent_id: parentId
    };

    router.post(route('articles.comments.store', props.article.id), replyData, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            const newReply = page.props.flash?.comment;
            if (newReply) {
                const parentComment = props.comments.find(c => c.id === parentId);
                if (parentComment) {
                    if (!parentComment.replies) {
                        parentComment.replies = [];
                    }
                    parentComment.replies.push(newReply);
                }
            }
            replyForm.reset();
            replyingTo.value = null;
        },
        onError: () => {
            // Gérer les erreurs si nécessaire
        }
    });
};

const startEditComment = (comment) => {
    editingComment.value = comment;
    editCommentForm.content = comment.content;
};

const cancelEditComment = () => {
    editingComment.value = null;
    editCommentForm.reset();
};

const updateComment = (comment) => {
    editCommentForm.put(route('articles.comments.update', comment.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            comment.content = editCommentForm.content;
            editingComment.value = null;
            editCommentForm.reset();
        },
    });
};

const deleteComment = (comment) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) {
        router.delete(route('articles.comments.destroy', comment.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Retirer le commentaire de la liste
                if (comment.parent_id) {
                    // C'est une réponse
                    const parentComment = props.comments.find(c => c.id === comment.parent_id);
                    if (parentComment && parentComment.replies) {
                        const index = parentComment.replies.findIndex(r => r.id === comment.id);
                        if (index > -1) {
                            parentComment.replies.splice(index, 1);
                        }
                    }
                } else {
                    // C'est un commentaire principal
                    const index = props.comments.findIndex(c => c.id === comment.id);
                    if (index > -1) {
                        props.comments.splice(index, 1);
                    }
                }
            }
        });
    }
};

const toggleReplyForm = (commentId) => {
    replyingTo.value = replyingTo.value === commentId ? null : commentId;
    if (replyingTo.value) {
        replyForm.reset();
    }
};

// Fonctions utilitaires
const togglePin = () => {
    router.post(route('articles.pin', props.article.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            props.article.is_pinned = !props.article.is_pinned;
        },
    });
};

const deleteArticle = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        router.delete(route('articles.destroy', props.article.id));
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatContent = (content) => {
    // Simple formatage Markdown vers HTML
    return content
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`(.*?)`/g, '<code>$1</code>')
        .replace(/\n\n/g, '</p><p>')
        .replace(/\n/g, '<br>')
        .replace(/^/, '<p>')
        .replace(/$/, '</p>');
};
</script>