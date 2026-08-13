import { ref } from 'vue';

export function useCrudDialogs<T>() {
    const createOpen = ref(false);
    const showOpen = ref(false);
    const editOpen = ref(false);

    const selected = ref<T | null>(null);

    const openCreate = () => {
        createOpen.value = true;
    };

    const openShow = (item: T) => {
        selected.value = item;
        showOpen.value = true;
    };

    const openEdit = (item: T) => {
        selected.value = item;
        editOpen.value = true;
    };

    const closeCreate = () => {
        createOpen.value = false;
    };

    const closeShow = () => {
        showOpen.value = false;
        selected.value = null;
    };

    const closeEdit = () => {
        editOpen.value = false;
        selected.value = null;
    };

    return {
        createOpen,
        showOpen,
        editOpen,
        selected,

        openCreate,
        openShow,
        openEdit,

        closeCreate,
        closeShow,
        closeEdit,
    };
}
