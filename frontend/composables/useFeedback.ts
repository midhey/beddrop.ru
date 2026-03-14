import type { FeedbackApi } from '~/utils/feedback';

const fallbackFeedback: FeedbackApi = {
    notify: () => {
    },
    success: () => {
    },
    failure: () => {
    },
    info: () => {
    },
    warning: () => {
    },
    block: () => {
    },
    unblock: () => {
    },
    withBlock: async (_target, task) => task(),
    confirm: async ({ message }) => {
        if (typeof window !== 'undefined' && typeof window.confirm === 'function') {
            return window.confirm(message);
        }

        return false;
    },
};

export const useFeedback = (): FeedbackApi => {
    const nuxtApp = useNuxtApp();
    return (nuxtApp.$feedback as FeedbackApi | undefined) ?? fallbackFeedback;
};
