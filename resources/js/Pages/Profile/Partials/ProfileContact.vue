        <script setup>
        import { defineProps, computed } from 'vue';

        // Définition des props attendues
        const props = defineProps({
            contacts: {
                type: Array,
                required: true
            }
        });

        const sortedContacts = computed(() => {
            return [...props.contacts].sort((a, b) => a.priority - b.priority);
        });

</script>
        
<template>
    <div v-if="contacts.length > 0" class="grid gap-4">
        <div v-for="contact in sortedContacts" :key="contact.id"
            class="w-full rounded-lg bg-gray-50 p-4 shadow dark:bg-gray-900">
            <p class="text-gray-800 dark:text-gray-200">
                <strong class="text-xl capitalize dark:text-gray-100">
                    {{ contact.firstname }} {{ contact.lastname }}
                </strong>
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">({{ contact.relation }})</span>
            </p>
            <div class="m-2 text-lg">
                <p class="text-gray-600 dark:text-gray-400">N° tél : {{ contact.phone }}</p>
                <p class="text-gray-600 dark:text-gray-400">Email: {{ contact.email || 'Non renseigné' }}</p>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Priority: {{ contact.priority }}
            </p>
        </div>
    </div>

    <div v-else class="text-gray-600 dark:text-gray-400 text-sm italic">
        Aucun contact d'urgence enregistré.
    </div>
</template>


