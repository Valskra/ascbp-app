<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    postId: {
        type: Number,
        required: true
    },
    parentId: {
        type: Number,
        default: null
    },
    placeholder: {
        type: String,
        default: 'Écrivez un commentaire...'
    },
    compact: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['submitted', 'cancelled'])

const commentForm = useForm({
    content: '',
    parent_id: props.parentId
})

const isSubmitting = ref(false)

const canSubmit = computed(() => {
    return commentForm.content.trim().length > 0 && !isSubmitting.value
})

const submitComment = async () => {
    if (!canSubmit.value) return

    isSubmitting.value = true

    try {
        const response = await fetch(route('articles.comments.store', props.postId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                content: commentForm.content,
                parent_id: commentForm.parent_id
            })
        })

        if (response.ok) {
            const data = await response.json()
            emit('submitted', data.comment)
            commentForm.reset()
        }
    } catch (error) {
        console.error('Erreur lors de l\'envoi du commentaire:', error)
    } finally {
        isSubmitting.value = false
    }
}

const cancel = () => {
    commentForm.reset()
    emit('cancelled')
}

const handleKeydown = (event) => {
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault()
        submitComment()
    }
    if (event.key === 'Escape') {
        cancel()
    }
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex space-x-3">
            <!-- Avatar de l'utilisateur -->
            <div class="flex-shrink-0">
                <div
                    class="w-8 h-8 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                    <span class="text-white font-medium text-xs">
                        {{ $page.props.auth.user.firstname[0] }}{{ $page.props.auth.user.lastname[0] }}
                    </span>
                </div>
            </div>

            <!-- Champ de saisie -->
            <div class="flex-1">
                <textarea v-model="commentForm.content" :placeholder="placeholder" :rows="compact ? 1 : 2"
                    @keydown="handleKeydown"
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'opacity-50': isSubmitting }"></textarea>
            </div>
        </div>

        <!-- Actions -->
        <div v-if="commentForm.content.trim().length > 0" class="flex items-center justify-between ml-11">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                <kbd
                    class="px-1 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">Ctrl</kbd>
                +
                <kbd
                    class="px-1 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">Entrée</kbd>
                pour publier
            </div>

            <div class="flex items-center space-x-2">
                <button @click="cancel" type="button"
                    class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                    Annuler
                </button>

                <button @click="submitComment" :disabled="!canSubmit"
                    class="px-4 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center space-x-2">
                    <span v-if="isSubmitting">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </span>
                    <span>{{ isSubmitting ? 'Publication...' : 'Publier' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

kbd {
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1), 0 2px 2px rgba(0, 0, 0, 0.05);
}
</style>