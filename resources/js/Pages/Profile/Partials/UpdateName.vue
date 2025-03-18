<script setup>
import { defineProps, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

// On reçoit l'utilisateur en props (avec firstname / lastname)
const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})

// On crée un formulaire Inertia pour gérer firstname / lastname
const form = useForm({
    firstname: props.user.firstname ?? '',
    lastname: props.user.lastname ?? ''
})

function autoSave() {
    form.patch(route('profile.updateName'), {
        onSuccess: () => {
            console.log('Mise à jour réussie !')
        }
    })
}
</script>

<template>
    <div class="flex items-center space-x-3">
        <!-- Bloc Prénom -->
        <div class="relative ">
            <input type="text" v-model="form.firstname" placeholder="Prénom" @blur="autoSave" class="border-x-0 border-t-0 border-gray-300 dark:border-gray-600 bg-transparent 
               focus:outline-none focus:border-blue-500 text-xl font-semibold text-gray-900 dark:text-white
               px-2 py-1 pr-6 w-28 mr-2" />

            <input type="text" v-model="form.lastname" placeholder="Nom" @blur="autoSave" class="border-x-0 border-t-0 border-gray-300 dark:border-gray-600 bg-transparent 
               focus:outline-none focus:border-blue-500 text-xl font-semibold text-gray-900 dark:text-white
               px-2 py-1 pr-6 w-28" />
        </div>
    </div>
</template>

<style scoped>
/* Ajoute tes ajustements si besoin */
</style>
