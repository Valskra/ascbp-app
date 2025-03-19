<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { useDateFormat } from '@vueuse/core'
import UpdatePhoneForm from './UpdatePhoneForm.vue'
import UpdateName from './UpdateName.vue'

const props = defineProps({
    user: {
        type: Object,
        required: true
    }

})
// Calcul de la date de naissance formatée
const birthDateFormatted = computed(() => {
    if (!props.user.birth_date) {
        return '__/__/____'
    }
    // useDateFormat renvoie un Ref, on ajoute .value
    return useDateFormat(props.user.birth_date, 'DD/MM/YYYY').value
})

const page = usePage()

// État pour ouvrir/fermer la fenêtre de dépôt
const showModal = ref(false)

// Form Inertia pour l'envoi du fichier
const form = useForm({
    photo: null, // correspond à l'input file
})

// Ouvrir le modal
function openModal() {
    showModal.value = true
}

// Fermer le modal
function closeModal() {
    showModal.value = false
}

// Gérer la sélection du fichier
function handleFileChange(e) {
    const file = e.target.files[0]
    if (!file) return
    form.photo = file
}

// Soumission du formulaire
function submitForm() {
    form.submit('put', route('profile.updatePhoto'), {
        onSuccess: () => {
            // On ferme le modal si tout est OK
            closeModal()
        },
    })
}
</script>

<template>
    <div class="flex flex-row items-start">
        <!-- Image de profil -->
        <img v-if="user.profile_photo_url" :src="user.profile_photo_url" alt="Photo de profil"
            class="rounded-full border border-gray-300 dark:border-gray-600 mr-8 min-h-[220px]" @click="openModal" />

        <img v-else src="https://placehold.co/100" alt="Profil"
            class="rounded-full border border-gray-300 dark:border-gray-600 mr-8 min-h-[220px]" @click="openModal" />

        <!-- Modal (s'affiche quand showModal = true) -->
        <div v-if="showModal" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
            <div class="bg-white rounded p-6 w-full max-w-md">
                <h3 class="text-xl font-semibold mb-4">Changer ma photo</h3>

                <!-- Formulaire d'upload -->
                <form @submit.prevent="submitForm" class="space-y-4">
                    <input type="file" @change="handleFileChange" accept="image/*" />

                    <div class="flex justify-end space-x-2">
                        <button type="button" class="px-4 py-2 bg-gray-300 rounded" @click="closeModal">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">
                            Enregistrer
                        </button>
                    </div>
                </form>
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

<style scoped></style>
