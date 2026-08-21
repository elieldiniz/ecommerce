const BIN_PREFIXES = [
    { brand: 'visa', patterns: [/^4/] },
    { brand: 'mastercard', patterns: [/^5[1-5]/, /^222[1-9]/, /^22[3-9]/, /^2[3-6]/, /^27[01]/, /^2720/] },
    { brand: 'amex', patterns: [/^34/, /^37/] },
    { brand: 'elo', patterns: [/^4011/, /^4312/, /^4389/, /^504175/, /^509/, /^627780/, /^636297/, /^636368/, /^65(0|1)/] },
    { brand: 'aura', patterns: [/^50/] },
    { brand: 'jcb', patterns: [/^35(2[89]|[3-8])/] },
    { brand: 'diners', patterns: [/^30[0-5]/, /^36/, /^38/] },
    { brand: 'discover', patterns: [/^6011/, /^64[4-9]/, /^65/] },
];

export function luhnIsValid(digits) {
    if (!/^[0-9]+$/.test(digits)) {
        return false;
    }

    let sum = 0;
    let shouldDouble = false;

    for (let i = digits.length - 1; i >= 0; i--) {
        let digit = parseInt(digits[i], 10);

        if (shouldDouble) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }

        sum += digit;
        shouldDouble = !shouldDouble;
    }

    return sum % 10 === 0;
}

export function detectBrand(digits) {
    for (const { brand, patterns } of BIN_PREFIXES) {
        if (patterns.some((pattern) => pattern.test(digits))) {
            return brand;
        }
    }

    return null;
}

export function formatCardNumber(digits) {
    return digits.match(/.{1,4}/g)?.join(' ') ?? '';
}

const BRAND_LABELS = {
    visa: 'Visa',
    mastercard: 'Mastercard',
    amex: 'Amex',
    elo: 'Elo',
    aura: 'Aura',
    jcb: 'JCB',
    diners: 'Diners',
    discover: 'Discover',
};

export function brandLabel(brand) {
    return BRAND_LABELS[brand] ?? '';
}

export function normalizeExpiry(mmYY) {
    const match = /^(0[1-9]|1[0-2])\/([0-9]{2})$/.exec(mmYY);

    if (!match) {
        return mmYY;
    }

    const currentCentury = Math.floor(new Date().getFullYear() / 100) * 100;

    return `${match[1]}/${currentCentury + parseInt(match[2], 10)}`;
}

export function isValidExpiry(mmYYYY) {
    const match = /^(0[1-9]|1[0-2])\/([0-9]{4})$/.exec(mmYYYY);

    if (!match) {
        return false;
    }

    const month = parseInt(match[1], 10);
    const year = parseInt(match[2], 10);

    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth() + 1;

    if (year < currentYear) {
        return false;
    }

    if (year === currentYear && month < currentMonth) {
        return false;
    }

    return true;
}

export function isValidCvv(cvv, brand) {
    if (!/^[0-9]+$/.test(cvv)) {
        return false;
    }

    return brand === 'amex' ? cvv.length === 4 : cvv.length === 3;
}

export function cardCheckout() {
    return {
        cardNumberRaw: '',
        cardNumberFormatted: '',
        holder: '',
        expiry: '',
        cvv: '',
        submitting: false,
        tokenizeError: '',
        touched: { cardNumber: false, expiry: false, cvv: false },

        get brand() {
            return detectBrand(this.cardNumberRaw);
        },

        get brandDisplayName() {
            return brandLabel(this.brand);
        },

        get cardNumberError() {
            if (!this.touched.cardNumber || this.cardNumberRaw === '') {
                return '';
            }

            if (this.brand === null) {
                return 'Bandeira não reconhecida.';
            }

            if (!luhnIsValid(this.cardNumberRaw)) {
                return 'Número de cartão inválido.';
            }

            return '';
        },

        get expiryError() {
            if (!this.touched.expiry || this.expiry === '') {
                return '';
            }

            return isValidExpiry(normalizeExpiry(this.expiry)) ? '' : 'Validade inválida.';
        },

        get cvvError() {
            if (!this.touched.cvv || this.cvv === '') {
                return '';
            }

            return isValidCvv(this.cvv, this.brand) ? '' : 'CVV inválido.';
        },

        onCardNumberInput(event) {
            this.cardNumberRaw = event.target.value.replace(/\D/g, '').slice(0, 19);
            this.cardNumberFormatted = formatCardNumber(this.cardNumberRaw);
            event.target.value = this.cardNumberFormatted;
        },

        onCardNumberBlur() {
            this.touched.cardNumber = true;
        },

        onExpiryInput(event) {
            const digits = event.target.value.replace(/\D/g, '').slice(0, 6);
            this.expiry = digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
            event.target.value = this.expiry;
        },

        onExpiryBlur(event) {
            this.expiry = normalizeExpiry(this.expiry);
            event.target.value = this.expiry;
            this.touched.expiry = true;
        },

        onCvvInput(event) {
            this.cvv = event.target.value.replace(/\D/g, '').slice(0, 4);
            event.target.value = this.cvv;
        },

        onCvvBlur() {
            this.touched.cvv = true;
        },

        cardIsValid() {
            return luhnIsValid(this.cardNumberRaw)
                && this.brand !== null
                && isValidExpiry(normalizeExpiry(this.expiry))
                && isValidCvv(this.cvv, this.brand);
        },

        async submit(paymentMethodSlug) {
            if (paymentMethodSlug !== 'cartao') {
                this.$wire.finalizarCompra();

                return;
            }

            if (!this.cardIsValid() || this.submitting) {
                return;
            }

            this.submitting = true;
            this.tokenizeError = '';

            try {
                const response = await fetch('/checkout/tokenizar-cartao', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        holder: this.holder,
                        cardNumber: this.cardNumberRaw,
                        expirationDate: normalizeExpiry(this.expiry),
                        securityCode: this.cvv,
                    }),
                });

                if (!response.ok) {
                    this.tokenizeError = 'Não foi possível validar o cartão. Tente novamente.';

                    return;
                }

                const { token } = await response.json();

                await this.$wire.set('cardToken', token);
                this.$wire.finalizarCompra();
            } catch {
                this.tokenizeError = 'Não foi possível validar o cartão. Tente novamente.';
            } finally {
                this.submitting = false;
            }
        },
    };
}
