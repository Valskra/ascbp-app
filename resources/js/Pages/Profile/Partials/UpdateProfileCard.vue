<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { useDateFormat } from '@vueuse/core'
import { Cropper } from "vue-advanced-cropper"
import "vue-advanced-cropper/dist/style.css"
import UpdatePhoneForm from './UpdatePhoneForm.vue'
import UpdateName from './UpdateName.vue'

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})

const page = usePage()
const showModal = ref(false)          // Modal pour choisir un fichier
const showCropModal = ref(false)      // Modal pour recadrer
const imagePreviewUrl = ref('')       // URL temporaire de l'image
const cropperRef = ref(null)          // Référence du cropper

const form = useForm({
    photo: null  // Stocke l'image recadrée avant envoi
})

const birthDateFormatted = computed(() => {
    if (!props.user.birth_date) {
        return '__/__/____'
    }
    return useDateFormat(props.user.birth_date, 'DD/MM/YYYY').value
})

// Fonction pour ouvrir la sélection de fichier
function openModal() {
    showModal.value = true
}

// Fermer le modal de sélection
function closeModal() {
    showModal.value = false
}

// Quand l'utilisateur sélectionne une image
function handleFileChange(e) {
    const file = e.target.files[0]
    if (!file) return

    // Générer un aperçu local de l'image
    imagePreviewUrl.value = URL.createObjectURL(file)

    // Fermer le modal de sélection et ouvrir celui de recadrage
    showModal.value = false
    showCropModal.value = true
}

// Fonction pour valider et recadrer l’image
function validateCrop() {
    if (!cropperRef.value) return

    const { canvas } = cropperRef.value.getResult()

    // Convertir le canvas en fichier Blob
    canvas.toBlob((blob) => {
        if (!blob) return

        // Convertir le Blob en fichier
        const croppedFile = new File([blob], 'cropped.png', { type: blob.type })

        // Stocker l'image recadrée dans le formulaire
        form.photo = croppedFile

        // Nettoyer l'URL temporaire
        URL.revokeObjectURL(imagePreviewUrl.value)
        imagePreviewUrl.value = ''

        // Fermer le modal de recadrage et envoyer l'image
        showCropModal.value = false
        submitForm()
    }, 'image/png', 1) // Qualité 100%
}

// Fonction pour envoyer l'image au serveur
function submitForm() {
    form.post(route('files.store.user.profile-picture'), {
        preserveScroll: true,
        onSuccess: (response) => {
            if (response.props.user.profile_picture) {
                // Récupérer la nouvelle URL et l'affecter à l'objet user
                props.user.profile_picture.url = response.props.user.profile_picture.url + '?t=' + new Date().getTime();
            }
            // Fermer le modal
            showCropModal.value = false;
        }
    });
}

</script>

<template>
    <div class="flex flex-row items-start">
        <div class="relative rounded-full border border-gray-300 dark:border-gray-600 mr-8 h-[220px] w-[220px] overflow-hidden cursor-pointer"
            @click="openModal">

            <img v-if="user.profile_picture?.url" :src="user.profile_picture.url" alt="Photo de profil"
                class="h-full w-full object-cover" />

            <img v-else src="https://placehold.co/100" alt="Profil" class="h-full w-full object-cover" />

            <!-- Overlay apparaissant au hover -->
            <div
                class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                <span class="text-white font-semibold">Modifier l'image</span>
                <!-- ou icône crayon -->
                <!-- <i class="fas fa-pencil-alt text-white text-xl"></i> -->
            </div>
        </div>

        <!-- Modal pour choisir le fichier (showModal) -->
        <div v-if="showModal" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
            <div class="bg-white rounded p-6 w-full max-w-md">
                <h3 class="text-xl font-semibold mb-4">Choisir une photo</h3>

                <form class="space-y-4">
                    <input type="file" accept="image/*" @change="handleFileChange" />

                    <div class="flex justify-end space-x-2">
                        <button type="button" class="px-4 py-2 bg-gray-300 rounded" @click="closeModal">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal pour le recadrage (showCropModal) -->
        <div v-if="showCropModal" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
            <div class="bg-white rounded p-4 w-full max-w-lg">
                <h3 class="text-xl font-semibold mb-4">Recadrer l'image</h3>

                <!-- Zone du recadrage -->
                <div class="mb-4 max-h-[400px] overflow-auto">
                    <Cropper ref="cropperRef" class="max-w-full max-h-[400px] block" :src="imagePreviewUrl"
                        :stencil-props="{ aspectRatio: 1 }" />
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" class="px-4 py-2 bg-gray-300 rounded" @click="showCropModal = false">
                        Annuler
                    </button>
                    <button type="button" class="px-4 py-2 bg-blue-500 text-white rounded" @click="validateCrop">
                        Valider le recadrage
                    </button>
                </div>
            </div>
        </div>

        <!-- Informations du profil -->
        <div class="flex flex-col space-y-2">
            <UpdateName :user="user" class="mt-2 mb-4" />

            <!-- Section Mobile : Desktop -->
            <div class="hidden md:block">
                <UpdatePhoneForm :user="props.user" :labels="['Perso', 'Pro']" labelWidth="30%" />
            </div>
        </div>
    </div>

    <!-- Section Mobile : Mobile -->
    <div class="flex flex-col md:hidden">
        <UpdatePhoneForm :user="props.user" :labels="['Perso', 'Pro']" labelWidth="25%" />
    </div>
</template>
