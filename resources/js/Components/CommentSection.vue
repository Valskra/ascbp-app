
<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useDateFormat } from '@vueuse/core'

const props = defineProps({
    comments: {
        type: Array,
        default: () => []
    },
    postId: {
        type: Number,
        required: true
    },
    eventId: {
        type: Number,
        required: true
    },
    canComment: {
        type: Boolean,
        default: false
    },
    currentUser: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['commentsUpdated'])

// État des formulaires
const newCommentContent = ref('')
const isSubmittingComment = ref(false)
const isCorrectingComment = ref(false)

// État des réponses
const replyingTo = ref(null)
const replyContent = ref('')

// État d'édition
const editingComment = ref(null)
const editCommentContent = ref('')

// Formatage des dates
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

// Correction IA pour commentaires
const correctCommentWithAI = async () => {
    if (!newCommentContent.value.trim() || isCorrectingComment.value) return
    
    isCorrectingComment.value = true
    
    try {
        const response = await fetch('/ai-assistant/correct-chatgpt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                content: newCommentContent.value
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            newCommentContent.value = data.corrected_content
        } else {
            console.error('Erreur IA:', data.error)
        }
    } catch (error) {
        console.error('Erreur réseau:', error)
    } finally {
        isCorrectingComment.value = false
    }
}

// Soumettre un nouveau commentaire
const submitComment = async () => {
    if (!newCommentContent.value.trim() || isSubmittingComment.value) return
    
    isSubmittingComment.value = true
    
    try {
        const response = await fetch(route('events.posts.comments.store', [props.eventId, props.postId]), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                content: newCommentContent.value
            })
        })
        
        if (response.ok) {
            const data = await response.json()
            newCommentContent.value = ''
            emit('commentsUpdated')
        } else {
            console.error('Erreur lors de la publication du commentaire')
        }
    } catch (error) {
        console.error('Erreur réseau:', error)
    } finally {
        isSubmittingComment.value = false
    }
}

// Liker/déliker un commentaire
const toggleCommentLike = async (comment) => {
    try {
        const response = await fetch(route('events.posts.comments.like', [props.eventId, comment.id]), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        
        if (response.ok) {
            const data = await response.json()
            comment.is_liked = data.liked
            comment.likes_count = data.likes_count
        }
    } catch (error) {
        console.error('Erreur lors du like:', error)
    }
}

// Gestion des réponses
const showReplyForm = (comment) => {
    replyingTo.value = comment.id
    replyContent.value = ''
}

const cancelReply = () => {
    replyingTo.value = null
    replyContent.value = ''
}

const submitReply = async (parentComment) => {
    if (!replyContent.value.trim()) return
    
    try {
        const response = await fetch(route('events.posts.comments.store', [props.eventId, props.postId]), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                content: replyContent.value,
                parent_id: parentComment.id
            })
        })
        
        if (response.ok) {
            cancelReply()
            emit('commentsUpdated')
        }
    } catch (error) {
        console.error('Erreur lors de la réponse:', error)
    }
}

// Gestion de l'édition
const startEditComment = (comment) => {
    editingComment.value = comment.id
    editCommentContent.value = comment.content
}

const cancelEditComment = () => {
    editingComment.value = null
    editCommentContent.value = ''
}

const saveEditComment = async (comment) => {
    if (!editCommentContent.value.trim()) return
    
    try {
        const response = await fetch(route('events.posts.comments.update', [props.eventId, comment.id]), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                content: editCommentContent.value
            })
        })
        
        if (response.ok) {
            comment.content = editCommentContent.value
            cancelEditComment()
        }
    } catch (error) {
        console.error('Erreur lors de la modification:', error)
    }
}

// Supprimer un commentaire
const deleteComment = async (comment) => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) return
    
    try {
        const response = await fetch(route('events.posts.comments.destroy', [props.eventId, comment.id]), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        
        if (response.ok) {
            emit('commentsUpdated')
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error)
    }
}
</script>

