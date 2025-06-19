<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useDateFormat } from '@vueuse/core'
import { computed, ref, onMounted, nextTick } from 'vue'

const props = defineProps({
    event: {
        type: Object,
        required: true
    }
})

// État pour l'onglet actif
const activeTab = ref('informations')

// État pour les posts et médias
const posts = ref([])
const mediaItems = ref([])
const isLoadingPosts = ref(false)
const isLoadingMedia = ref(false)

// État pour les nouveaux posts - SIMPLIFIÉ
const postForm = useForm({
    content: '',
    images: [],
    video: null
})

// Références pour les inputs de fichiers
const imageInput = ref(null)
const videoInput = ref(null)

// États pour les commentaires
const showComments = ref({})
const commentForms = ref({})

// Formatage des dates
const formatDate = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'dddd DD MMMM YYYY', { locales: 'fr' }).value
}

const formatTime = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'HH:mm').value
}

const formatDateTime = (date) => {
    if (!date) return ''
    return useDateFormat(date, 'dddd DD MMMM YYYY à HH:mm', { locales: 'fr' }).value
}

const formatRelativeTime = (date) => {
    const now = new Date()
    const postDate = new Date(date)
    const diffInMinutes = Math.floor((now - postDate) / (1000 * 60))

    if (diffInMinutes < 1) return 'À l\'instant'
    if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`

    const diffInHours = Math.floor(diffInMinutes / 60)
    if (diffInHours < 24) return `Il y a ${diffInHours}h`

    return useDateFormat(date, 'DD/MM à HH:mm', { locales: 'fr' }).value
}

// Couleurs selon la catégorie
const getCategoryColor = (category) => {
    const colors = {
        'competition': 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
        'entrainement': 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
        'manifestation': 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800'
    }
    return colors[category] || 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:border-gray-800'
}

// Icônes selon la catégorie
const getCategoryIcon = (category) => {
    const icons = {
        'competition': '🏆',
        'entrainement': '💪',
        'manifestation': '🎉'
    }
    return icons[category] || '📅'
}

// Calcul du prix d'affichage
const displayPrice = computed(() => {
    if (!props.event.price) return 'Gratuit'
    const priceMatch = props.event.price.toString().match(/\d+/)
    const numericPrice = priceMatch ? parseInt(priceMatch[0]) : 0
    return numericPrice > 0 ? `${numericPrice}€` : 'Gratuit'
})

// Gestion de la date d'affichage
const displayDate = computed(() => {
    if (!props.event.start_date) return ''
    const start = formatDate(props.event.start_date)
    const end = props.event.end_date ? formatDate(props.event.end_date) : null
    if (end && end !== start) {
        return { single: false, start, end }
    }
    return { single: true, date: start }
})

// Construction de l'adresse complète
const fullAddress = computed(() => {
    if (!props.event.address) return null
    const parts = []
    if (props.event.address.house_number) parts.push(props.event.address.house_number)
    if (props.event.address.street_name) parts.push(props.event.address.street_name)
    const street = parts.join(' ')
    const cityPostal = [props.event.address.city, props.event.address.postal_code].filter(Boolean).join(' ')
    return {
        street: street || null,
        cityPostal: cityPostal || null,
        country: props.event.address.country || null
    }
})

// Configuration des onglets
const tabs = [
    {
        key: 'informations',
        label: 'Informations',
        icon: '📋',
        description: 'Détails de l\'événement'
    },
    {
        key: 'posts',
        label: 'Posts',
        icon: '💬',
        description: 'Posts et discussions'
    },
    {
        key: 'medias',
        label: 'Médias',
        icon: '📸',
        description: 'Photos et vidéos'
    }
]

// Fonction pour changer d'onglet
const setActiveTab = (tab) => {
    activeTab.value = tab
    if (tab === 'posts' && posts.value.length === 0) {
        loadPosts()
    } else if (tab === 'medias' && mediaItems.value.length === 0) {
        loadMedia()
    }
}

// Fonctions de gestion des posts - AMÉLIORÉES
const submitPost = async () => {
    if (!postForm.content.trim()) return

    const formData = new FormData()
    formData.append('content', postForm.content)

    // Ajouter les images
    if (postForm.images && postForm.images.length > 0) {
        postForm.images.forEach((image, index) => {
            formData.append(`images[${index}]`, image)
        })
    }

    // Ajouter la vidéo
    if (postForm.video) {
        formData.append('video', postForm.video)
    }

    try {
        const response = await fetch(route('events.posts.store', props.event.id), {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })

        if (response.ok) {
            const data = await response.json()
            posts.value.unshift(data.post)

            // Réinitialiser le formulaire
            postForm.reset()

            // Réinitialiser les inputs de fichiers
            if (imageInput.value) {
                imageInput.value.value = ''
            }
            if (videoInput.value) {
                videoInput.value.value = ''
            }
        } else {
            const errorData = await response.json()
            console.error('Erreur lors de la publication:', errorData)
        }
    } catch (error) {
        console.error('Erreur lors de la publication:', error)
    }
}

const handleImageUpload = (event) => {
    const files = Array.from(event.target.files)
    postForm.images = files.slice(0, 4) // Limiter à 4 images
}

const handleVideoUpload = (event) => {
    const file = event.target.files[0]
    postForm.video = file
}

const removeImage = (index) => {
    postForm.images.splice(index, 1)
    // Recréer l'input pour permettre de re-sélectionner le même fichier
    if (imageInput.value) {
        const newInput = imageInput.value.cloneNode(true)
        newInput.value = ''
        imageInput.value.parentNode.replaceChild(newInput, imageInput.value)
        imageInput.value = newInput
    }
}

const removeVideo = () => {
    postForm.video = null
    // Recréer l'input pour permettre de re-sélectionner le même fichier
    if (videoInput.value) {
        const newInput = videoInput.value.cloneNode(true)
        newInput.value = ''
        videoInput.value.parentNode.replaceChild(newInput, videoInput.value)
        videoInput.value = newInput
    }
}

// Fonction pour déclencher la sélection d'images
const triggerImageSelect = () => {
    if (imageInput.value) {
        imageInput.value.click()
    }
}

// Fonction pour déclencher la sélection de vidéo
const triggerVideoSelect = () => {
    if (videoInput.value) {
        videoInput.value.click()
    }
}

// Fonctions pour les posts
const loadPosts = async () => {
    if (isLoadingPosts.value) return

    isLoadingPosts.value = true
    try {
        const response = await fetch(route('events.posts.index', props.event.id))
        const data = await response.json()
        posts.value = data.posts.data
    } catch (error) {
        console.error('Erreur lors du chargement des posts:', error)
    } finally {
        isLoadingPosts.value = false
    }
}

const loadMedia = async () => {
    if (isLoadingMedia.value) return

    isLoadingMedia.value = true
    try {
        const response = await fetch(route('events.media.index', props.event.id))
        const data = await response.json()
        mediaItems.value = data.media
    } catch (error) {
        console.error('Erreur lors du chargement des médias:', error)
    } finally {
        isLoadingMedia.value = false
    }
}

const toggleLike = async (postId) => {
    try {
        const response = await fetch(route('articles.like', postId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })

        if (response.ok) {
            const data = await response.json()
            const post = posts.value.find(p => p.id === postId)
            if (post) {
                post.is_liked = data.liked
                post.likes_count = data.likes_count
            }
        }
    } catch (error) {
        console.error('Erreur lors du like:', error)
    }
}

const toggleComments = (postId) => {
    showComments.value[postId] = !showComments.value[postId]
}

// Chargement initial
onMounted(() => {
    if (activeTab.value === 'posts') {
        loadPosts()
    }
})
</script>

<template>

    <Head :title="event.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link :href="route('events.index')"
                        class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux événements
                    </Link>

                    <div class="text-gray-300 dark:text-gray-600">•</div>

                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border"
                        :class="getCategoryColor(event.category)">
                        <span class="mr-1">{{ getCategoryIcon(event.category) }}</span>
                        <span class="capitalize">{{ event.category }}</span>
                    </span>

                    <div class="text-gray-300 dark:text-gray-600">•</div>

                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                        En cours
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

                <!-- En-tête de l'événement -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
                    <!-- Image d'illustration -->
                    <div class="relative h-48 md:h-64 overflow-hidden">
                        <img v-if="event.illustration?.url" :src="event.illustration.url" :alt="event.title"
                            class="w-full h-full object-cover">

                        <div v-else class="w-full h-full relative" :class="{
                            'bg-gradient-to-br from-red-200 via-red-300 to-red-500 dark:from-red-800 dark:via-red-700 dark:to-red-600': event.category === 'competition',
                            'bg-gradient-to-br from-blue-200 via-blue-300 to-blue-500 dark:from-blue-800 dark:via-blue-700 dark:to-blue-600': event.category === 'entrainement',
                            'bg-gradient-to-br from-green-200 via-green-300 to-green-500 dark:from-green-800 dark:via-green-700 dark:to-green-600': event.category === 'manifestation',
                            'bg-gradient-to-br from-gray-200 via-gray-300 to-gray-500 dark:from-gray-800 dark:via-gray-700 dark:to-gray-600': !event.category
                        }">
                            <svg class="absolute inset-0 w-full h-full opacity-20" viewBox="0 0 400 200"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="80" cy="80" r="40" fill="none" stroke="currentColor" stroke-width="2"
                                    opacity="0.6" />
                                <polygon points="300,50 330,100 270,100" fill="currentColor" opacity="0.3" />
                                <line x1="0" y1="150" x2="150" y2="200" stroke="currentColor" stroke-width="2"
                                    opacity="0.4" />
                            </svg>
                        </div>

                        <!-- Prix en overlay -->
                        <div class="absolute top-4 right-4">
                            <span
                                class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold bg-white/90 dark:bg-gray-800/90 text-gray-900 dark:text-white shadow-lg backdrop-blur-sm">
                                {{ displayPrice }}
                            </span>
                        </div>
                    </div>

                    <!-- Contenu de l'en-tête -->
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                    {{ event.title }}
                                </h1>
                                <p v-if="event.description" class="text-gray-600 dark:text-gray-400 mb-4">
                                    {{ event.description }}
                                </p>
                                <div class="flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ event.participants_count }} participant{{ event.participants_count !== 1 ?
                                        's' : ''
                                        }}</span>
                                    <span v-if="fullAddress">📍 {{ fullAddress.cityPostal }}</span>
                                    <span>👤 {{ event.organizer.firstname }} {{ event.organizer.lastname }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation par onglets -->
                <div class="mb-8">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8">
                            <button v-for="tab in tabs" :key="tab.key" @click="setActiveTab(tab.key)"
                                class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                :class="activeTab === tab.key
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
                                <span class="mr-2 text-lg">{{ tab.icon }}</span>
                                <div class="flex flex-col items-start">
                                    <span>{{ tab.label }}</span>
                                    <span
                                        class="text-xs text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400">
                                        {{ tab.description }}
                                    </span>
                                </div>
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Contenu des onglets -->
                <div class="space-y-8">

                    <!-- Onglet Informations -->
                    <div v-if="activeTab === 'informations'" class="space-y-6">
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Informations détaillées
                            </h2>

                            <div class="grid md:grid-cols-2 gap-8">
                                <!-- Colonne gauche - Informations temporelles -->
                                <div class="space-y-6">
                                    <!-- Dates de l'événement -->
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">📅 Dates</h3>
                                        <div v-if="displayDate.single" class="text-gray-600 dark:text-gray-400">
                                            {{ displayDate.date }}
                                        </div>
                                        <div v-else class="text-gray-600 dark:text-gray-400">
                                            <div>Du {{ displayDate.start }}</div>
                                            <div>au {{ displayDate.end }}</div>
                                        </div>
                                    </div>

                                    <!-- Horaires -->
                                    <div v-if="formatTime(event.start_date)"
                                        class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">🕒 Horaires</h3>
                                        <div class="text-gray-600 dark:text-gray-400">
                                            <div>Début : {{ formatTime(event.start_date) }}</div>
                                            <div v-if="event.end_date">Fin : {{ formatTime(event.end_date) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Colonne droite - Informations pratiques -->
                                <div class="space-y-6">
                                    <!-- Lieu -->
                                    <div v-if="fullAddress" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">📍 Lieu</h3>
                                        <div class="text-gray-600 dark:text-gray-400 space-y-1">
                                            <div v-if="fullAddress.street">{{ fullAddress.street }}</div>
                                            <div v-if="fullAddress.cityPostal">{{ fullAddress.cityPostal }}</div>
                                            <div v-if="fullAddress.country">{{ fullAddress.country }}</div>
                                        </div>
                                    </div>

                                    <!-- Participants -->
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">👥 Participants</h3>
                                        <div class="text-gray-600 dark:text-gray-400">
                                            <div class="flex items-center justify-between">
                                                <span>{{ event.participants_count }} inscrit{{ event.participants_count
                                                    !== 1 ?
                                                    's' : '' }}</span>
                                                <span v-if="event.max_participants" class="text-gray-500">
                                                    / {{ event.max_participants }} max
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Posts -->
                    <div v-if="activeTab === 'posts'" class="space-y-6">

                        <!-- Formulaire de nouveau post - SIMPLIFIÉ -->
                        <div v-if="event.can_create_article"
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">

                            <!-- Avatar et zone de texte toujours visible -->
                            <div class="flex items-start space-x-4">
                                <div
                                    class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                    <img v-if="$page.props.auth.user.profile_picture?.url"
                                        :src="$page.props.auth.user.profile_picture.url" alt="Avatar de l'utilisateur"
                                        class="w-full h-full object-cover rounded-full">

                                    <span v-else class="text-blue-600 dark:text-blue-400 font-medium">
                                        {{ $page.props.auth.user.firstname[0] }}{{ $page.props.auth.user.lastname[0] }}
                                    </span>
                                </div>

                                <div class="flex-1 space-y-4">
                                    <!-- Zone de texte principale -->
                                    <textarea v-model="postForm.content" rows="3"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Partagez un moment de l'événement..."></textarea>

                                    <!-- Prévisualisation des images -->
                                    <div v-if="postForm.images && postForm.images.length > 0"
                                        class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div v-for="(image, index) in postForm.images" :key="index" class="relative">
                                            <img :src="URL.createObjectURL(image)"
                                                class="w-full h-24 object-cover rounded-lg">
                                            <button @click="removeImage(index)"
                                                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-sm hover:bg-red-600">
                                                ×
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Prévisualisation de la vidéo -->
                                    <div v-if="postForm.video" class="relative">
                                        <video :src="URL.createObjectURL(postForm.video)"
                                            class="w-full h-48 object-cover rounded-lg" controls></video>
                                        <button @click="removeVideo"
                                            class="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600">
                                            ×
                                        </button>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <!-- Input caché pour les images -->
                                            <input ref="imageInput" type="file" multiple accept="image/*"
                                                @change="handleImageUpload" class="hidden">

                                            <!-- Input caché pour la vidéo -->
                                            <input ref="videoInput" type="file" accept="video/*"
                                                @change="handleVideoUpload" class="hidden">

                                            <!-- Bouton pour les photos -->
                                            <button @click="triggerImageSelect" type="button"
                                                class="flex items-center space-x-2 cursor-pointer text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-sm">Photos</span>
                                            </button>

                                            <!-- Bouton pour la vidéo -->
                                            <button @click="triggerVideoSelect" type="button"
                                                class="flex items-center space-x-2 cursor-pointer text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-sm">Vidéo</span>
                                            </button>
                                        </div>

                                        <button @click="submitPost" :disabled="!postForm.content.trim()"
                                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                            Publier
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Liste des posts -->
                        <div class="space-y-6">
                            <div v-if="isLoadingPosts" class="text-center py-8">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600">
                                </div>
                            </div>

                            <div v-for="post in posts" :key="post.id"
                                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">

                                <!-- En-tête du post -->
                                <div class="flex items-start space-x-3 mb-4">
                                    <div
                                        class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                                        <span class="text-white font-medium text-sm">
                                            {{ post.author.firstname[0] }}{{ post.author.lastname[0] }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ post.author.firstname }} {{ post.author.lastname }}
                                            </p>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatRelativeTime(post.created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contenu du post -->
                                <div class="mb-4">
                                    <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ post.content }}</p>
                                </div>

                                <!-- Médias -->
                                <div v-if="post.media_files && post.media_files.length > 0" class="mb-4">
                                    <div class="grid gap-2" :class="{
                                        'grid-cols-1': post.media_files.length === 1,
                                        'grid-cols-2': post.media_files.length === 2,
                                        'grid-cols-2 md:grid-cols-3': post.media_files.length >= 3
                                    }">
                                        <div v-for="media in post.media_files" :key="media.url">
                                            <img v-if="media.type === 'image'" :src="media.url" :alt="media.name"
                                                class="w-full h-48 object-cover rounded-lg cursor-pointer hover:opacity-90 transition-opacity">
                                            <video v-else-if="media.type === 'video'" :src="media.url"
                                                class="w-full h-48 object-cover rounded-lg" controls></video>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions du post -->
                                <div
                                    class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center space-x-6">
                                        <button @click="toggleLike(post.id)"
                                            class="flex items-center space-x-2 text-gray-500 dark:text-gray-400 hover:text-red-500 transition-colors">
                                            <svg class="w-5 h-5"
                                                :class="post.is_liked ? 'text-red-500 fill-current' : ''" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                            <span class="text-sm">{{ post.likes_count }}</span>
                                        </button>

                                        <button @click="toggleComments(post.id)"
                                            class="flex items-center space-x-2 text-gray-500 dark:text-gray-400 hover:text-blue-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.418 8-8 8a9.862 9.862 0 01-4.343-.896L3 21l1.896-5.657C3.696 15.336 3 13.727 3 12c0-4.418 4.418-8 8-8s8 3.582 8 8z" />
                                            </svg>
                                            <span class="text-sm">{{ post.comments_count }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Section commentaires -->
                                <div v-if="showComments[post.id]"
                                    class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                                        Les commentaires seront disponibles prochainement
                                    </div>
                                </div>
                            </div>

                            <!-- Message si aucun post -->
                            <div v-if="!isLoadingPosts && posts.length === 0" class="text-center py-12">
                                <div
                                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.418 8-8 8a9.862 9.862 0 01-4.343-.896L3 21l1.896-5.657C3.696 15.336 3 13.727 3 12c0-4.418 4.418-8 8-8s8 3.582 8 8z" />
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                        Aucun post pour le moment
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        Soyez le premier à partager ce qui se passe !
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Médias -->
                    <div v-if="activeTab === 'medias'" class="space-y-6">
                        <div v-if="isLoadingMedia" class="text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600">
                            </div>
                        </div>

                        <div v-else-if="mediaItems.length > 0"
                            class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div v-for="media in mediaItems" :key="media.id"
                                class="group relative bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">

                                <div class="aspect-square">
                                    <img v-if="media.type === 'image'" :src="media.url" :alt="media.name"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    <video v-else-if="media.type === 'video'" :src="media.url"
                                        class="w-full h-full object-cover">
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/50">
                                            <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </div>
                                    </video>
                                </div>

                                <!-- Overlay avec infos -->
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                    <div class="absolute bottom-2 left-2 right-2">
                                        <p class="text-white text-xs font-medium">{{ media.author.firstname }} {{
                                            media.author.lastname }}</p>
                                        <p class="text-white/80 text-xs">{{ formatRelativeTime(media.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-12">
                            <div
                                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    Aucun média partagé
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Les photos et vidéos partagées apparaîtront ici
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}
</style>