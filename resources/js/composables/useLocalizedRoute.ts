import { usePage } from '@inertiajs/vue3'

export function useLocale() {
    const page = usePage()

    return page.props.locale as string
}