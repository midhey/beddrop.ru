import { ref } from 'vue';
import type { AxiosError } from 'axios';
import { useFeedback } from '~/composables/useFeedback';

export function useApiHelpers() {
    const errorMessage = ref<string | null>(null);
    const feedback = useFeedback();

    const extractErrorMessage = (error: any): string => {
        let message = 'Произошла ошибка при запросе к серверу';

        const axiosError = error as AxiosError<any>;

        const respData = axiosError?.response?.data as any;

        if (respData?.message) {
            message = respData.message;
        } else if (respData?.errors) {
            const errors = respData.errors;
            const firstKey = Object.keys(errors)[0];
            if (firstKey && Array.isArray(errors[firstKey]) && errors[firstKey].length) {
                message = errors[firstKey][0];
            }
        } else if (axiosError?.message) {
            message = axiosError.message;
        }

        return message;
    };

    const handleApiError = (error: any, notify = true) => {
        const msg = extractErrorMessage(error);
        errorMessage.value = msg;
        if (notify) {
            feedback.failure(msg);
        }
        console.error('[API ERROR]', error);
    };

    return {
        errorMessage,
        extractErrorMessage,
        handleApiError,
    };
}
