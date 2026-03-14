export const formatPrice = (
    value: string | number | null | undefined,
): string => {
    if (value == null) return '';

    const amount = typeof value === 'string' ? Number(value) : value;
    if (Number.isNaN(amount)) return String(value);

    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        maximumFractionDigits: 0,
    }).format(amount);
};

export const formatDateTime = (iso: string): string => {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return iso;

    return date.toLocaleString('ru-RU', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};
