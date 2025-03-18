<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

import { useDateFormat } from '@vueuse/core'
import UpdatePhoneForm from './UpdatePhoneForm.vue'
import UpdateName from './UpdateName.vue';

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
