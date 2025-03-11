<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    user: {
        Object
    },
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    labelWidth: {
        type: String,
        default: '30%'
    },
});

const form = useForm({
    email: props.user.email,
    email_pro: props.user.email_pro ?? '',
});
</script>

<style scoped>
.label {
    width: v-bind(labelWidth);
}

.value {
    width: calc(100% - v-bind(labelWidth));
}
</style>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Modifier les Emails
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Modifiez vos adresses email personnelle et professionnelle.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.updateEmail'))" class="mt-6 space-y-3">

            <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600">
                <table class="w-full border-collapse">
                    <tbody>
                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <td
                                class="px-4 py-3 font-semibold bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white label">
                                Email Perso.
                            </td>
                            <td class="text-gray-700 dark:text-gray-300 value">
                                <input id="email" type="email" v-model="form.email" required
                                    class="w-full border-0 bg-transparent py-2 px-3 text-gray-700 dark:text-gray-200 focus:ring-0" />
                            </td>
                            <InputError class="mt-2" :message="form.errors.email" />
                        </tr>

                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <td
                                class="px-4 py-3 font-semibold bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white label">
                                Email Pro.
                            </td>
                            <td class="text-gray-700 dark:text-gray-300 value">
                                <input id="email_pro" type="email" v-model="form.email_pro"
                                    class="w-full border-0 bg-transparent py-2 px-3 text-gray-700 dark:text-gray-200 focus:ring-0" />
                            </td>
                            <InputError class="mt-2" :message="form.errors.email_pro" />
                        </tr>
                    </tbody>
                </table>
                <div v-if="mustVerifyEmail && user.email_verified_at === null">
                    <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">
                        Votre email n'est pas vérifié.
                        <Link :href="route('verification.send')" method="post" as="button"
                            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800">
                        Cliquez ici pour renvoyer l'e-mail de vérification.
                        </Link>
                    </p>

                    <div v-show="status === 'verification-link-sent'"
                        class="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                        Un nouveau lien de vérification a été envoyé à votre adresse
                        e-mail.
                    </div>
                </div>
            </div>


            <div class="flex justify-end">
                <PrimaryButton @click="form.patch(route('profile.updateEmail'))" :disabled="form.processing"
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
