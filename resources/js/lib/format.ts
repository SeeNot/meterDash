const EUR = new Intl.NumberFormat('en-IE', {
    style: 'currency',
    currency: 'EUR',
});

const KWH = new Intl.NumberFormat('en-IE', {
    maximumFractionDigits: 1,
});

const RATE = new Intl.NumberFormat('en-IE', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 4,
    maximumFractionDigits: 4,
});

const DATE = new Intl.DateTimeFormat('en-IE', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

export function euro(value: number): string {
    return EUR.format(value);
}

export function rate(value: number): string {
    return RATE.format(value);
}

export function kwh(value: number, digits = 1): string {
    return value.toLocaleString('en-IE', {
        maximumFractionDigits: digits,
        minimumFractionDigits: digits,
    });
}

export function kwhCompact(value: number): string {
    return KWH.format(value);
}

export function date(value: string): string {
    return DATE.format(new Date(`${value}T00:00:00`));
}
