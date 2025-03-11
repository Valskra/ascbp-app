<template>
    <div class="flex flex-row items-start ">
        <!-- Image de profil -->
        <img src="https://placehold.co/100" alt="Profil"
            class="rounded-full border border-gray-300 dark:border-gray-600 mr-8 min-h-[220px]" />

        <!-- Informations du profil -->
        <div class="flex flex-col space-y-2">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ name }}</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm">{{ status }}</p>
            </div>
            <p class="text-gray-600 dark:text-gray-300 text-sm">Anniversaire {{ birthday || '__/__/____' }}</p>

            <!-- Section Mobile : Desktop -->
            <div class="hidden md:block">
                <ProfileInformationDisplay title="Mobile" v-if="mobileNumbers[1]" :labels="['Perso', 'Pro']"
                    labelWidth="30%" :data="mobileNumbers" />

                <ProfileInformationDisplay title="Mobile" v-else :labels="['Perso']" labelWidth="30%"
                    :data="mobileNumbers" />
            </div>

        </div>
    </div>
    <!-- Section Mobile : Mobile -->
    <div class="flex flex-col md:hidden">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white md:hidden">Mobile</h2>
        <ProfileInformationDisplay v-if="mobileNumbers[1]" :labels="['Perso', 'Pro']" labelWidth="30%"
            :data="mobileNumbers" />
        <ProfileInformationDisplay v-else :labels="['Perso']" labelWidth="30%" :data="mobileNumbers" />
    </div>
</template>

<script>
import ProfileInformationDisplay from "./ProfileInformationDisplay.vue";

export default {
    components: {
        ProfileInformationDisplay
    },
    props: {
        name: {
            type: String,
            required: true,
            default: "Prénom NOM"

        },
        status: {
            type: String,
            required: true
        },
        birthday: {
            type: String,
            default: "__/__/____"
        },
        mobileNumbers: {
            type: Array,
            required: true
        }
    }
};
</script>

<style scoped></style>