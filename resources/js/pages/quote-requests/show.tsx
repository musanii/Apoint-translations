import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';

type Document = {
    id: number;
    original_name: string;
    extension: string;
    size: number;
    created_at: string;
};

type QuoteRequestData = {
    id: number;
    status: string;
    quantity: number | null;
    unit: string | null;
    delivery_date: string | null;
    instructions: string | null;
    delivery_method: string;
    recipient_name: string | null;
    recipient_phone: string | null;
    delivery_country: string | null;
    delivery_address: string | null;
    delivery_postal_code: string | null;
    service: { id: number; name: string };
    source_language: { id: number; name: string } | null;
    target_language: { id: number; name: string } | null;
    currency: { id: number; code: string; symbol: string };
    documents: Document[];
    created_at: string;
};

type Props = {
    quoteRequest: QuoteRequestData;
};

export default function ShowQuoteRequest({ quoteRequest }: Props) {
    const page = usePage();
    const flash = (page.props as { flash?: { success?: string } }).flash;

    const { data, setData, post, processing, errors, reset } = useForm<{
        document: File | null;
    }>({
        document: null,
    });

    function uploadDocument(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        post(`/quote-requests/${quoteRequest.id}/documents`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset('document'),
        });
    }

    function formatSize(bytes: number): string {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    const detailClass = 'text-sm text-[#705B60]';

    return (
        <>
            <Head title="Quote Request Submitted" />

            <div className="min-h-screen bg-[#FAF6F0] text-[#3A091A]">
                <header className="border-b border-[#3A091A]/10">
                    <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
                        <a href="/" className="flex items-center gap-3">
                            <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#3A091A] font-serif text-2xl text-[#E8B4B8]">A</span>
                            <span>
                                <span className="block font-serif text-xl font-semibold">Apoint</span>
                                <span className="block text-[9px] font-semibold tracking-[0.25em] text-[#4A1525]">TRANSLATIONS</span>
                            </span>
                        </a>
                        <a href="/" className="text-sm font-medium text-[#705B60] hover:text-[#3A091A]">Back to website</a>
                    </div>
                </header>

                <main className="px-6 py-12 md:py-16">
                    <div className="mx-auto max-w-4xl">
                        <div className="rounded-3xl bg-white p-7 shadow-xl shadow-[#3A091A]/5 md:p-10">
                            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-[#E8B4B8]/35 text-2xl text-[#3A091A]" aria-hidden="true">✓</div>
                            <p className="mt-6 text-xs font-semibold uppercase tracking-[0.22em] text-[#9B6877]">Request received</p>
                            <h1 className="mt-3 font-serif text-4xl font-medium">Thank you for contacting Apoint.</h1>
                            <p className="mt-4 max-w-2xl text-sm leading-7 text-[#705B60]">
                                Your request has been saved. Our team will review the details and prepare a quotation. You can securely upload the documents you need translated below.
                            </p>

                            {flash?.success && (
                                <p role="status" className="mt-6 rounded-xl border border-[#9B6877]/25 bg-[#FAF6F0] px-4 py-3 text-sm text-[#3A091A]">
                                    {flash.success}
                                </p>
                            )}

                            <div className="mt-8 grid gap-5 rounded-2xl bg-[#FAF6F0] p-5 sm:grid-cols-2">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-[#9B6877]">Service</p>
                                    <p className="mt-1 text-sm font-semibold">{quoteRequest.service.name}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-[#9B6877]">Preferred currency</p>
                                    <p className="mt-1 text-sm font-semibold">{quoteRequest.currency.code}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-[#9B6877]">Language pair</p>
                                    <p className="mt-1 text-sm font-semibold">
                                        {quoteRequest.source_language?.name ?? 'Not specified'}
                                        {' → '}
                                        {quoteRequest.target_language?.name ?? 'Not specified'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-[#9B6877]">Quantity</p>
                                    <p className="mt-1 text-sm font-semibold">
                                        {quoteRequest.quantity
                                            ? `${quoteRequest.quantity}${quoteRequest.unit ? ` ${quoteRequest.unit}` : ''}`
                                            : 'Not specified'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-[#9B6877]">Delivery preference</p>
                                    <p className="mt-1 text-sm font-semibold capitalize">{quoteRequest.delivery_method}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-[#9B6877]">Status</p>
                                    <p className="mt-1 text-sm font-semibold capitalize">{quoteRequest.status}</p>
                                </div>
                            </div>

                            <section className="mt-10 border-t border-[#3A091A]/10 pt-8">
                                <h2 className="font-serif text-2xl font-semibold">Upload your documents</h2>
                                <p className="mt-2 text-sm leading-6 text-[#705B60]">
                                    Upload the files related to this request. Accepted file types and size limits are enforced securely.
                                </p>

                                <form onSubmit={uploadDocument} className="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end">
                                    <div className="min-w-0 flex-1">
                                        <label htmlFor="document" className="mb-2 block text-sm font-semibold">Choose a document</label>
                                        <input
                                            id="document"
                                            type="file"
                                            accept=".pdf,.docx,.jpg,.jpeg,.png,.xlsx,.csv,.pptx,.zip"
                                            onChange={event => setData('document', event.target.files?.[0] ?? null)}
                                            className="block w-full rounded-xl border border-[#3A091A]/15 bg-white px-4 py-3 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-[#E8B4B8] file:px-4 file:py-2 file:text-xs file:font-semibold file:text-[#3A091A]"
                                        />
                                        <InputError message={errors.document} className="mt-2" />
                                    </div>
                                    <button
                                        type="submit"
                                        disabled={processing || !data.document}
                                        className="rounded-full bg-[#3A091A] px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-[#4A1525] disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {processing ? 'Uploading…' : 'Upload document'}
                                    </button>
                                </form>

                                {quoteRequest.documents.length > 0 && (
                                    <div className="mt-7">
                                        <h3 className="text-sm font-semibold">Uploaded documents</h3>
                                        <ul className="mt-3 divide-y divide-[#3A091A]/10 rounded-xl border border-[#3A091A]/10">
                                            {quoteRequest.documents.map(document => (
                                                <li key={document.id} className="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <span className="break-all text-sm font-medium">{document.original_name}</span>
                                                    <span className={detailClass}>{document.extension.toUpperCase()} · {formatSize(document.size)}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </section>

                            {quoteRequest.instructions && (
                                <section className="mt-8 border-t border-[#3A091A]/10 pt-7">
                                    <h2 className="text-sm font-semibold">Your instructions</h2>
                                    <p className="mt-2 whitespace-pre-wrap text-sm leading-7 text-[#705B60]">{quoteRequest.instructions}</p>
                                </section>
                            )}
                        </div>

                        <p className="mt-6 text-center text-xs leading-6 text-[#806D70]">
                            Your documents are stored privately and are only associated with your quote request.
                        </p>
                    </div>
                </main>
            </div>
        </>
    );
}
