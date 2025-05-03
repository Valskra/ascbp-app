<!-- resources/js/Components/ui/ConfirmModal.vue -->
<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
    /** Texte principal */
    title: { type: String, default: 'Confirmer' },
    /** Texte plus descriptif (facultatif) */
    message: { type: String, default: '' },
    /** Libellé du bouton “OK” */
    confirmLbl: { type: String, default: 'Oui, supprimer' },
    /** Libellé du bouton “Annuler” */
    cancelLbl: { type: String, default: 'Annuler' },
    /** Couleur (classes Tailwind) du bouton “OK” */
    confirmCls: { type: String, default: 'bg-red-600 hover:bg-red-700' },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
    <!-- TELEPORT = rendu dans <body> afin d’être au-dessus de tout -->
    <teleport to="body">
        <!-- overlay sombre -->
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 backdrop-blur-sm"
            @click.self="emit('cancel')">
            <!-- boîte -->
            <div class="w-[90vw] max-w-sm sm:max-w-md rounded-lg bg-white dark:bg-gray-800 shadow-xl p-6
               animate-[fadeIn_.25s_ease-out]">
                <h2 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                    {{ title }}
                </h2>

                <p v-if="message" class="text-sm mb-4 text-gray-600 dark:text-gray-300">
                    {{ message }}
                </p>

                <!-- boutons -->
                <div class="flex justify-end gap-3">
                    <button class="px-3 py-2 rounded bg-gray-200 dark:bg-gray-700 text-sm
                   hover:bg-gray-300 dark:hover:bg-gray-600 transition" @click="emit('cancel')">
                        {{ cancelLbl }}
                    </button>

                    <button :class="['px-3 py-2 rounded text-sm text-white transition', confirmCls]"
                        @click="emit('confirm')">
                        {{ confirmLbl }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
