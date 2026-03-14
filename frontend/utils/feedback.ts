export interface FeedbackConfirmOptions {
    title?: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
}

export type FeedbackLevel = 'success' | 'failure' | 'info' | 'warning';
export type FeedbackTarget = string | HTMLElement | null | undefined;

export interface FeedbackApi {
    notify: (level: FeedbackLevel, message: string) => void;
    success: (message: string) => void;
    failure: (message: string) => void;
    info: (message: string) => void;
    warning: (message: string) => void;
    block: (target: FeedbackTarget, message?: string) => void;
    unblock: (target: FeedbackTarget) => void;
    withBlock: <T>(
        target: FeedbackTarget,
        task: () => Promise<T>,
        message?: string,
    ) => Promise<T>;
    confirm: (options: FeedbackConfirmOptions) => Promise<boolean>;
}
