<script setup>
import ProfileInformationDisplay from "./ProfileInformationDisplay.vue";
import { computed } from 'vue';
import { useDateFormat } from '@vueuse/core';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const birthDateFormatted = computed(() => {
    return props.user.birth_date
        ? useDateFormat(props.user.birth_date, 'DD/MM/YYYY').value
        : "__/__/____";
});

</script>

<style scoped></style>

<template>
    <div class="flex flex-row items-start ">
        <!-- Image de profil -->
        <img v-if="user.profile_picture?.url" :src="user.profile_picture.url" alt="Photo de profil"
            class="rounded-full border border-gray-300 dark:border-gray-600 mr-8 h-[220px] w-[220px]" />

        <img v-else src="https://placehold.co/100" alt="Profil"
            class="rounded-full border border-gray-300 dark:border-gray-600 mr-8 h-[220px] w-[220px]" />

        <!-- Informations du profil -->
        <div class="flex flex-col space-y-2">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white"> {{ user.firstname }} {{
                    user.lastname }}
                </h2>
            </div>
            <p class="text-gray-600 dark:text-gray-300 text-sm"> Anniversaire : {{ birthDateFormatted || '__/__/____' }}
            </p>

            <!-- Section Mobile : Desktop -->
            <div class="hidden md:block">
                <ProfileInformationDisplay title="Mobile" v-if="user.phone_secondary" :labels="['Perso', 'Pro']"
                    labelWidth="30%" :data="[user.phone, user.phone_secondary]" />

                <ProfileInformationDisplay title="Mobile" v-else :labels="['Perso']" labelWidth="30%"
                    :data="[user.phone]" />
            </div>

        </div>
    </div>
    <!-- Section Mobile : Mobile -->
    <div class="flex flex-col md:hidden">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white md:hidden">Mobile</h2>
        <ProfileInformationDisplay title="" v-if="user.phone_secondary" :labels="['Perso', 'Pro']" labelWidth="25%"
            :data="[user.phone, user.phone_secondary]" />
        <ProfileInformationDisplay title="" v-else :labels="['Perso']" labelWidth="20%" :data="[user.phone]" />
    </div>
</template>
