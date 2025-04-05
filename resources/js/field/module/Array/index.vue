<template>
    <div>
        <draggable
            :class="'flex flex-wrap space-x-3 nml-display-' + type"
            v-if="array && array.length"
            item-key="id"
            v-model="array"
            @end="changeArray(array)"
        >
            <template #item="{ element }">
                <div class="relative w-32">
                    <div class="title truncate text-center" v-text="element.title || element.name" />
                    <img class="h-40 w-32" :src="element.url" />
                    <!-- <button v-if="isForm" @click="remove(i)">Remove</button> -->
                </div>
            </template>
        </draggable>

        <div
            class="card border-lg border-50 max-w-xs max-w-xs cursor-pointer border p-8 text-center"
            v-else-if="isForm"
            @click="popup = true"
        >
            {{ __('Select Files') }}
        </div>

        <div style="margin-top: 2rem;" class="mt-4" v-if="isForm && array && array.length">
            <a @click="popup = true" class="shadow relative bg-primary-500 hover:bg-primary-400 text-white dark:text-gray-900 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3 shadow relative bg-primary-500 hover:bg-primary-400 text-white dark:text-gray-900">
                {{ __('Media Library') }}
            </a>

            <a style="margin-left: 1rem;" class="shadow relative bg-white hover:bg-white text-red-500 border-1 border-red-500 hover:text-red-700 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3" @click="changeArray([])">
                {{ __('Clear') }}
            </a>
        </div>

        <transition name="fade" mode="out-in">
            <Library v-if="popup" isArray :field="field" />
        </transition>
    </div>
</template>

<script src="./script.js"></script>
