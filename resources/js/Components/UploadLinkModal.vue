<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({ open: Boolean })
const emit = defineEmits(['close'])

const step = ref(0)
const linkUrl = ref('')

const form = useForm({ name: '', duration: 1 })

watch(() => props.open, isOpen => {
    if (isOpen) {
        step.value = 0
        linkUrl.value = ''
        form.reset('name', 'duration')
    }
})

function cancel() {
    emit('close')
}

async function generate() {
    form.post(route('upload-link.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: async () => {
            try {
                const res = await fetch(route('upload-link.latest'))
                if (!res.ok) throw new Error('Aucun lien trouvé')
                linkUrl.value = await res.text()
                step.value = 1
            } catch (e) {
                console.error(e)
            }
        }
    })
}

function copyLink() {
    navigator.clipboard.writeText(linkUrl.value)
}
</script>


<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md mx-4 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ step === 0 ? 'Générer un lien d’envoi' : 'Lien généré' }}
                    </h3>
                </div>

                <!-- Body -->
                <div class="px-6 py-4">
                    <!-- Étape 0 : formulaire -->
                    <div v-if="step === 0" class="space-y-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nom (optionnel)
                        </label>
                        <input v-model="form.name" type="text" placeholder="Ex : Certificat reçu"
                            class="mt-1 block w-full border rounded p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                        <p v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</p>

                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Durée de validité
                        </label>
                        <select v-model="form.duration"
                            class="mt-1 block w-full border rounded p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option :value="1">1 jour</option>
                            <option :value="7">1 semaine</option>
                            <option :value="21">3 semaines</option>
                        </select>
                        <p v-if="form.errors.duration" class="text-red-500 text-sm">{{ form.errors.duration }}</p>
                    </div>

                    <!-- Étape 1 : lien généré -->
                    <div v-else class="space-y-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">Lien généré :</p>
                        <div class="flex items-center gap-2">
                            <input :value="linkUrl" readonly
                                class="flex-1 border rounded p-2 bg-gray-100 dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200" />
                            <button @click="copyLink"
                                class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Copier
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t dark:border-gray-700 flex justify-end gap-2">
                    <button @click="cancel"
                        class="px-4 py-2 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        {{ step === 0 ? 'Annuler' : 'Fermer' }}
                    </button>
                    <button v-if="step === 0" @click="generate" :disabled="form.processing"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                        {{ form.processing ? 'Génération…' : 'Générer' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
