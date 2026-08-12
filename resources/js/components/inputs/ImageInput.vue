<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ImagePlus, Trash, Upload } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import BaseImage from '@/components/images/BaseImage.vue';
import { Button } from '@/components/ui/button';
import { useLang } from '@/composables/useLang.js';
import { cn } from '@/lib/utils';

interface Props {
    modelValue: File | null;
    label?: string;
    error?: string;
    id?: string;
    disabled?: boolean;
    required?: boolean;
    allowedTypes?: string[];
    maxSizeMB?: number;
}

const props = withDefaults(defineProps<Props>(), {
    label: '',
    error: '',
    id: undefined,
    disabled: false,
    required: false,

    allowedTypes: () => ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'],

    maxSizeMB: 5,
});

const emit = defineEmits<{
    'update:modelValue': [value: File | null];
}>();

const { t } = useLang();

const page = usePage();

const isRTL = computed(() => page.props.locale === 'ar');

/*
|--------------------------------------------------------------------------
| Refs
|--------------------------------------------------------------------------
*/

const fileInput = ref<HTMLInputElement | null>(null);

const previewUrl = ref<string | null>(null);

const localError = ref<string | null>(null);

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const displayError = computed(() => localError.value || props.error || null);

const acceptedTypes = computed(() =>
    props.allowedTypes
        .map((type) => {
            const extension = type.split('/')[1];

            return extension === 'jpeg' ? 'JPG' : extension.toUpperCase();
        })
        .join(', '),
);

/*
|--------------------------------------------------------------------------
| Preview
|--------------------------------------------------------------------------
*/

const createPreview = (file: File | null) => {
    if (!file) {
        previewUrl.value = null;

        return;
    }

    const reader = new FileReader();

    reader.onload = (event) => {
        previewUrl.value = (event.target?.result as string) ?? null;
    };

    reader.readAsDataURL(file);
};

watch(
    () => props.modelValue,
    (file) => {
        createPreview(file);
    },
    {
        immediate: true,
    },
);

/*
|--------------------------------------------------------------------------
| File Input
|--------------------------------------------------------------------------
*/

const openFilePicker = () => {
    if (props.disabled) {
        return;
    }

    fileInput.value?.click();
};

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

const validateFile = (file: File): boolean => {
    /*
    |--------------------------------------------------------------------------
    | Type
    |--------------------------------------------------------------------------
    */

    if (
        props.allowedTypes.length > 0 &&
        !props.allowedTypes.includes(file.type)
    ) {
        localError.value =
            `${t('app.not_allowed_file_type_warning').value}: ` +
            `${acceptedTypes.value}`;

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Size
    |--------------------------------------------------------------------------
    */

    const maxBytes = props.maxSizeMB * 1024 * 1024;

    if (file.size > maxBytes) {
        localError.value =
            `${t('app.exceeded_file_size').value}: ` + `${props.maxSizeMB}MB`;

        return false;
    }

    return true;
};

/*
|--------------------------------------------------------------------------
| Change
|--------------------------------------------------------------------------
*/

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;

    const file = input.files?.[0] ?? null;

    if (!file) {
        return;
    }

    localError.value = null;

    if (!validateFile(file)) {
        emit('update:modelValue', null);

        /*
         * Reset the native input so the user can select
         * the same file again after a validation failure.
         */
        input.value = '';

        return;
    }

    emit('update:modelValue', file);
};

/*
|--------------------------------------------------------------------------
| Remove
|--------------------------------------------------------------------------
*/

const removeImage = () => {
    emit('update:modelValue', null);

    localError.value = null;

    /*
     * Reset the native input so the same file can be
     * selected again.
     */
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

/*
|--------------------------------------------------------------------------
| Accessibility
|--------------------------------------------------------------------------
*/

const inputId = computed(
    () => props.id ?? `image-input-${Math.random().toString(36).slice(2, 9)}`,
);
</script>

<template>
    <div class="space-y-2" :dir="isRTL ? 'rtl' : 'ltr'">
        <!-- Label -->
        <label v-if="label" :for="inputId" class="block text-sm font-medium">
            {{ label }}

            <span v-if="required" class="text-destructive"> * </span>
        </label>

        <!-- Upload / Preview -->
        <div
            class="relative flex min-h-40 w-full items-center justify-center overflow-hidden rounded-lg border-2 border-dashed p-6 text-center transition-colors"
            :class="
                cn(
                    displayError ? 'border-destructive' : 'border-border',

                    disabled
                        ? 'cursor-not-allowed opacity-50'
                        : 'cursor-pointer hover:border-primary hover:bg-muted/50',
                )
            "
            role="button"
            tabindex="0"
            :aria-disabled="disabled"
            :aria-label="
                previewUrl
                    ? t('app.change_image', 'Change image')
                    : t('app.click_to_upload_image', 'Click to upload image')
            "
            @click="openFilePicker"
            @keydown.enter.prevent="openFilePicker"
            @keydown.space.prevent="openFilePicker"
        >
            <!-- ========================================================= -->
            <!-- Preview -->
            <!-- ========================================================= -->

            <div v-if="previewUrl" class="relative">
                <BaseImage
                    :src="previewUrl"
                    :alt="label || t('app.image_preview', 'Image preview')"
                    class="h-32 w-32 rounded-lg border object-cover"
                />

                <!-- Remove -->
                <Button
                    type="button"
                    variant="destructive"
                    size="icon"
                    class="absolute -top-2 -right-2 size-7 rounded-full shadow-sm"
                    :disabled="disabled"
                    @click.stop="removeImage"
                >
                    <Trash class="size-3.5" />

                    <span class="sr-only">
                        {{ t('app.remove', 'Remove') }}
                    </span>
                </Button>
            </div>

            <!-- ========================================================= -->
            <!-- Empty State -->
            <!-- ========================================================= -->

            <div v-else class="flex flex-col items-center justify-center">
                <!-- Icon -->
                <div
                    class="mb-3 flex size-12 items-center justify-center rounded-full bg-muted"
                >
                    <ImagePlus class="size-6 text-muted-foreground" />
                </div>

                <!-- Main text -->
                <p class="text-sm font-medium">
                    {{
                        t('app.click_to_upload_image', 'Click to upload image')
                    }}
                </p>

                <!-- Upload hint -->
                <p
                    class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                >
                    <Upload class="size-3" />

                    {{ t('app.upload_image', 'Upload an image') }}
                </p>

                <!-- Accepted types -->
                <p class="mt-3 text-xs text-muted-foreground">
                    {{ t('app.accepted_types') }}:
                    {{ acceptedTypes }}
                </p>

                <!-- Max size -->
                <p class="text-xs text-muted-foreground">
                    {{ t('app.file_max_size') }}
                    {{ maxSizeMB }}MB
                </p>
            </div>

            <!-- Native input -->
            <input
                :id="inputId"
                ref="fileInput"
                type="file"
                class="sr-only"
                :accept="allowedTypes.join(',')"
                :disabled="disabled"
                @change="handleFileChange"
                @click.stop
            />
        </div>

        <!-- Error -->
        <p
            v-if="displayError"
            class="text-sm text-destructive"
            :class="isRTL ? 'text-right' : 'text-left'"
            role="alert"
        >
            {{ displayError }}
        </p>
    </div>
</template>
