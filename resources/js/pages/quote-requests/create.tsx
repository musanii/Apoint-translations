import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';

type ServiceOption = {
    id: number;
    name: string;
    slug: string;
    quantity_unit: string | null;
    quantity_is_fixed: boolean;
};

type LanguageOption = {
    id: number;
    name: string;
    code: string;
    native_name: string | null;
};

type CurrencyOption = {
    id: number;
    code: string;
    name: string;
    symbol: string;
    decimal_places: number;
    is_default: boolean;
};

type InitialSelection = {
    service_id: number | null;
    source_language_id: number | null;
    target_language_id: number | null;
    currency_id: number | null;
    quantity: number | null;
};

type Props = {
    services: ServiceOption[];
    languages: LanguageOption[];
    currencies: CurrencyOption[];
    initial: InitialSelection;
};

type FormData = {
    service_id: number | null;
    source_language_id: number | null;
    target_language_id: number | null;
    currency_id: number | null;
    quantity: number | null;
    delivery_date: string;
    instructions: string;
    delivery_method: 'digital' | 'physical';
    recipient_name: string;
    recipient_phone: string;
    delivery_country: string;
    delivery_address: string;
    delivery_postal_code: string;
};

export default function CreateQuoteRequest({
    services,
    languages,
    currencies,
    initial,
}: Props) {
    const defaultCurrency =
        currencies.find((currency) => currency.is_default) ?? currencies[0];

    const localToday = new Date();
    const minimumDeliveryDate = new Date(
        localToday.getTime() - localToday.getTimezoneOffset() * 60_000,
    )
        .toISOString()
        .slice(0, 10);

    const { data, setData, post, processing, errors } = useForm<FormData>({
        service_id: initial.service_id ?? services[0]?.id ?? null,
        source_language_id: initial.source_language_id,
        target_language_id: initial.target_language_id,
        currency_id: initial.currency_id ?? defaultCurrency?.id ?? null,
        quantity: initial.quantity,
        delivery_date: '',
        instructions: '',
        delivery_method: 'digital',
        recipient_name: '',
        recipient_phone: '',
        delivery_country: '',
        delivery_address: '',
        delivery_postal_code: '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        post('/quote-requests', {
            preserveScroll: true,
        });
    }

    const selectedService = services.find(
        (service) => service.id === data.service_id,
    );
    const quantityUnit = selectedService?.quantity_unit ?? null;
    const quantityIsFixed = selectedService?.quantity_is_fixed ?? false;
    const quantityLabel = quantityUnit === 'word'
        ? 'Approximate word count (optional)'
        : quantityUnit === 'page'
          ? 'Approximate page count (optional)'
          : quantityUnit
            ? `Approximate ${quantityUnit} count (optional)`
            : 'Approximate quantity (optional)';
    const quantityHelp = quantityUnit === 'word'
        ? 'A rough word count is enough. You can find it in most document editors.'
        : quantityUnit === 'page'
          ? 'Enter the approximate number of pages to be translated.'
          : quantityUnit
            ? `Enter an approximate number of ${quantityUnit}s if known.`
            : 'If you are unsure, leave this blank and upload your documents after submitting.';

    const fieldClass =
        'w-full rounded-xl border border-[#3A091A]/15 bg-white px-4 py-3 text-sm text-[#3A091A] outline-none transition placeholder:text-[#806D70]/60 focus:border-[#9B6877] focus:ring-2 focus:ring-[#E8B4B8]/40';

    const labelClass = 'mb-2 block text-sm font-semibold text-[#3A091A]';

    return (
        <>
            <Head title="Request a Quote" />

            <div className="min-h-screen bg-[#FAF6F0] text-[#3A091A]">
                <header className="border-b border-[#3A091A]/10 bg-[#FAF6F0]">
                    <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
                        <a href="/" className="flex items-center gap-3">
                            <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#3A091A] font-serif text-2xl text-[#E8B4B8]">
                                A
                            </span>
                            <span>
                                <span className="block font-serif text-xl font-semibold">
                                    Apoint
                                </span>
                                <span className="block text-[9px] font-semibold tracking-[0.25em] text-[#4A1525]">
                                    TRANSLATIONS
                                </span>
                            </span>
                        </a>

                        <div className="flex items-center gap-4">
                            <a href="/dashboard" className="text-sm font-semibold text-[#3A091A] transition hover:text-[#8B5362]">
                                ← My dashboard
                            </a>
                            <a
                                href="/"
                                className="text-sm font-medium text-[#705B60] transition hover:text-[#3A091A]"
                            >
                                Back to website
                            </a>
                        </div>
                    </div>
                </header>

                <main className="px-6 py-12 md:py-16">
                    <div className="mx-auto max-w-4xl">
                        <div className="mb-9 max-w-2xl">
                            <p className="text-xs font-semibold tracking-[0.22em] text-[#9B6877] uppercase">
                                Tell us about your project
                            </p>
                            <h1 className="mt-3 font-serif text-4xl font-medium md:text-5xl">
                                Request a quote
                            </h1>
                            <p className="mt-4 text-sm leading-7 text-[#705B60] md:text-base">
                                Share a few details and our team will review
                                your requirements. You can upload your documents
                                securely after submitting this request.
                            </p>
                        </div>

                        <form
                            onSubmit={submit}
                            className="overflow-hidden rounded-3xl bg-white shadow-xl shadow-[#3A091A]/5"
                        >
                            <div className="space-y-8 p-6 md:p-10">
                                <section>
                                    <h2 className="font-serif text-2xl font-semibold">
                                        Project details
                                    </h2>
                                    <p className="mt-1 text-sm text-[#806D70]">
                                        What would you like us to help with?
                                    </p>

                                    <div className="mt-6 grid gap-5 md:grid-cols-2">
                                        <div className="md:col-span-2">
                                            <label
                                                htmlFor="service_id"
                                                className={labelClass}
                                            >
                                                Service *
                                            </label>
                                            <select
                                                id="service_id"
                                                value={data.service_id ?? ''}
                                                onChange={(event) =>
                                                    setData(
                                                        'service_id',
                                                        event.target.value
                                                            ? Number(
                                                                  event.target
                                                                      .value,
                                                              )
                                                            : null,
                                                    )
                                                }
                                                className={fieldClass}
                                                required
                                            >
                                                <option value="">
                                                    Choose a service
                                                </option>
                                                {services.map((service) => (
                                                    <option
                                                        key={service.id}
                                                        value={service.id}
                                                    >
                                                        {service.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.service_id}
                                                className="mt-2"
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="source_language_id"
                                                className={labelClass}
                                            >
                                                Source language
                                            </label>
                                            <select
                                                id="source_language_id"
                                                value={
                                                    data.source_language_id ??
                                                    ''
                                                }
                                                onChange={(event) =>
                                                    setData(
                                                        'source_language_id',
                                                        event.target.value
                                                            ? Number(
                                                                  event.target
                                                                      .value,
                                                              )
                                                            : null,
                                                    )
                                                }
                                                className={fieldClass}
                                            >
                                                <option value="">
                                                    Not applicable / not sure
                                                </option>
                                                {languages.map((language) => (
                                                    <option
                                                        key={language.id}
                                                        value={language.id}
                                                    >
                                                        {language.name}
                                                        {language.native_name &&
                                                        language.native_name !==
                                                            language.name
                                                            ? ` (${language.native_name})`
                                                            : ''}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.source_language_id
                                                }
                                                className="mt-2"
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="target_language_id"
                                                className={labelClass}
                                            >
                                                Target language
                                            </label>
                                            <select
                                                id="target_language_id"
                                                value={
                                                    data.target_language_id ??
                                                    ''
                                                }
                                                onChange={(event) =>
                                                    setData(
                                                        'target_language_id',
                                                        event.target.value
                                                            ? Number(
                                                                  event.target
                                                                      .value,
                                                              )
                                                            : null,
                                                    )
                                                }
                                                className={fieldClass}
                                            >
                                                <option value="">
                                                    Not applicable / not sure
                                                </option>
                                                {languages.map((language) => (
                                                    <option
                                                        key={language.id}
                                                        value={language.id}
                                                    >
                                                        {language.name}
                                                        {language.native_name &&
                                                        language.native_name !==
                                                            language.name
                                                            ? ` (${language.native_name})`
                                                            : ''}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.target_language_id
                                                }
                                                className="mt-2"
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="currency_id"
                                                className={labelClass}
                                            >
                                                Preferred currency *
                                            </label>
                                            <select
                                                id="currency_id"
                                                value={data.currency_id ?? ''}
                                                onChange={(event) =>
                                                    setData(
                                                        'currency_id',
                                                        event.target.value
                                                            ? Number(
                                                                  event.target
                                                                      .value,
                                                              )
                                                            : null,
                                                    )
                                                }
                                                className={fieldClass}
                                                required
                                            >
                                                <option value="">
                                                    Choose a currency
                                                </option>
                                                {currencies.map((currency) => (
                                                    <option
                                                        key={currency.id}
                                                        value={currency.id}
                                                    >
                                                        {currency.code} —{' '}
                                                        {currency.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.currency_id}
                                                className="mt-2"
                                            />
                                        </div>

                                        {!quantityIsFixed && (
                                        <div>
                                            <label
                                                htmlFor="quantity"
                                                className={labelClass}
                                            >
                                                {quantityLabel}
                                            </label>
                                            <input
                                                id="quantity"
                                                type="number"
                                                min="1"
                                                max="100000"
                                                step="1"
                                                value={data.quantity ?? ''}
                                                onChange={(event) =>
                                                    setData(
                                                        'quantity',
                                                        event.target.value
                                                            ? Number(
                                                                  event.target
                                                                      .value,
                                                              )
                                                            : null,
                                                    )
                                                }
                                                placeholder={
                                                    quantityUnit === 'word'
                                                        ? 'e.g. 1,200'
                                                        : quantityUnit === 'page'
                                                          ? 'e.g. 8'
                                                          : 'Enter an estimate'
                                                }
                                                className={fieldClass}
                                            />
                                            <p className="mt-2 text-xs leading-5 text-[#806D70]">
                                                {quantityHelp}
                                            </p>
                                            <InputError
                                                message={errors.quantity}
                                                className="mt-2"
                                            />
                                        </div>
                                        )}

                                        <div>
                                            <label
                                                htmlFor="delivery_date"
                                                className={labelClass}
                                            >
                                                When do you need it?
                                            </label>
                                            <input
                                                id="delivery_date"
                                                type="date"
                                                min={minimumDeliveryDate}
                                                value={data.delivery_date}
                                                onChange={(event) =>
                                                    setData(
                                                        'delivery_date',
                                                        event.target.value,
                                                    )
                                                }
                                                className={fieldClass}
                                            />
                                            <InputError
                                                message={errors.delivery_date}
                                                className="mt-2"
                                            />
                                        </div>

                                        <div className="md:col-span-2">
                                            <label
                                                htmlFor="instructions"
                                                className={labelClass}
                                            >
                                                Additional instructions
                                            </label>
                                            <textarea
                                                id="instructions"
                                                rows={5}
                                                maxLength={5000}
                                                value={data.instructions}
                                                onChange={(event) =>
                                                    setData(
                                                        'instructions',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Tell us about formatting, certification, intended use, or any other requirements."
                                                className={fieldClass}
                                            />
                                            <InputError
                                                message={errors.instructions}
                                                className="mt-2"
                                            />
                                        </div>
                                    </div>
                                </section>

                                <div className="border-t border-[#3A091A]/10" />

                                <section>
                                    <h2 className="font-serif text-2xl font-semibold">
                                        Delivery preference
                                    </h2>
                                    <p className="mt-1 text-sm text-[#806D70]">
                                        Choose how you would like to receive the
                                        completed work.
                                    </p>

                                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                        <label
                                            className={`flex cursor-pointer gap-3 rounded-2xl border p-4 transition ${data.delivery_method === 'digital' ? 'border-[#9B6877] bg-[#FAF6F0]' : 'border-[#3A091A]/15'}`}
                                        >
                                            <input
                                                type="radio"
                                                name="delivery_method"
                                                value="digital"
                                                checked={
                                                    data.delivery_method ===
                                                    'digital'
                                                }
                                                onChange={() =>
                                                    setData(
                                                        'delivery_method',
                                                        'digital',
                                                    )
                                                }
                                                className="mt-1 accent-[#3A091A]"
                                            />
                                            <span>
                                                <span className="block text-sm font-semibold">
                                                    Digital delivery
                                                </span>
                                                <span className="mt-1 block text-xs leading-5 text-[#806D70]">
                                                    Receive your completed
                                                    documents electronically.
                                                </span>
                                            </span>
                                        </label>

                                        <label
                                            className={`flex cursor-pointer gap-3 rounded-2xl border p-4 transition ${data.delivery_method === 'physical' ? 'border-[#9B6877] bg-[#FAF6F0]' : 'border-[#3A091A]/15'}`}
                                        >
                                            <input
                                                type="radio"
                                                name="delivery_method"
                                                value="physical"
                                                checked={
                                                    data.delivery_method ===
                                                    'physical'
                                                }
                                                onChange={() =>
                                                    setData(
                                                        'delivery_method',
                                                        'physical',
                                                    )
                                                }
                                                className="mt-1 accent-[#3A091A]"
                                            />
                                            <span>
                                                <span className="block text-sm font-semibold">
                                                    Physical delivery
                                                </span>
                                                <span className="mt-1 block text-xs leading-5 text-[#806D70]">
                                                    For stamped originals or
                                                    printed documents.
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                    <InputError
                                        message={errors.delivery_method}
                                        className="mt-2"
                                    />

                                    {data.delivery_method === 'physical' && (
                                        <div className="mt-6 grid gap-5 rounded-2xl bg-[#FAF6F0] p-5 md:grid-cols-2">
                                            <div>
                                                <label
                                                    htmlFor="recipient_name"
                                                    className={labelClass}
                                                >
                                                    Recipient name *
                                                </label>
                                                <input
                                                    id="recipient_name"
                                                    value={data.recipient_name}
                                                    onChange={(event) =>
                                                        setData(
                                                            'recipient_name',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={fieldClass}
                                                />
                                                <InputError
                                                    message={
                                                        errors.recipient_name
                                                    }
                                                    className="mt-2"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    htmlFor="recipient_phone"
                                                    className={labelClass}
                                                >
                                                    Recipient phone *
                                                </label>
                                                <input
                                                    id="recipient_phone"
                                                    type="tel"
                                                    value={data.recipient_phone}
                                                    onChange={(event) =>
                                                        setData(
                                                            'recipient_phone',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={fieldClass}
                                                />
                                                <InputError
                                                    message={
                                                        errors.recipient_phone
                                                    }
                                                    className="mt-2"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    htmlFor="delivery_country"
                                                    className={labelClass}
                                                >
                                                    Country *
                                                </label>
                                                <input
                                                    id="delivery_country"
                                                    value={
                                                        data.delivery_country
                                                    }
                                                    onChange={(event) =>
                                                        setData(
                                                            'delivery_country',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={fieldClass}
                                                />
                                                <InputError
                                                    message={
                                                        errors.delivery_country
                                                    }
                                                    className="mt-2"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    htmlFor="delivery_postal_code"
                                                    className={labelClass}
                                                >
                                                    Postal code
                                                </label>
                                                <input
                                                    id="delivery_postal_code"
                                                    value={
                                                        data.delivery_postal_code
                                                    }
                                                    onChange={(event) =>
                                                        setData(
                                                            'delivery_postal_code',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={fieldClass}
                                                />
                                                <InputError
                                                    message={
                                                        errors.delivery_postal_code
                                                    }
                                                    className="mt-2"
                                                />
                                            </div>
                                            <div className="md:col-span-2">
                                                <label
                                                    htmlFor="delivery_address"
                                                    className={labelClass}
                                                >
                                                    Full delivery address *
                                                </label>
                                                <textarea
                                                    id="delivery_address"
                                                    rows={3}
                                                    maxLength={255}
                                                    value={
                                                        data.delivery_address
                                                    }
                                                    onChange={(event) =>
                                                        setData(
                                                            'delivery_address',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={fieldClass}
                                                />
                                                <InputError
                                                    message={
                                                        errors.delivery_address
                                                    }
                                                    className="mt-2"
                                                />
                                            </div>
                                        </div>
                                    )}
                                </section>
                            </div>

                            <div className="flex flex-col gap-4 bg-[#3A091A] px-6 py-6 text-white md:flex-row md:items-center md:justify-between md:px-10">
                                <p className="max-w-xl text-xs leading-5 text-white/65">
                                    Submitting this form does not commit you to
                                    a purchase. Our team will review your
                                    requirements and prepare a quotation.
                                </p>
                                <button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        services.length === 0 ||
                                        currencies.length === 0
                                    }
                                    className="inline-flex shrink-0 items-center justify-center rounded-full bg-[#E8B4B8] px-8 py-4 text-sm font-semibold text-[#3A091A] transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {processing
                                        ? 'Submitting…'
                                        : 'Submit quote request'}
                                    {!processing && (
                                        <span className="ml-3">→</span>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </main>
            </div>
        </>
    );
}
