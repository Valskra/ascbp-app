<script setup>
import { computed } from 'vue';

const props = defineProps({
    /** { id, title, extension, url, … } */
    certificate: { type: Object, required: true },
});

/* longueur max du titre avant coupure */
const MAX = 30;

const label = computed(() => {
    const { title = '', extension = '' } = props.certificate;

    if (title.length <= MAX) {
        return `${title}.${extension}`;
    }

    const head = Math.ceil(MAX / 2);
    const tail = MAX - head;

    return `${title.slice(0, head)}...${title.slice(-tail)}.${extension}`;
});
</script>

<template>
    <a :href="certificate.url" target="_blank" class="block w-full rounded border border-gray-300 dark:border-gray-600
           bg-white dark:bg-gray-800 px-4 py-3 text-sm text-gray-800 dark:text-gray-200
           transition duration-150 ease-in-out
           hover:bg-gray-100 hover:dark:bg-gray-700 hover:underline">
        {{ label }}
    </a>
</template>
