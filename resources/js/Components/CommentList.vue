<script setup>
import { ref } from 'vue'
import { useDateFormat } from '@vueuse/core'
import CommentForm from './CommentForm.vue'

const props = defineProps({
    comments: {
        type: Array,
        default: () => []
    },
    postId: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['commentAdded', 'commentLiked'])

const showReplyForm = ref({})
const isLikingComment = ref({})

const formatRelativeTime = (date) => {
    const now = new Date()
    const commentDate = new Date(date)
    const diffInMinutes = Math.floor((now - commentDate) / (1000 * 60))

    if (diffInMinutes < 1) return 'À l\'instant'
    if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`

    const diffInHours = Math.floor(diffInMinutes / 60)
    if (diffInHours < 24) return `Il y a ${diffInHours}h`

    return useDateFormat(date, 'DD/MM à HH:mm', { locales: 'fr' }).value
}

const toggleReplyForm = (commentId) => {
    showReplyForm.value[commentId] = !showReplyForm.value[commentId]
}

const handleCommentAdded = (comment, parentId = null) => {
    emit('commentAdded', { comment, parentId })
    if (parentId) {
        showReplyForm.value[parentId] = false
    }
}

const likeComment = async (commentId) => {
    if (isLikingComment.value[commentId]) return

    isLikingComment.value[commentId] = true

    try {
        const response = await fetch(route('articles.comments.like', commentId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })

        if (response.ok) {
            const data = await response.json()
            emit('commentLiked', { commentId, liked: data.liked, likesCount: data.likes_count })
        }
    } catch (error) {
        console.error('Erreur lors du like du commentaire:', error)
    } finally {
        isLikingComment.value[commentId] = false
    }
}
</script>

<template>
    <div class="space-y-4">
        <!-- Formulaire de nouveau commentaire -->
        <CommentForm :post-id="postId" @submitted="(comment) => handleCommentAdded(comment)"
            placeholder="Écrivez un commentaire..." />

        <!-- Liste des commentaires -->
        <div v-if="comments.length > 0" class="space-y-4">
            <div v-for="comment in comments" :key="comment.id" class="space-y-3">

                <!-- Commentaire principal -->
                <div class="flex space-x-3">
                    <div class="flex-shrink-0">
                        <div
                            class="w-8 h-8 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-medium text-xs">
                                {{ comment.author.firstname[0] }}{{ comment.author.lastname[0] }}
                            </span>
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <!-- En-tête du commentaire -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="font-medium text-sm text-gray-900 dark:text-white">
                                    {{ comment.author.firstname }} {{ comment.author.lastname }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatRelativeTime(comment.created_at) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ comment.content }}
                            </p>
                        </div>

                        <!-- Actions du commentaire -->
                        <div class="flex items-center space-x-4 mt-2 text-xs">
                            <button @click="likeComment(comment.id)" :disabled="isLikingComment[comment.id]"
                                class="flex items-center space-x-1 text-gray-500 dark:text-gray-400 hover:text-blue-600 transition-colors disabled:opacity-50">
                                <svg class="w-4 h-4" :class="comment.is_liked ? 'text-blue-600 fill-current' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                </svg>
                                <span>{{ comment.likes_count > 0 ? comment.likes_count : '' }} J'aime</span>
                            </button>

                            <button @click="toggleReplyForm(comment.id)"
                                class="text-gray-500 dark:text-gray-400 hover:text-blue-600 transition-colors">
                                Répondre
                            </button>

                            <button v-if="comment.can_edit"
                                class="text-gray-500 dark:text-gray-400 hover:text-blue-600 transition-colors">
                                Modifier
                            </button>
                        </div>

                        <!-- Formulaire de réponse -->
                        <div v-if="showReplyForm[comment.id]" class="mt-3">
                            <CommentForm :post-id="postId" :parent-id="comment.id"
                                @submitted="(reply) => handleCommentAdded(reply, comment.id)"
                                @cancelled="toggleReplyForm(comment.id)"
                                :placeholder="`Répondre à ${comment.author.firstname}...`" compact />
                        </div>

                        <!-- Réponses -->
                        <div v-if="comment.replies && comment.replies.length > 0" class="mt-4 space-y-3">
                            <div v-for="reply in comment.replies" :key="reply.id" class="flex space-x-3">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-7 h-7 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center">
                                        <span class="text-white font-medium text-xs">
                                            {{ reply.author.firstname[0] }}{{ reply.author.lastname[0] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="font-medium text-sm text-gray-900 dark:text-white">
                                                {{ reply.author.firstname }} {{ reply.author.lastname }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatRelativeTime(reply.created_at) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{
                                            reply.content }}</p>
                                    </div>

                                    <div class="flex items-center space-x-4 mt-2 text-xs">
                                        <button @click="likeComment(reply.id)" :disabled="isLikingComment[reply.id]"
                                            class="flex items-center space-x-1 text-gray-500 dark:text-gray-400 hover:text-blue-600 transition-colors disabled:opacity-50">
                                            <svg class="w-4 h-4"
                                                :class="reply.is_liked ? 'text-blue-600 fill-current' : ''" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                            </svg>
                                            <span>{{ reply.likes_count > 0 ? reply.likes_count : '' }} J'aime</span>
                                        </button>

                                        <button v-if="reply.can_edit"
                                            class="text-gray-500 dark:text-gray-400 hover:text-blue-600 transition-colors">
                                            Modifier
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message si aucun commentaire -->
        <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm">
            <p>Aucun commentaire pour le moment.</p>
            <p class="text-xs mt-1">Soyez le premier à commenter !</p>
        </div>
    </div>
</template>