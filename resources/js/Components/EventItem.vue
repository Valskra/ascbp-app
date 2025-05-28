<template>
    <div
        class="flex items-center gap-4 bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
        <img :src="event.imageUrl" alt="Image de l'événement" class="w-24 h-24 object-cover rounded-lg" />
        <div class="flex flex-col flex-grow">
            <h3 class="font-semibold text-lg text-gray-800">{{ event.title }}</h3>
            <p class="text-sm text-gray-500">{{ event.description }}</p>
        </div>
        <button v-if="canEdit" @click="$emit('edit', event.id)"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium">
            Modifier
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
});

const user = usePage().props.auth.user

const canEdit = computed(() => {
    return user.isAdmin || user.id === props.event.organizerId;
});
</script>
