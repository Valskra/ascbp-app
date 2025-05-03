<script setup>
/**
 * props.errors = objet retourné par Laravel / Inertia :
 * {
 *   title: ['Ce titre existe déjà…'],
 *   file : ['Le fichier doit être un PDF…']
 * }
 */
const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <div v-if="Object.keys(errors).length"
        class="rounded border border-red-300 bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-200">
        <ul class="list-disc list-inside space-y-1">
            <li v-for="(messages, field) in errors" :key="field">
                {{ messages }}
            </li>
        </ul>
    </div>
</template>
