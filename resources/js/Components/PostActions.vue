<script setup>
import { ref } from 'vue'

const props = defineProps({
    post: {
        type: Object,
        required: true
    },
    showComments: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['like', 'comment', 'toggleComments'])

const isLiking = ref(false)

const handleLike = async () => {
    if (isLiking.value) return

    isLiking.value = true
    try {
        emit('like', props.post.id)
    } finally {
        isLiking.value = false
    }
}

const handleComment = () => {
    emit('comment', props.post.id)
}

const handleToggleComments = () => {
    emit('toggleComments', props.post.id)
}
</script>

<template>
    <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
        <div class="flex items-center space-x-6">
            <!-- Bouton Like -->
            <button @click="handleLike" :disabled="isLiking"
                class="flex items-center space-x-2 text-gray-500 dark:text-gray-400 hover:text-red-500 transition-colors disabled:opacity-50">
                <svg class="w-5 h-5 transition-all duration-200" :class="[
                    post.is_liked ? 'text-red-500 fill-current scale-110' : '',
                    isLiking ? 'animate-pulse' : ''
                ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span class="text-sm font-medium">
                    {{ post.likes_count }}
                    <span v-if="post.likes_count !== 1" class="hidden sm:inline">J'aime</span>
                    <span v-else class="hidden sm:inline">J'aime</span>
                </span>
            </button>

            <!-- Bouton Commentaires -->
            <button @click="handleToggleComments"
                class="flex items-center space-x-2 text-gray-500 dark:text-gray-400 hover:text-blue-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.418 8-8 8a9.862 9.862 0 01-4.343-.896L3 21l1.896-5.657C3.696 15.336 3 13.727 3 12c0-4.418 4.418-8 8-8s8 3.582 8 8z" />
                </svg>
                <span class="text-sm font-medium">
                    {{ post.comments_count }}
                    <span v-if="post.comments_count !== 1" class="hidden sm:inline">commentaires</span>
                    <span v-else class="hidden sm:inline">commentaire</span>
                </span>
            </button>

            <!-- Bouton Partager -->
            <button
                class="flex items-center space-x-2 text-gray-500 dark:text-gray-400 hover:text-green-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                </svg>
                <span class="text-sm hidden sm:inline">Partager</span>
            </button>
        </div>

        <!-- Menu d'options -->
        <div v-if="post.can_edit" class="relative">
            <button
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
            </button>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>