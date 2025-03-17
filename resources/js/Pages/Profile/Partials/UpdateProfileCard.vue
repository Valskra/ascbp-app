<script setup>
import { computed } from 'vue'
import { useDateFormat } from '@vueuse/core'
import ProfileInformationDisplay from './ProfileInformationDisplay.vue'
import UpdatePhoneForm from './UpdatePhoneForm.vue'

// Définition des props en mode script setup
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
</script>

<template>
    <div class="flex flex-row items-start">
        <!-- Image de profil -->
        <img src="https://placehold.co/100" alt="Profil"
            class="rounded-full border border-gray-300 dark:border-gray-600 mr-8 min-h-[220px]" />

        <!-- Informations du profil -->
        <div class="flex flex-col space-y-2">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ props.user.firstname }} {{ props.user.lastname }}
                </h2>
            </div>

            <!-- Affichage de la date de naissance formatée -->
            <p class="text-gray-600 dark:text-gray-300 text-sm">
                Anniversaire : {{ birthDateFormatted }}
            </p>

            <!-- Section Mobile : Desktop -->
            <div class="hidden md:block">
                <UpdatePhoneForm :user="props.user" :labels="['Perso', 'Pro']" labelWidth="30%" />
            </div>
        </div>
    </div>

    <!-- Section Mobile : Mobile -->
    <div class="flex flex-col md:hidden">
        <UpdatePhoneForm :user="props.user" :labels="['Perso', 'Pro']" labelWidth="30%" />
    </div>
</template>

<style scoped></style>
