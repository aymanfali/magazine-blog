<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { wTrans } from 'laravel-vue-i18n';
import { computed, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import {
    Calendar,
    CheckCircle,
    CircleAlert,
    Clock,
    ShieldX,
} from 'lucide-vue-next';
import FormTextarea from '../inputs/FormTextarea.vue';

interface Message {
    id: number;
    name: string;
    email: string;
    subject: string;
    type: string;
    message: string;
    reply?: string | null;
    replied_at?: string | null;
    attachment?: string | null;
    status: 'new' | 'in_progress' | 'replied' | 'spam';
    created_at: string;
}

const props = defineProps<{
    modelValue: boolean;
    message: Message | null;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'send-reply', payload: { id: number; reply: string }): void;
}>();

/* ---------------------------------------- */
const form = useForm({
    reply: '',
    message_id: null as number | null,
});

/* Reset reply when modal opens */
watch(
    () => props.message,
    (msg) => {
        if (msg) {
            form.message_id = msg.id;
            form.reply = '';
            form.clearErrors();
        }
    },
    { immediate: true },
);

/* v-model binding */
const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

/* ---------------------------------------- */
function sendReply() {
    if (!form.reply.trim() || !form.message_id) return;

    form.post('/messages/reply', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('update:modelValue', false);
        },
    });
}

const statusMap = {
    new: {
        label: wTrans('app.new'),
        class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        icon: CircleAlert,
    },
    in_progress: {
        label: wTrans('app.in_progress'),
        class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        icon: Clock,
    },
    replied: {
        label: wTrans('app.replied'),
        class: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        icon: CheckCircle,
    },
    spam: {
        label: wTrans('app.spam'),
        class: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
        icon: ShieldX,
    },
} as const;

const currentStatus = computed(() => {
    if (!props.message) return null;
    return statusMap[props.message.status];
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-2xl">
            <!-- Header -->
            <DialogHeader>
                <DialogTitle>{{
                    wTrans('app.message_details_title')
                }}</DialogTitle>
                <DialogDescription>
                    {{ wTrans('app.message_details_description') }}
                </DialogDescription>
            </DialogHeader>

            <!-- Body -->
            <div v-if="message" class="space-y-5">
                <!-- Meta -->
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-muted-foreground">
                            {{ wTrans('app.name') }}
                        </p>
                        <p class="font-medium">{{ message.name }}</p>
                    </div>

                    <div>
                        <p class="text-muted-foreground">
                            {{ wTrans('app.email') }}
                        </p>
                        <p class="font-medium">{{ message.email }}</p>
                    </div>

                    <div>
                        <p class="text-muted-foreground">
                            {{ wTrans('app.subject') }}
                        </p>
                        <p class="font-medium">{{ message.subject }}</p>
                    </div>

                    <div v-if="message.type">
                        <p class="text-muted-foreground">
                            {{ wTrans('app.message_type') }}
                        </p>
                        <p class="font-medium">{{ message.type }}</p>
                    </div>

                    <div class="col-span-2">
                        <p class="mb-1 text-muted-foreground">
                            {{ wTrans('app.message') }}
                        </p>
                        <div class="rounded-lg bg-muted p-3 text-sm">
                            {{ message.message }}
                        </div>
                    </div>

                    <div v-if="message.attachment" class="col-span-2 space-y-1">
                        <p class="text-muted-foreground">
                            {{ wTrans('app.attachments') }}
                        </p>
                        <a
                            :href="message.attachment"
                            target="_blank"
                            class="text-sm text-primary underline"
                        >
                            {{ wTrans('app.view_file') }}
                        </a>
                    </div>

                    <div class="col-span-2">
                        <span
                            v-if="currentStatus"
                            :class="[
                                currentStatus.class,
                                'inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium',
                            ]"
                        >
                            <component
                                :is="currentStatus.icon"
                                class="h-3 w-3"
                            />
                            {{ currentStatus.label }}
                        </span>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-2xl font-medium">
                        {{ wTrans('app.reply') }}
                    </label>
                    <label v-if="message.replied_at" class="flex gap-2 text-sm font-medium text-gray-400">
                        <Calendar class="h-4 w-4"/> {{ message.replied_at }}
                    </label>

                    <!-- ✅ IF REPLIED: show message -->
                    <div
                        v-if="message.status === 'replied'"
                        class="rounded-lg border bg-muted p-3 text-sm"
                    >
                        {{ message.reply || wTrans('app.no_reply_available') }}
                    </div>

                    <!-- ✅ ELSE: allow replying -->
                    <template v-else>
                        <FormTextarea
                            v-model="form.reply"
                            :label="`${wTrans('app.message').value}`"
                            required
                            :error="form.errors.reply"
                        />

                        <div class="flex justify-end gap-2">
                            <Button variant="outline" @click="isOpen = false">
                                {{ wTrans('app.cancel') }}
                            </Button>

                            <Button
                                @click="sendReply"
                                :disabled="
                                    !form.reply.trim() || form.processing
                                "
                            >
                                {{
                                    form.processing
                                        ? wTrans('app.sending')
                                        : wTrans('app.send_reply')
                                }}
                            </Button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Empty -->
            <div v-else class="py-6 text-center text-muted-foreground">
                {{ wTrans('app.no_message') }}
            </div>
        </DialogContent>
    </Dialog>
</template>
