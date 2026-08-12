<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Label } from '@/components/ui/label';
import { useLang } from '@/composables/useLang';
import { cn } from '@/lib/utils';
import { Textarea } from '@/components/ui/textarea';

interface Props {
    modelValue: string | null;
    label?: string;
    placeholder?: string;
    error?: string;
    id?: string;
    disabled?: boolean;
    required?: boolean;
    rows?: number;
    maxChars?: number;
    maxWords?: number;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    required: false,
    rows: 4,
    maxChars: undefined,
    maxWords: undefined,
});

const emit = defineEmits(['update:modelValue']);
const { t } = useLang();
const textareaId = computed(
    () => props.id ?? `textarea-${Math.random().toString(36).slice(2)}`,
);
const hasError = computed(() => !!props.error);

const page = usePage();
const isRTL = computed(() => page.props.locale === 'ar');

// Local value for v-model
const internalValue = ref(props.modelValue ?? '');

// Sync internal value with parent
watch(internalValue, (val) => emit('update:modelValue', val));
watch(
    () => props.modelValue,
    (val) => (internalValue.value = val ?? ''),
);

// Counters
const words = computed(() =>
    internalValue.value.trim().split(/\s+/).filter(Boolean),
);
const wordCount = computed(() => words.value.length);
const charCount = computed(() => internalValue.value.length);

// Enforce maxWords if defined
watch(internalValue, () => {
    if (props.maxWords && wordCount.value > props.maxWords) {
        internalValue.value = words.value.slice(0, props.maxWords).join(' ');
    }
});
</script>

<template>
    <div class="space-y-1.5" :dir="isRTL ? 'rtl' : 'ltr'">
        <!-- Label -->
        <Label v-if="label" :for="textareaId" class="flex items-center gap-1">
            <span>{{ label }}</span>
            <span v-if="required" class="text-destructive">*</span>
        </Label>

        <!-- Textarea -->
        <Textarea
            :id="textareaId"
            :placeholder="placeholder"
            :disabled="disabled"
            :rows="rows"
            :maxlength="props.maxChars"
            :aria-invalid="hasError"
            v-model="internalValue"
            :class="
                cn(
                    hasError &&
                        'border-destructive focus-visible:border-destructive focus-visible:ring-destructive',
                    isRTL && 'text-right',
                )
            "
        />

        <!-- Counters -->
        <div
            class="mt-1 flex justify-between text-xs text-muted-foreground"
            :class="isRTL ? 'flex-row-reverse' : ''"
        >
            <span>
                {{ t('app.words_count') }} :
                {{ wordCount }}
                <span v-if="props.maxWords">/ {{ props.maxWords }}</span>
            </span>
            <span v-if="props.maxChars"
                >{{ t('app.chars_count') }} : {{ charCount }} /
                {{ props.maxChars }}</span
            >
        </div>

        <!-- Error -->
        <p
            v-if="error"
            class="text-sm text-destructive"
            :class="isRTL ? 'text-right' : 'text-left'"
        >
            {{ error }}
        </p>
    </div>
</template>
