<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Modifier les Numéros de mobile
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Modifiez vos Numéros de téléphone
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.updatePhone'))" class="mt-6 space-y-3">

            <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600">
                <table class="w-full border-collapse">
                    <tbody>
                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <td
                                class="px-4 py-3 font-semibold bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white label">
                                Numéro Perso.
                            </td>
                            <td class="text-gray-700 dark:text-gray-300 value">
                                <input id="phone" type="phone" v-model="form.phone"
                                    class="w-full border-0 bg-transparent py-2 px-3 text-gray-700 dark:text-gray-200 focus:ring-0" />
                            </td>
                            <InputError class="mt-2" :message="form.errors.phone" />
                        </tr>

                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <td
                                class="px-4 py-3 font-semibold bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white label">
                                Numéro Pro.
                            </td>
                            <td class="text-gray-700 dark:text-gray-300 value">
                                <input id="phone_pro" type="phone" v-model="form.phone_pro"
                                    class="w-full border-0 bg-transparent py-2 px-3 text-gray-700 dark:text-gray-200 focus:ring-0" />
                            </td>
                            <InputError class="mt-2" :message="form.errors.phone_pro" />
                        </tr>
                    </tbody>
                </table>
            </div>


            <div class="flex justify-end">
                <PrimaryButton @click="form.patch(route('profile.updateMobile'))" :disabled="form.processing"
                    v-if="!form.recentlySuccessful">
                    Enregistrer
                </PrimaryButton>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="ml-4 text-sm text-gray-600 dark:text-gray-400">
                        Enregistré.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ user: Object });

const form = useForm({
    phone: props.user.phone,
    phone_pro: props.user.phone_pro ?? '',
});
</script>
