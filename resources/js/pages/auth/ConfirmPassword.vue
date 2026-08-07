<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { setLayoutProps } from '@inertiajs/vue3';
import { useLang } from '@/composables/useLang';
import { store } from '@/routes/password/confirm';

const { t } = useLang();

setLayoutProps({
    title: t('confirm_password_title'),
    description: t('confirm_password_description'),
});
</script>

<template>
    <Head :title="t('confirm_password_head_title', 'Confirm password')" />

    <PasskeyVerify
        :routes="{
            options: confirmOptions(),
            submit: confirmStore(),
        }"
        :label="t('labels.confirm_with_passkey', 'Confirm with passkey')"
        :loading-label="t('labels.confirming', 'Confirming...')"
        :separator="t('labels.or_confirm_with_password', 'Or confirm with password')"
    />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password">{{t('labels.password')}}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('labels.confirm_password', 'Confirm password') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