<template>
    
    <div class="space-y-4">
        <!-- Formulaire de nouveau commentaire -->
        <div v-if="canComment" class="border-t border-gray-100 dark:border-gray-700 pt-4">
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-blue-600 dark:text-blue-400 font-medium text-sm">
                        {{ currentUser.firstname[0] }}{{ currentUser.lastname[0] }}
                    </span>
                </div>
                <div class="flex-1">
                    <div class="relative">
                        <textarea 
                            v-model="newCommentContent" 
                            rows="2"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :placeholder="'Ajouter un commentaire...'"
                            @keydown.enter.ctrl="submitComment"
                            @keydown.enter.meta="submitComment">
                        </textarea>
                        
                        <!-- Bouton d'assistance IA pour commentaires -->
                        <button v-if="newCommentContent.trim()" @click="correctCommentWithAI" 
                            :disabled="isCorrectingComment"
                            class="absolute bottom-2 right-2 px-2 py-1 text-xs bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-800/50 text-purple-700 dark:text-purple-300 rounded transition-colors disabled:opacity-50">
                            <span v-if="!isCorrectingComment" class="flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span>IA</span>
                            </span>
                            <span v-else class="flex items-center space-x-1">
                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>...</span>
                            </span>
                        </button>
                    </div>
                    
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Ctrl+Entrée pour publier
                        </span>
                        <button 
                            @click="submitComment" 
                            :disabled="!newCommentContent.trim() || isSubmittingComment"
                            class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <span v-if="!isSubmittingComment">Commenter</span>
                            <span v-else class="flex items-center space-x-1">
                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>...</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message pour utilisateurs non connectés -->
        <div v-else-if="!currentUser" class="border-t border-gray-100 dark:border-gray-700 pt-4">
            <div class="text-center py-3 text-gray-500 dark:text-gray-400 text-sm">
                <Link :href="route('login')" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    Connectez-vous
                </Link>
                pour participer à la discussion
            </div>
        </div>

        <!-- Liste des commentaires -->
        <div v-if="comments.length > 0" class="space-y-4">
            <div v-for="comment in comments" :key="comment.id" class="space-y-3">
                <!-- Commentaire principal -->
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-gray-400 to-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-medium text-sm">
                            {{ comment.author.firstname[0] }}{{ comment.author.lastname[0] }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="font-medium text-sm text-gray-900 dark:text-white">
                                    {{ comment.author.firstname }} {{ comment.author.lastname }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatRelativeTime(comment.created_at) }}
                                </span>
                            </div>
                            
                            <!-- Mode édition -->
                            <div v-if="editingComment === comment.id" class="space-y-2">
                                <textarea 
                                    v-model="editCommentContent" 
                                    rows="2"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none focus:ring-1 focus:ring-blue-500">
                                </textarea>
                                <div class="flex items-center space-x-2">
                                    <button @click="saveEditComment(comment)" 
                                        class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                        Sauvegarder
                                    </button>
                                    <button @click="cancelEditComment" 
                                        class="px-2 py-1 bg-gray-600 text-white text-xs rounded hover:bg-gray-700">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Mode lecture -->
                            <div v-else>
                                <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ comment.content }}</p>
                            </div>
                        </div>
                        
                        <!-- Actions du commentaire -->
                        <div class="flex items-center space-x-4 mt-2 text-xs">
                            <button @click="toggleCommentLike(comment)" 
                                class="flex items-center space-x-1 text-gray-500 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" :class="comment.is_liked ? 'text-red-500 fill-current' : ''" 
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <span>{{ comment.likes_count }}</span>
                            </button>
                            
                            <button @click="showReplyForm(comment)" 
                                class="text-gray-500 hover:text-blue-500 transition-colors">
                                Répondre
                            </button>
                            
                            <button v-if="comment.can_edit" @click="startEditComment(comment)" 
                                class="text-gray-500 hover:text-green-500 transition-colors">
                                Modifier
                            </button>
                            
                            <button v-if="comment.can_delete" @click="deleteComment(comment)" 
                                class="text-gray-500 hover:text-red-500 transition-colors">
                                Supprimer
                            </button>
                        </div>
                        
                        <!-- Formulaire de réponse -->
                        <div v-if="replyingTo === comment.id" class="mt-3 flex items-start space-x-2">
                            <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-blue-600 dark:text-blue-400 font-medium text-xs">
                                    {{ currentUser.firstname[0] }}{{ currentUser.lastname[0] }}
                                </span>
                            </div>
                            <div class="flex-1">
                                <textarea 
                                    v-model="replyContent" 
                                    rows="2"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none focus:ring-1 focus:ring-blue-500"
                                    :placeholder="`Répondre à ${comment.author.firstname}...`">
                                </textarea>
                                <div class="flex items-center space-x-2 mt-2">
                                    <button @click="submitReply(comment)" 
                                        :disabled="!replyContent.trim()"
                                        class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 disabled:opacity-50">
                                        Répondre
                                    </button>
                                    <button @click="cancelReply" 
                                        class="px-2 py-1 bg-gray-600 text-white text-xs rounded hover:bg-gray-700">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Réponses -->
                        <div v-if="comment.replies && comment.replies.length > 0" class="mt-3 space-y-2 ml-6">
                            <div v-for="reply in comment.replies" :key="reply.id" class="flex items-start space-x-2">
                                <div class="w-6 h-6 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-white font-medium text-xs">
                                        {{ reply.author.firstname[0] }}{{ reply.author.lastname[0] }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded px-2 py-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="font-medium text-xs text-gray-900 dark:text-white">
                                                {{ reply.author.firstname }} {{ reply.author.lastname }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatRelativeTime(reply.created_at) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-900 dark:text-white whitespace-pre-wrap">{{ reply.content }}</p>
                                    </div>
                                    
                                    <!-- Actions de la réponse -->
                                    <div class="flex items-center space-x-3 mt-1 text-xs">
                                        <button @click="toggleCommentLike(reply)" 
                                            class="flex items-center space-x-1 text-gray-500 hover:text-red-500 transition-colors">
                                            <svg class="w-3 h-3" :class="reply.is_liked ? 'text-red-500 fill-current' : ''" 
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                            <span>{{ reply.likes_count }}</span>
                                        </button>
                                        
                                        <button v-if="reply.can_edit" @click="startEditComment(reply)" 
                                            class="text-gray-500 hover:text-green-500 transition-colors">
                                            Modifier
                                        </button>
                                        
                                        <button v-if="reply.can_delete" @click="deleteComment(reply)" 
                                            class="text-gray-500 hover:text-red-500 transition-colors">
                                            Supprimer
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
        <div v-else class="border-t border-gray-100 dark:border-gray-700 pt-4">
            <div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                {{ canComment ? 'Soyez le premier à commenter !' : 'Aucun commentaire pour le moment' }}
            </div>
        </div>
    </div>
</template>
