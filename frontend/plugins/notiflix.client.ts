import Notiflix from 'notiflix';
import type {
    FeedbackApi,
    FeedbackConfirmOptions,
    FeedbackLevel,
    FeedbackTarget,
} from '~/utils/feedback';

export default defineNuxtPlugin(() => {
    Notiflix.Notify.init({
        position: 'right-bottom',
        timeout: 4000,
        clickToClose: true,
        pauseOnHover: true,
        fontSize: '16px',
        width: '320px',
        distance: '16px',
        notifyPadding: '20px',
        showOnlyTheLastOne: false,
        warning: { background: '#facc15' },
        failure: { background: '#ed4137' },
        success: { background: '#16a34a' },
    });

    Notiflix.Block.init({
        svgSize: '32px',
        svgColor: '#ffffff',
        backgroundColor: 'rgba(15,23,42,0.75)',
        messageColor: '#e5e7eb',
    });

    Notiflix.Confirm.init({
        width: '360px',
        borderRadius: '16px',
        titleFontSize: '20px',
        messageFontSize: '15px',
        okButtonBackground: '#00bcf0',
        cancelButtonBackground: '#9ca3af',
    });

    const notify = (level: FeedbackLevel, message: string) => {
        Notiflix.Notify[level](message);
    };

    const block = (target: FeedbackTarget, message = 'Подождите...') => {
        if (!target) return;
        Notiflix.Block.circle(target, message);
    };

    const unblock = (target: FeedbackTarget) => {
        if (!target) return;
        Notiflix.Block.remove(target);
    };

    const withBlock: FeedbackApi['withBlock'] = async (target, task, message) => {
        block(target, message);

        try {
            return await task();
        } finally {
            unblock(target);
        }
    };

    const confirm = ({
        title = 'Подтверждение',
        message,
        confirmText = 'Подтвердить',
        cancelText = 'Отмена',
    }: FeedbackConfirmOptions) => {
        return new Promise<boolean>((resolve) => {
            Notiflix.Confirm.show(
                title,
                message,
                confirmText,
                cancelText,
                () => resolve(true),
                () => resolve(false),
            );
        });
    };

    const feedback: FeedbackApi = {
        notify,
        success: (message) => notify('success', message),
        failure: (message) => notify('failure', message),
        info: (message) => notify('info', message),
        warning: (message) => notify('warning', message),
        block,
        unblock,
        withBlock,
        confirm,
    };

    return {
        provide: {
            feedback,
            notify: Notiflix.Notify,
            block: Notiflix.Block,
            confirm: Notiflix.Confirm,
        },
    };
});
