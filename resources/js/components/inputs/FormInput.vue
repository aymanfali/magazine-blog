```vue
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Eye, EyeOff } from '@lucide/vue';
import { computed, useId, ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface Props {
    modelValue: string | number | null;
    label?: string;
    type?: string;
    placeholder?: string;
    error?: string;
    id?: string;
    name?: string;
    autocomplete?: string;
    disabled?: boolean;
    readonly?: boolean;
    required?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    disabled: false,
    readonly: false,
    required: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number | null];
}>();

/**
 * --------------------------------------------------------------------------
 * IDs
 * --------------------------------------------------------------------------
 */

const generatedId = useId();

const inputId = computed(() => props.id ?? `input-${generatedId}`);
const errorId = computed(() => `${inputId.value}-error`);

/**
 * --------------------------------------------------------------------------
 * Locale / Direction
 * --------------------------------------------------------------------------
 */

const page = usePage();

const isRTL = computed(() => page.props.locale === 'ar');

/**
 * --------------------------------------------------------------------------
 * State
 * --------------------------------------------------------------------------
 */

const showPassword = ref(false);

const hasError = computed(() => Boolean(props.error));

/**
 * --------------------------------------------------------------------------
 * Input type
 * --------------------------------------------------------------------------
 */

const inputType = computed(() => {
    if (props.type === 'password') {
        return showPassword.value ? 'text' : 'password';
    }

    return props.type;
});

/**
 * --------------------------------------------------------------------------
 * Password toggle
 * --------------------------------------------------------------------------
 */

const isPassword = computed(() => props.type === 'password');

const passwordToggleLabel = computed(() => {
    if (isRTL.value) {
        return showPassword.value ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور';
    }

    return showPassword.value ? 'Hide password' : 'Show password';
});

/**
 * --------------------------------------------------------------------------
 * Input classes
 * --------------------------------------------------------------------------
 */

const inputClasses = computed(() =>
    cn(
        'w-full transition-colors',

        // Password padding
        isPassword.value && (isRTL.value ? 'pl-10' : 'pr-10'),

        // RTL text alignment
        isRTL.value && 'text-right',

        // Error state
        hasError.value && [
            'border-destructive',
            'focus-visible:border-destructive',
            'focus-visible:ring-destructive/20',
        ],

        // Disabled
        props.disabled && 'cursor-not-allowed',

        // Readonly
        props.readonly && 'cursor-default',
    ),
);

/**
 * --------------------------------------------------------------------------
 * Error classes
 * --------------------------------------------------------------------------
 */

const errorClasses = computed(() =>
    cn('text-sm text-destructive', isRTL.value ? 'text-right' : 'text-left'),
);

/**
 * --------------------------------------------------------------------------
 * Events
 * --------------------------------------------------------------------------
 */

const updateValue = (value: string | number) => {
    emit('update:modelValue', value);
};

const togglePassword = () => {
    if (props.disabled || props.readonly) {
        return;
    }

    showPassword.value = !showPassword.value;
};
</script>

<template>
    <div class="space-y-2" :dir="isRTL ? 'rtl' : 'ltr'">
        <!-- Label -->
        <Label v-if="label" :for="inputId" class="flex items-center gap-1">
            <span>{{ label }}</span>

            <span v-if="required" class="text-destructive" aria-hidden="true">
                *
            </span>
        </Label>

        <!-- Input -->
        <div class="relative">
            <Input
                :id="inputId"
                :name="name"
                :type="inputType"
                :placeholder="placeholder"
                :autocomplete="autocomplete"
                :model-value="modelValue"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :aria-invalid="hasError"
                :aria-required="required"
                :aria-describedby="hasError ? errorId : undefined"
                :class="inputClasses"
                @update:model-value="updateValue"
            />

            <!-- Password Toggle -->
            <Button
                v-if="isPassword"
                type="button"
                variant="ghost"
                size="icon"
                :disabled="disabled || readonly"
                :aria-label="passwordToggleLabel"
                :aria-pressed="showPassword"
                :title="passwordToggleLabel"
                class="absolute top-1/2 h-8 w-8 -translate-y-1/2"
                :class="isRTL ? 'left-1' : 'right-1'"
                @click="togglePassword"
            >
                <Eye v-if="!showPassword" class="h-4 w-4" aria-hidden="true" />

                <EyeOff v-else class="h-4 w-4" aria-hidden="true" />
            </Button>
        </div>

        <!-- Error -->
        <p v-if="error" :id="errorId" :class="errorClasses" role="alert">
            {{ error }}
        </p>
    </div>
</template>
```
