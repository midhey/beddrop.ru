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
        warning: { background: '#FFB800' }, // --color-secondary
        failure: { background: '#E74C3C' }, // --color-error
        success: { background: '#7A3BFF' }, // --color-primary
        info: { background: '#00bcf0' },
    });

    Notiflix.Block.init({
        svgSize: '32px',
        svgColor: '#7A3BFF', // --color-primary
        backgroundColor: 'rgba(255,255,255,0.75)',
        messageColor: '#1F1B2D', // --color-text
    });

    Notiflix.Confirm.init({
        width: '360px',
        borderRadius: '22px', // matches card radius
        titleColor: '#1F1B2D',
        titleFontSize: '20px',
        messageFontSize: '15px',
        okButtonBackground: '#7A3BFF', // --color-primary
        okButtonColor: '#ffffff',
        cancelButtonBackground: '#F4F2FF', // --color-bg
        cancelButtonColor: '#6B6780', // --color-text-muted
        backOverlayColor: 'rgba(31, 27, 45, 0.4)', // --color-text + alpha
    });

    const notify = (level: FeedbackLevel, message: string) => {
        Notiflix.Notify[level](message);
    };

    const canResolveTarget = (target: FeedbackTarget): boolean => {
        if (typeof target !== 'string') {
            return true;
        }

        return document.querySelector(target) !== null;
    };

    const block = (target: FeedbackTarget, message = 'Подождите...') => {
        if (!target) return;
        if (!canResolveTarget(target)) return;
        Notiflix.Block.circle(target, message);
    };

    const unblock = (target: FeedbackTarget) => {
        if (!target) return;
        if (!canResolveTarget(target)) return;
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
