            
<script setup>
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
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
</script>

<template>
    <AuthenticatedLayout>
        <div class="min-h-screen flex flex-col items-center justify-center bg-white space-y-6 text-center">
            <div v-if="user?.has_membership" class="text-gray-800 text-lg">
                <p class="mb-2 font-medium" v-if="time_since_join[0] != 0">
                    Adhérent depuis : {{ time_since_join[0] }} {{ time_since_join[1] === 'd' ? 'jours' :
                        time_since_join[1] === 'm' ? 'mois' : 'ans' }}
                </p>
                <p class="mb-2 font-medium" v-else>
                    Adhérent depuis : Aujourd'hui
                </p>
            </div>

            <button @click="submit" :disabled="isSubmitting || user?.has_membership"
                class="bg-blue-600 hover:bg-blue-700 text-white text-lg font-semibold px-8 py-4 rounded-xl shadow-lg disabled:opacity-50 transition duration-200">
                {{
                    isSubmitting
                        ? 'Adhésion en cours...'
                        : user?.has_membership
                            ? 'Vous êtes déjà adhérent'
                            : "J'adhère"
                }}
            </button>

            <p v-if="time_left">
                Temps restant : {{ time_left[0] }} {{ time_left[1] === 'd' ? 'jours' :
                    time_left[1] === 'm' ? 'mois' : 'ans' }}
            </p>

        </div>
    </AuthenticatedLayout>
</template>
