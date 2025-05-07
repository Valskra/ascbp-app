<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { defineProps } from 'vue'

const props = defineProps({
    files: {
        type: Array,
        default: () => [],
    }
})

const form = useForm({
    file: null,
})

function handleFileChange(e) {
    form.file = e.target.files[0]
}

function submitForm() {
    form.post(route('file-test.store'))
}

function isImage(ext) {
    return ['jpg', 'jpeg', 'png', 'gif'].includes(ext.toLowerCase())
}
</script>

<template>
    <div>
        <h1>Test Upload</h1>

        <!-- Formulaire d'upload -->
        <form @submit.prevent="submitForm">
            <input type="file" @change="handleFileChange" />
            <button type="submit">Uploader</button>
        </form>

        <p v-if="$page.props.flash?.success" style="color: green">
            {{ $page.props.flash.success }}
        </p>

        <!-- Liste des fichiers en base -->
        <h2>Fichiers en base :</h2>
        <ul>
            <li v-for="(file, index) in files" :key="index">
                {{ file.name }}.{{ file.extension }}
                ({{ file.mimetype }} - {{ file.size }} octets)
                <br>
                <em>Path:</em> {{ file.path }}
                <br>
                <!-- Aperçu si c’est une image -->
                <img v-if="isImage(file.extension)" :src="`/storage/${file.path}`" alt="apercu"
                    style="max-width: 200px;" />
                <hr>
            </li>
        </ul>
    </div>
</template>
