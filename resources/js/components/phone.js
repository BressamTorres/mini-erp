export function formatPhone(value) {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 11);

    if (digits.length <= 2) {
        return digits.length > 0 ? `(${digits}` : '';
    }

    const areaCode = digits.slice(0, 2);
    const subscriber = digits.slice(2);
    const prefixLength = digits.length === 11 ? 5 : 4;
    const prefix = subscriber.slice(0, prefixLength);
    const suffix = subscriber.slice(prefixLength);

    return `(${areaCode}) ${prefix}${suffix ? `-${suffix}` : ''}`;
}
