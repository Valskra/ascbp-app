<!-- components/ProfileLayout.vue -->
<script setup>
defineProps({
    editUrl: String,
    documentsUrl: String,
})

function handleDownloadPDF() {
    console.log('Téléchargement PDF...')
}
</script>

<template>
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Profil
        </h2>
        <div class="space-x-8 sm:-my-px sm:ms-10 sm:flex">
            <a :href="editUrl" class="text-sm font-medium text-blue-600 hover:underline">
                Modifier
            </a>
            <a :href="documentsUrl" class="text-sm font-medium text-blue-600 hover:underline">
                Documents
            </a>
            <button @click="handleDownloadPDF"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded">
                Télécharger PDF
            </button>
        </div>
    </div>
</template>