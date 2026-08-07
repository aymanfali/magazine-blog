<script setup lang="ts">
import { Form, Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useLang } from '@/composables/useLang';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

const page = usePage();
const locale = (page.props as any).locale ?? 'en';
const { t } = useLang();

setLayoutProps({
    title: t('verify_email_title'),
    description: t('verify_email_description'),
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('verify_email_head_title', 'Email verification')" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ t('verify_email_sent_message', 'A new verification link has been sent to the email address you provided during registration.') }}
    </div>

    <Form
        v-bind="send.form(locale)"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary">
            <Spinner v-if="processing" />
            {{ t('labels.resend_verification_email', 'Resend verification email') }}
        </Button>

        <TextLink :href="logout(locale)" as="button" class="mx-auto block text-sm">
            {{ t('labels.logout', 'Log out') }}
        </TextLink>
    </Form>
</template>
