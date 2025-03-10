<template>
    <div class="border-40 flex items-center border-b" :class="elementSize">
        <slot>
            <div class="text-80 md:w-1/4 @sm/peekable:w-1/4 @md/modal:w-1/4 md:py-3 @sm/peekable:py-3 @md/modal:py-3">
                {{ field.indexName }}
            </div>
        </slot>

        <slot name="value">
            <div class="nml-field-form break-all md:w-3/4 @sm/peekable:w-3/4 @md/modal:w-3/4 md:py-3 @sm/peekable:py-3 md/modal:py-3 lg:break-words @md/peekable:break-words @lg/modal:break-words">
                <template v-if="!field.value">—</template>

                <span class="dim text-primary cursor-pointer font-bold" v-else-if="isHidden" @click="isHidden = false">{{
                    __('Show')
                }}</span>

                <template v-else-if="!field.nmlJsCallback && !field.nmlTrix">
                    <nmlArray v-if="field.nmlArray" :field="field" />
                    <nmlFile v-else :field="field" />
                </template>
            </div>
        </slot>
    </div>
</template>

<script src="./script.js"></script>
