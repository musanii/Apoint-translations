
import { useEffect, useMemo, useState } from 'react';

type ServiceOption = {
    id: number;
    name: string;
    slug: string;
};

type LanguageOption = {
    id: number;
    name: string;
    code?: string;
    native_name?: string | null;
};

type CurrencyOption = {
    id: number;
    code: string;
    name?: string;
    symbol: string;
    decimal_places: number;
    is_default?: boolean;
};

type CalculatorOption = {
    service: {
        id: number;
        name: string;
    };
    source_language: LanguageOption | null;
    target_language: LanguageOption | null;
    currency: CurrencyOption;
    pricing_type: 'per_word' | 'per_page' | 'fixed' | string;
    unit: string;
    description: string | null;
};

type CalculatorOptionsResponse = {
    services: ServiceOption[];
    languages: LanguageOption[];
    currencies: CurrencyOption[];
    pricing_options: CalculatorOption[];
};

type Estimate = {
    pricing_type: string;
    unit: string;
    quantity: number;
    rate: number;
    subtotal: number;
    minimum_charge: number;
    total: number;
    currency: string;
};

type Selection = {
    serviceId: number;
    sourceLanguageId: number | null;
    targetLanguageId: number | null;
    currencyId: number;
};

function CalculatorSkeleton() {
    return (
        <section className="bg-[#FAF6F0] px-6 py-20">
            <div className="mx-auto max-w-6xl animate-pulse">
                <div className="mx-auto mb-12 max-w-2xl space-y-4 text-center">
                    <div className="mx-auto h-3 w-36 rounded-full bg-[#E8B4B8]/50" />
                    <div className="mx-auto h-10 w-3/4 rounded-lg bg-[#3A091A]/10" />
                    <div className="mx-auto h-4 w-2/3 rounded bg-gray-200" />
                </div>

                <div className="grid overflow-hidden rounded-3xl bg-white shadow-xl md:grid-cols-[1.2fr_0.8fr]">
                    <div className="space-y-7 p-7 md:p-10">
                        {[1, 2, 3, 4].map(item => (
                            <div key={item} className="space-y-3">
                                <div className="h-4 w-28 rounded bg-gray-200" />
                                <div className="h-12 w-full rounded-xl bg-gray-100" />
                            </div>
                        ))}
                    </div>

                    <div className="space-y-6 bg-[#3A091A] p-8 md:p-10">
                        <div className="h-4 w-40 rounded bg-white/20" />
                        <div className="h-12 w-3/4 rounded-lg bg-white/20" />
                        <div className="h-px w-full bg-white/10" />
                        <div className="h-4 w-2/3 rounded bg-white/10" />
                        <div className="h-4 w-1/2 rounded bg-white/10" />
                    </div>
                </div>
            </div>
        </section>
    );
}

function getQuantityLabel(pricingType: string, unit: string): string {
    if (pricingType === 'per_word' || unit === 'word') {
        return 'Document word count';
    }

    if (pricingType === 'per_page' || unit === 'page') {
        return 'Document page count';
    }

    return `Quantity (${unit || 'units'})`;
}

function getQuantityQuestion(pricingType: string, unit: string): string {
    if (pricingType === 'per_word' || unit === 'word') {
        return 'How many words are in your document?';
    }

    if (pricingType === 'per_page' || unit === 'page') {
        return 'How many pages need translation?';
    }

    return `How many ${unit || 'units'} do you need?`;
}

function getQuantityHelp(pricingType: string, unit: string): string {
    if (pricingType === 'per_word' || unit === 'word') {
        return 'You can usually find the word count in your document editor.';
    }

    if (pricingType === 'per_page' || unit === 'page') {
        return 'Count the pages that require translation.';
    }

    return 'Enter the quantity required for your project.';
}

