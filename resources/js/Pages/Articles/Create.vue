<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ isEditing ? 'Modifier l\'article' : 'Nouvel article' }}
                <span v-if="event" class="text-blue-600"> - {{ event.title }}</span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Titre -->
                        <div>
                            <InputLabel for="title" value="Titre de l'article" />
                            <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" required
                                :class="{ 'border-red-500': form.errors.title }" />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>

                        <!-- Extrait -->
                        <div>
                            <InputLabel for="excerpt" value="Extrait (optionnel)" />
                            <textarea id="excerpt" v-model="form.excerpt" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                placeholder="Un court résumé de votre article..."
                                :class="{ 'border-red-500': form.errors.excerpt }"></textarea>
                            <InputError :message="form.errors.excerpt" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">{{ form.excerpt?.length || 0 }}/500 caractères</p>
                        </div>

                        <!-- Image mise en avant -->
                        <div>
                            <InputLabel value="Image mise en avant (optionnelle)" />

                            <!-- Affichage de l'image actuelle -->
                            <div v-if="currentImage || form.image" class="mt-2 mb-4">
                                <img :src="imagePreview || currentImage" alt="Aperçu"
                                    class="w-full max-w-md h-48 object-cover rounded-lg" />
                                <button v-if="currentImage && !form.image" @click="removeCurrentImage" type="button"
                                    class="mt-2 text-red-600 text-sm hover:text-red-800">
                                    Supprimer l'image actuelle
                                </button>
                            </div>

                            <input ref="imageInput" type="file" accept="image/*" @change="handleImageUpload"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                :class="{ 'border-red-500': form.errors.image }" />
                            <InputError :message="form.errors.image" class="mt-2" />
                        </div>

                        <!-- Contenu -->
                        <div>
                            <InputLabel for="content" value="Contenu de l'article" />
                            <div class="mt-1">
                                <!-- Rich Text Editor ou Textarea simple -->
                                <textarea id="content" v-model="form.content" rows="15"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    required :class="{ 'border-red-500': form.errors.content }"
                                    placeholder="Rédigez votre article ici..."></textarea>
                            </div>
                            <InputError :message="form.errors.content" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                Vous pouvez utiliser du Markdown pour formater votre texte.
                            </p>
                        </div>

                        <!-- Options de publication -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Options de publication
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Statut -->
                                <div>
                                    <InputLabel for="status" value="Statut" />
                                    <select id="status" v-model="form.status"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <option value="published">Publié</option>
                                        <option value="draft">Brouillon</option>
                                    </select>
                                    <InputError :message="form.errors.status" class="mt-2" />
                                </div>

                                <!-- Date de publication -->
                                <div>
                                    <InputLabel for="publish_date" value="Date de publication" />
                                    <input id="publish_date" v-model="form.publish_date" type="datetime-local"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                        :class="{ 'border-red-500': form.errors.publish_date }" />
                                    <InputError :message="form.errors.publish_date" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-6 border-t">
                            <Link :href="getBackUrl()"
                                class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                            Annuler
                            </Link>

                            <div class="flex gap-3">
                                <!-- Sauvegarder en brouillon -->
                                <button v-if="form.status === 'published'" @click="saveDraft" type="button"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                    :disabled="form.processing">
                                    Sauvegarder en brouillon
                                </button>

                                <!-- Publier/Mettre à jour -->
                                <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                                    <span v-if="form.processing">
                                        {{ isEditing ? 'Mise à jour...' : 'Publication...' }}
                                    </span>
                                    <span v-else>
                                        {{ isEditing ? 'Mettre à jour' : (form.status === 'draft' ? 'Sauvegarder' :
                                        'Publier')
                                        }}
                                    </span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    article: {
        type: Object,
        default: null,
    },
    event: {
        type: Object,
        default: null,
    },
});

const isEditing = computed(() => !!props.article);
const imageInput = ref();
const imagePreview = ref(null);
const currentImage = ref(props.article?.featured_image?.url || null);

const form = useForm({
    title: props.article?.title || '',
    excerpt: props.article?.excerpt || '',
    content: props.article?.content || '',
    status: props.article?.status || 'published',
    publish_date: props.article?.publish_date || new Date().toISOString().slice(0, 16),
    image: null,
    event_id: props.event?.id || props.article?.event_id || null,
});

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;

        // Créer un aperçu
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeCurrentImage = () => {
    currentImage.value = null;
    form.image = null;
    imagePreview.value = null;
    if (imageInput.value) {
        imageInput.value.value = '';
    }
};

const saveDraft = () => {
    form.status = 'draft';
    submit();
};

const submit = () => {
    if (isEditing.value) {
        form.post(route('articles.update', props.article.id), {
            forceFormData: true,
            _method: 'put',
        });
    } else {
        form.post(route('articles.store'), {
            forceFormData: true,
        });
    }
};

const getBackUrl = () => {
    if (isEditing.value) {
        return route('articles.show', props.article.id);
    }
    if (props.event) {
        return route('events.articles', props.event.id);
    }
    return route('articles.index');
};

onMounted(() => {
    // Définir une date par défaut si ce n'est pas une édition
    if (!isEditing.value && !form.publish_date) {
        form.publish_date = new Date().toISOString().slice(0, 16);
    }
});
</script>