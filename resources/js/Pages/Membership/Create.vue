<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    user: Object,
    time_left: Array,
    time_since_join: Array,
})

const user = props.user ?? {}
const isSubmitting = ref(false)

const submit = () => {
    isSubmitting.value = true
    router.post(route('membership.store'), {}, {
        onFinish: () => {
            isSubmitting.value = false
        },
    })
}

const membershipStatus = computed(() => user.membership_status ?? 0)
const canJoin = computed(() => membershipStatus.value === 0 || membershipStatus.value === 2)
const buttonText = computed(() => {
    if (isSubmitting.value) return 'Adhésion en cours...'

    switch (membershipStatus.value) {
        case -1:
            return 'Compte désactivé'
        case 1:
            return 'Vous êtes déjà adhérent'
        default:
            return "J'adhère"
    }
})
</script>

<template>
    <AuthenticatedLayout>
        <div class="min-h-screen flex flex-col items-center justify-center bg-white space-y-6 text-center">
            <!-- Infos adhésion -->
            <div v-if="membershipStatus > 0" class="text-gray-800 text-lg">
                <p class="mb-2 font-medium" v-if="time_since_join?.[0] !== 0">
                    Adhérent depuis : {{ time_since_join[0] }} {{ time_since_join[1] === 'd' ? 'jours' :
                        time_since_join[1] === 'm' ? 'mois' : 'ans' }}
                </p>
                <p class="mb-2 font-medium" v-else>
                    Adhérent depuis : Aujourd'hui
                </p>
            </div>

            <!-- 🔍 DEBUG INFOS : tout afficher -->
            <div class="bg-gray-100 p-4 rounded text-left max-w-xl text-sm w-full">
                <h2 class="font-bold mb-2">[Debug] Données utilisateur</h2>
                <pre>{{ user }}</pre>

                <h2 class="font-bold mt-4 mb-2">[Debug] Statut d'adhésion</h2>
                <ul>
                    <li>membership_status : {{ membershipStatus.value }}</li>
                    <li>canJoin : {{ canJoin.value }}</li>
                    <li>isSubmitting : {{ isSubmitting }}</li>
                    <li>time_since_join : {{ time_since_join }}</li>
                    <li>time_left : {{ time_left }}</li>
                </ul>
            </div>


            <!-- Infos adhésion visibles uniquement si statut > 0 -->
            <div v-if="membershipStatus.value > 0" class="text-gray-800 text-lg">
                <p class="mb-2 font-medium" v-if="time_since_join?.[0] !== 0">
                    Adhérent depuis : {{ time_since_join[0] }} {{
                        time_since_join[1] === 'd' ? 'jours' :
                            time_since_join[1] === 'm' ? 'mois' : 'ans'
                    }}
                </p>
                <p class="mb-2 font-medium" v-else>
                    Adhérent depuis : Aujourd'hui
                </p>
            </div>
            <!-- Bouton d'adhésion -->
            <button @click="submit" :disabled="isSubmitting || !canJoin"
                class="bg-blue-600 hover:bg-blue-700 text-white text-lg font-semibold px-8 py-4 rounded-xl shadow-lg disabled:opacity-50 transition duration-200">
                {{ buttonText }}
            </button>

            <!-- Temps restant -->
            <p v-if="time_left && membershipStatus === 1">
                Temps restant : {{ time_left[0] }} {{ time_left[1] === 'd' ? 'jours' :
                    time_left[1] === 'm' ? 'mois' : 'ans' }}
            </p>
        </div>
    </AuthenticatedLayout>
</template>
