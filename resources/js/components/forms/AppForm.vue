<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';

import Button from '@/components/ui/button/Button.vue';

interface Props {
    processing?: boolean;
    submitText?: string;
    processingText?: string;
    cancelText?: string;

    showActions?: boolean;
    showCancel?: boolean;

    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    processing: false,
    submitText: 'Save',
    processingText: 'Saving...',
    cancelText: 'Cancel',

    showActions: true,
    showCancel: true,

    disabled: false,
});

const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

const formElement = ref<HTMLFormElement | null>(null);

const isFormValid = ref(false);

const updateValidity = () => {
    isFormValid.value = formElement.value?.checkValidity() ?? false;
};

const submitDisabled = computed(() => {
    return props.processing || props.disabled || !isFormValid.value;
});

const handleSubmit = () => {
    updateValidity();

    if (!formElement.value?.checkValidity()) {
        formElement.value?.reportValidity();

        return;
    }

    if (props.processing || props.disabled) {
        return;
    }

    emit('submit');
};

const handleCancel = () => {
    if (props.processing) {
        return;
    }

    emit('cancel');
};

const initializeValidity = async () => {
    await nextTick();

    updateValidity();
};

initializeValidity();
</script>

<template>
    <form
        ref="formElement"
        class="space-y-6"
        @submit.prevent="handleSubmit"
        @input="updateValidity"
        @change="updateValidity"
    >
        <div class="space-y-5">
            <slot />
        </div>

        <div v-if="showActions" class="flex items-center justify-end gap-2">
            <slot name="actions">
                <Button
                    v-if="showCancel"
                    type="button"
                    variant="outline"
                    :disabled="processing"
                    @click="handleCancel"
                >
                    {{ cancelText }}
                </Button>

                <Button type="submit" :disabled="submitDisabled">
                    {{ processing ? processingText : submitText }}
                </Button>
            </slot>
        </div>
    </form>
</template>
