export const PHONE_MASK_OPTIONS = {
    mask: '+{7} 000 000-00-00',
    lazy: false,
    overwrite: true,
} as const;

export const normalizePhoneInput = (value: string | null | undefined): string => {
    const digits = (value || '').replace(/\D/g, '');

    if (digits.length === 10) {
        return `7${digits}`;
    }

    return digits;
};

export const isCompletePhoneInput = (
    value: string | null | undefined,
): boolean => {
    return normalizePhoneInput(value).length === 11;
};

export const formatPhoneForDisplay = (
    value: string | null | undefined,
    fallback = 'Не указано',
): string => {
    if (!value) return fallback;

    const digits = normalizePhoneInput(value);
    if (digits.length !== 11) return value;

    return `+7 ${digits.slice(1, 4)}-${digits.slice(4, 7)}-${digits.slice(7, 9)}-${digits.slice(9, 11)}`;
};