export default function PriceCalculator() {
    const [services, setServices] = useState<ServiceOption[]>([]);
    const [languages, setLanguages] = useState<LanguageOption[]>([]);
    const [currencies, setCurrencies] = useState<CurrencyOption[]>([]);
    const [options, setOptions] = useState<CalculatorOption[]>([]);

    const [selection, setSelection] = useState<Selection | null>(null);
    const [quantity, setQuantity] = useState(1);
    const [estimate, setEstimate] = useState<Estimate | null>(null);

    const [loadingOptions, setLoadingOptions] = useState(true);
    const [calculating, setCalculating] = useState(false);
    const [error, setError] = useState('');

    // Load all active choices independently of configured pricing rules.
    useEffect(() => {
        let cancelled = false;

        async function loadOptions() {
            try {
                const response = await fetch('/calculator/options', {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Unable to load calculator options.');
                }

                const data: CalculatorOptionsResponse = await response.json();

                if (cancelled) return;

                const loadedServices = data.services ?? [];
                const loadedLanguages = data.languages ?? [];
                const loadedCurrencies = data.currencies ?? [];
                const loadedOptions = data.pricing_options ?? [];

                setServices(loadedServices);
                setLanguages(loadedLanguages);
                setCurrencies(loadedCurrencies);
                setOptions(loadedOptions);

                // Start with a real configured combination when one exists.
                const first = loadedOptions[0];

                if (first) {
                    setSelection({
                        serviceId: first.service.id,
                        sourceLanguageId: first.source_language?.id ?? null,
                        targetLanguageId: first.target_language?.id ?? null,
                        currencyId: first.currency.id,
                    });
                } else if (loadedServices.length > 0 && loadedCurrencies.length > 0) {
                    setSelection({
                        serviceId: loadedServices[0].id,
                        sourceLanguageId: null,
                        targetLanguageId: null,
                        currencyId:
                            loadedCurrencies.find(currency => currency.is_default)?.id ??
                            loadedCurrencies[0].id,
                    });
                }
            } catch {
                if (!cancelled) {
                    setError(
                        'We could not load the calculator. Please try again later.',
                    );
                }
            } finally {
                if (!cancelled) {
                    setLoadingOptions(false);
                }
            }
        }

        loadOptions();

        return () => {
            cancelled = true;
        };
    }, []);

    // Pricing rules belonging to the selected service.
    const serviceOptions = useMemo(
        () =>
            options.filter(
                option => option.service.id === selection?.serviceId,
            ),
        [options, selection?.serviceId],
    );

    // Some services, such as fixed-price services, do not require languages.
    const requiresLanguages = useMemo(
        () =>
            serviceOptions.some(
                option => option.source_language || option.target_language,
            ),
        [serviceOptions],
    );

    // Match the exact service, language pair and currency.
    const selectedOption = useMemo(
        () =>
            options.find(
                option =>
                    option.service.id === selection?.serviceId &&
                    (option.source_language?.id ?? null) ===
                        selection?.sourceLanguageId &&
                    (option.target_language?.id ?? null) ===
                        selection?.targetLanguageId &&
                    option.currency.id === selection?.currencyId,
            ) ?? null,
        [
            options,
            selection?.serviceId,
            selection?.sourceLanguageId,
            selection?.targetLanguageId,
            selection?.currencyId,
        ],
    );

    // Use an existing service rule to determine quantity wording even when
    // the customer selects a combination without a configured price.
    const quantityTemplate = selectedOption ?? serviceOptions[0] ?? null;

    const isFixed = quantityTemplate?.pricing_type === 'fixed';

    const selectedService = services.find(
        service => service.id === selection?.serviceId,
    );

    const selectedCurrency = currencies.find(
        currency => currency.id === selection?.currencyId,
    );

    // All active currencies are available, not just currencies with prices.
    const availableCurrencies = currencies;

    // All active languages are available for both selectors.
    const sourceLanguages = languages;
    const targetLanguages = languages;

    function chooseService(serviceId: number) {
        const first = options.find(
            option => option.service.id === serviceId,
        );

        const currentCurrency = currencies.find(
            currency => currency.id === selection?.currencyId,
        );

        const defaultCurrency =
            currencies.find(currency => currency.is_default) ??
            currencies[0];

        setSelection({
            serviceId,
            sourceLanguageId: first?.source_language?.id ?? null,
            targetLanguageId: first?.target_language?.id ?? null,
            currencyId:
                first?.currency.id ??
                currentCurrency?.id ??
                defaultCurrency?.id ??
                0,
        });

        setEstimate(null);
        setError('');
        setQuantity(1);
    }

    function chooseSourceLanguage(sourceLanguageId: number) {
        if (!selection) return;

        setSelection({
            ...selection,
            sourceLanguageId,
        });

        setEstimate(null);
        setError('');
    }

    function chooseTargetLanguage(targetLanguageId: number) {
        if (!selection) return;

        setSelection({
            ...selection,
            targetLanguageId,
        });

        setEstimate(null);
        setError('');
    }

    function chooseCurrency(currencyId: number) {
        if (!selection) return;

        setSelection({
            ...selection,
            currencyId,
        });

        setEstimate(null);
        setError('');
    }

    // Recalculate only when a matching pricing rule exists.
    useEffect(() => {
        if (!selection || options.length === 0) {
            setEstimate(null);
            setCalculating(false);
            return;
        }

        const matchingRule = options.find(
            option =>
                option.service.id === selection.serviceId &&
                (option.source_language?.id ?? null) ===
                    selection.sourceLanguageId &&
                (option.target_language?.id ?? null) ===
                    selection.targetLanguageId &&
                option.currency.id === selection.currencyId,
        );

        if (!matchingRule) {
            setEstimate(null);
            setCalculating(false);
            setError('');
            return;
        }

        let cancelled = false;

        const timer = window.setTimeout(async () => {
            setCalculating(true);
            setError('');

            try {
                const params = new URLSearchParams({
                    service_id: String(selection.serviceId),
                    source_language_id:
                        selection.sourceLanguageId === null
                            ? ''
                            : String(selection.sourceLanguageId),
                    target_language_id:
                        selection.targetLanguageId === null
                            ? ''
                            : String(selection.targetLanguageId),
                    currency_id: String(selection.currencyId),
                    quantity: String(quantity),
                });

                const response = await fetch(
                    `/calculator/calculate?${params.toString()}`,
                    {
                        headers: {
                            Accept: 'application/json',
                        },
                    },
                );

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message || 'Unable to calculate this estimate.',
                    );
                }

                if (!cancelled) {
                    setEstimate(data.estimate);
                }
            } catch (exception) {
                if (!cancelled) {
                    setEstimate(null);
                    setError(
                        exception instanceof Error
                            ? exception.message
                            : 'Unable to calculate this estimate.',
                    );
                }
            } finally {
                if (!cancelled) {
                    setCalculating(false);
                }
            }
        }, 250);

        return () => {
            cancelled = true;
            window.clearTimeout(timer);
        };
    }, [
        options,
        selection?.serviceId,
        selection?.sourceLanguageId,
        selection?.targetLanguageId,
        selection?.currencyId,
        quantity,
    ]);

    const formattedTotal = estimate
        ? new Intl.NumberFormat('en', {
              style: 'currency',
              currency: estimate.currency,
              minimumFractionDigits: selectedCurrency?.decimal_places ?? 2,
              maximumFractionDigits: selectedCurrency?.decimal_places ?? 2,
          }).format(estimate.total)
        : null;

    const formattedMinimumCharge = estimate
        ? new Intl.NumberFormat('en', {
              style: 'currency',
              currency: estimate.currency,
              minimumFractionDigits: selectedCurrency?.decimal_places ?? 2,
              maximumFractionDigits: selectedCurrency?.decimal_places ?? 2,
          }).format(estimate.minimum_charge)
        : null;

    const quoteRequestUrl = useMemo(() => {
        if (!selection) {
            return '/quote-requests/create';
        }

        const params = new URLSearchParams({
            service_id: String(selection.serviceId),
            currency_id: String(selection.currencyId),
        });

        if (selection.sourceLanguageId !== null) {
            params.set('source_language_id', String(selection.sourceLanguageId));
        }

        if (selection.targetLanguageId !== null) {
            params.set('target_language_id', String(selection.targetLanguageId));
        }

        if (!isFixed && quantity > 0) {
            params.set('quantity', String(quantity));
        }

        return `/quote-requests/create?${params.toString()}`;
    }, [
        selection?.serviceId,
        selection?.sourceLanguageId,
        selection?.targetLanguageId,
        selection?.currencyId,
        quantity,
        isFixed,
    ]);

    if (loadingOptions) {
        return <CalculatorSkeleton />;
    }

    if (services.length === 0 || currencies.length === 0) {
        return (
            <section
                id="price-calculator"
                className="bg-[#FAF6F0] px-6 py-20"
            >
                <div className="mx-auto max-w-2xl text-center">
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-[#9B6877]">
                        Transparent pricing
                    </p>

                    <h2 className="mt-3 font-serif text-4xl text-[#3A091A]">
                        Request a personalised quote
                    </h2>

                    <p className="mt-4 leading-7 text-gray-600">
                        The calculator is currently unavailable. Please contact
                        us for a personalised quote.
                    </p>

                    {error && (
                        <p role="alert" className="mt-4 text-sm text-red-600">
                            {error}
                        </p>
                    )}
                </div>
            </section>
        );
    }

    return (
        <section
            id="price-calculator"
            className="bg-[#FAF6F0] px-6 py-20"
        >
            <div className="mx-auto max-w-6xl">
                <div className="mx-auto mb-12 max-w-2xl text-center">
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-[#9B6877]">
                        Transparent pricing
                    </p>

                    <h2 className="mt-3 font-serif text-4xl text-[#3A091A] md:text-5xl">
                        Calculate your estimate
                    </h2>

                    <p className="mt-4 leading-7 text-gray-600">
                        Get an instant estimate for your translation or
                        localisation project. No account required.
                    </p>
                </div>

                <div className="grid overflow-hidden rounded-3xl bg-white shadow-xl md:grid-cols-[1.2fr_0.8fr]">
                    {/* Calculator form */}
                    <div className="space-y-6 p-7 md:p-10">
                        <div>
                            <label
                                htmlFor="calculator-service"
                                className="mb-2 block text-sm font-semibold text-[#3A091A]"
                            >
                                What service do you need?
                            </label>

                            <select
                                id="calculator-service"
                                value={selection?.serviceId ?? ''}
                                onChange={event =>
                                    chooseService(Number(event.target.value))
                                }
                                className="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-800 outline-none transition focus:border-[#9B6877] focus:ring-2 focus:ring-[#E8B4B8]/40"
                            >
                                {services.map(service => (
                                    <option
                                        key={service.id}
                                        value={service.id}
                                    >
                                        {service.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {requiresLanguages && (
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label
                                        htmlFor="calculator-source"
                                        className="mb-2 block text-sm font-semibold text-[#3A091A]"
                                    >
                                        What language is your document in?
                                    </label>

                                    <select
                                        id="calculator-source"
                                        value={selection?.sourceLanguageId ?? ''}
                                        onChange={event => {
                                            if (event.target.value !== '') {
                                                chooseSourceLanguage(
                                                    Number(event.target.value),
                                                );
                                            }
                                        }}
                                        className="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 outline-none transition focus:border-[#9B6877]"
                                    >
                                        <option value="">
                                            Select source language
                                        </option>

                                        {sourceLanguages.map(language => (
                                            <option
                                                key={language.id}
                                                value={language.id}
                                            >
                                                {language.name}
                                                {language.native_name &&
                                                language.native_name !== language.name
                                                    ? ` (${language.native_name})`
                                                    : ''}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label
                                        htmlFor="calculator-target"
                                        className="mb-2 block text-sm font-semibold text-[#3A091A]"
                                    >
                                        Which language do you need?
                                    </label>

                                    <select
                                        id="calculator-target"
                                        value={selection?.targetLanguageId ?? ''}
                                        onChange={event => {
                                            if (event.target.value !== '') {
                                                chooseTargetLanguage(
                                                    Number(event.target.value),
                                                );
                                            }
                                        }}
                                        className="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 outline-none transition focus:border-[#9B6877]"
                                    >
                                        <option value="">
                                            Select target language
                                        </option>

                                        {targetLanguages.map(language => (
                                            <option
                                                key={language.id}
                                                value={language.id}
                                            >
                                                {language.name}
                                                {language.native_name &&
                                                language.native_name !== language.name
                                                    ? ` (${language.native_name})`
                                                    : ''}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        )}

                        <div className="grid gap-5 sm:grid-cols-2">
                            {!isFixed && (
                                <div>
                                    <label
                                        htmlFor="calculator-quantity"
                                        className="mb-2 block text-sm font-semibold text-[#3A091A]"
                                    >
                                        {quantityTemplate
                                            ? getQuantityQuestion(
                                                  quantityTemplate.pricing_type,
                                                  quantityTemplate.unit,
                                              )
                                            : 'What quantity do you need?'}
                                    </label>

                                    <input
                                        id="calculator-quantity"
                                        type="number"
                                        min="1"
                                        max="100000"
                                        step="1"
                                        value={quantity}
                                        onChange={event => {
                                            const value = event.target.value;

                                            if (value === '') {
                                                setQuantity(1);
                                                return;
                                            }

                                            setQuantity(
                                                Math.min(
                                                    100000,
                                                    Math.max(
                                                        1,
                                                        Math.floor(Number(value)) || 1,
                                                    ),
                                                ),
                                            );
                                        }}
                                        className="w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-[#9B6877]"
                                    />

                                    <p className="mt-2 text-xs leading-5 text-gray-500">
                                        {quantityTemplate
                                            ? getQuantityHelp(
                                                  quantityTemplate.pricing_type,
                                                  quantityTemplate.unit,
                                              )
                                            : 'Enter the quantity for your project.'}
                                    </p>
                                </div>
                            )}

                            <div>
                                <label
                                    htmlFor="calculator-currency"
                                    className="mb-2 block text-sm font-semibold text-[#3A091A]"
                                >
                                    Preferred currency
                                </label>

                                <select
                                    id="calculator-currency"
                                    value={selection?.currencyId ?? ''}
                                    onChange={event =>
                                        chooseCurrency(Number(event.target.value))
                                    }
                                    className="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 outline-none transition focus:border-[#9B6877]"
                                >
                                    {availableCurrencies.map(currency => (
                                        <option
                                            key={currency.id}
                                            value={currency.id}
                                        >
                                            {currency.code}
                                            {currency.name
                                                ? ` — ${currency.name}`
                                                : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {selectedOption?.description && (
                            <p className="text-sm leading-6 text-gray-500">
                                {selectedOption.description}
                            </p>
                        )}
                    </div>

                    {/* Estimate panel */}
                    <div className="flex flex-col justify-center bg-[#3A091A] p-8 text-white md:p-10">
                        <p className="text-sm font-semibold uppercase tracking-[0.18em] text-[#E8B4B8]">
                            Your estimated price
                        </p>

                        <div className="mt-5 flex min-h-20 items-center">
                            {calculating ? (
                                <div
                                    className="h-12 w-3/4 max-w-xs animate-pulse rounded-lg bg-white/15"
                                    aria-label="Updating price estimate"
                                    role="status"
                                />
                            ) : formattedTotal ? (
                                <p className="font-serif text-4xl md:text-5xl">
                                    {formattedTotal}
                                </p>
                            ) : requiresLanguages &&
                              (!selection?.sourceLanguageId ||
                                  !selection?.targetLanguageId) ? (
                                <p className="text-lg text-white/70">
                                    Select your languages
                                </p>
                            ) : (
                                <p className="text-lg text-white/70">
                                    Custom quote required
                                </p>
                            )}
                        </div>

                        {estimate && !calculating && (
                            <div className="mt-6 space-y-3 border-t border-white/20 pt-5 text-sm">
                                <div className="flex justify-between gap-4">
                                    <span className="text-white/65">
                                        {estimate.quantity}{' '}
                                        {estimate.unit}
                                        {estimate.quantity === 1 ? '' : 's'}
                                    </span>

                                    <span>
                                        {new Intl.NumberFormat('en', {
                                            style: 'currency',
                                            currency: estimate.currency,
                                            minimumFractionDigits:
                                                selectedCurrency?.decimal_places ?? 2,
                                            maximumFractionDigits:
                                                selectedCurrency?.decimal_places ?? 2,
                                        }).format(estimate.subtotal)}
                                    </span>
                                </div>

                                {estimate.minimum_charge > estimate.subtotal && (
                                    <div className="flex justify-between gap-4">
                                        <span className="text-white/65">
                                            Minimum charge applied
                                        </span>

                                        <span>{formattedMinimumCharge}</span>
                                    </div>
                                )}

                                <div className="flex justify-between gap-4 border-t border-white/20 pt-3 font-semibold">
                                    <span>Estimated total</span>
                                    <span>{formattedTotal}</span>
                                </div>
                            </div>
                        )}

                        {!calculating && !selectedOption && (
                            <div className="mt-6 border-t border-white/20 pt-5">
                                <p className="text-sm leading-6 text-white/80">
                                    {requiresLanguages &&
                                    (!selection?.sourceLanguageId ||
                                        !selection?.targetLanguageId)
                                        ? 'Choose your source and target languages to check available pricing.'
                                        : 'We do not currently have an instant price for this selection. You can still request a personalised quotation.'}
                                </p>
                            </div>
                        )}

                        {error && (
                            <p
                                role="alert"
                                className="mt-5 text-sm text-[#F4C2C2]"
                            >
                                {error}
                            </p>
                        )}

                        <p className="mt-8 text-xs leading-5 text-white/60">
                            This is an estimate based on our current pricing.
                            Your final quote may vary depending on document
                            complexity, formatting and any additional
                            requirements.
                        </p>

                        {selection && (
                            <a
                                href={quoteRequestUrl}
                                className="mt-6 inline-flex items-center justify-center rounded-full bg-[#E8B4B8] px-7 py-3.5 text-sm font-semibold text-[#3A091A] transition hover:bg-white"
                            >
                                Request this quote
                                <span className="ml-3">→</span>
                            </a>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
