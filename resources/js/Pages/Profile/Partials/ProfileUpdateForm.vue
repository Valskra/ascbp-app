<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ title }}
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Mettez à jour vos informations {{ title.toLowerCase() }}.
            </p>
        </header>

        <form @submit.prevent="updateProfile" class="mt-6 space-y-6">
            <div v-for="(label, index) in labels" :key="index">
                <InputLabel :for="fields[index]" :value="label" />
                <TextInput :id="fields[index]" type="text" class="mt-1 block w-full" v-model="form[fields[index]]"
                    required />
                <InputError class="mt-2" :message="form.errors[fields[index]]" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">
                        Enregistré.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    fields: {
        type: Array,
        required: true
    },
    labels: {
        type: Array,
        required: true
    },
    values: {
        type: Array,
        required: true
    }
});

const form = useForm(
    props.fields.reduce((acc, field, index) => {
        acc[field] = props.values[index] || '';
        return acc;
    }, {})
);

const updateProfile = () => {
    form.patch(route('profile.update'));
};
</script>