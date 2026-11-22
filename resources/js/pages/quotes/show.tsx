import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Clock3, FileText, XCircle } from 'lucide-react';
import type { FormEvent } from 'react';

type Quote = {
    id: number; reference: string; pricing_type: string; quantity: string | null;
    unit: string | null; rate: string | null; subtotal: string; express_fee: string;
    certification_fee: string; courier_fee: string; total: string;
    customer_notes: string | null; status: string; sent_at: string | null;
    valid_until: string | null; currency: { code: string; symbol: string };
    request: { id: number; service: string; source_language: string | null; target_language: string | null };
};

export default function ShowQuote({ quote }: { quote: Quote }) {
    const isActionable = quote.status === 'sent';
    function decide(event: FormEvent<HTMLFormElement>, action: 'accept' | 'reject') {
        event.preventDefault();
        router.post('/quotes/' + quote.id + '/' + action);
    }
    const money = (value: string) => quote.currency.code + ' ' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    return <>
        <Head title={'Quotation ' + quote.reference} />
        <div className="min-h-screen bg-[#FAF6F0] px-4 py-8 text-[#3A091A] sm:px-6">
            <div className="mx-auto max-w-4xl">
                <Link href="/dashboard" className="inline-flex items-center gap-2 text-sm font-semibold text-[#705B60] hover:text-[#3A091A]"><ArrowLeft size={16} /> Back to dashboard</Link>
                <div className="mt-6 overflow-hidden rounded-3xl border border-[#3A091A]/10 bg-white shadow-sm">
                    <div className="bg-[#3A091A] px-6 py-8 text-white sm:px-9">
                        <p className="text-xs font-semibold tracking-[.22em] text-[#E8B4B8] uppercase">Your quotation</p>
                        <h1 className="mt-2 font-serif text-3xl sm:text-4xl">{quote.reference}</h1>
                        <p className="mt-2 text-sm text-white/75">{quote.request.service} · {quote.request.source_language ?? 'Language not specified'} → {quote.request.target_language ?? 'Language not specified'}</p>
                        <p className="mt-6 text-sm text-white/75">Total quoted amount</p>
                        <p className="mt-1 font-serif text-3xl font-semibold">{money(quote.total)}</p>
                    </div>
                    <div className="p-6 sm:p-9">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="inline-flex items-center gap-2 rounded-full bg-[#E7F1E8] px-3 py-1.5 text-sm font-semibold capitalize text-[#386547]"><FileText size={15} /> {quote.status.replaceAll('_', ' ')}</div>
                            {quote.valid_until && <p className="inline-flex items-center gap-2 text-sm text-[#705B60]"><Clock3 size={15} /> Valid until {new Date(quote.valid_until).toLocaleDateString()}</p>}
                        </div>
                        <section className="mt-8">
                            <h2 className="font-serif text-2xl font-semibold">Price breakdown</h2>
                            <div className="mt-4 divide-y divide-[#3A091A]/10 rounded-2xl border border-[#3A091A]/10 px-4">
                                <div className="flex justify-between gap-4 py-3 text-sm"><span className="text-[#705B60]">{quote.pricing_type === 'fixed' ? 'Fixed service fee' : Number(quote.quantity).toLocaleString() + ' ' + (quote.unit ?? '') + ' × ' + money(quote.rate ?? '0') + ' per ' + (quote.unit ?? 'unit')}</span><strong>{money(quote.subtotal)}</strong></div>
                                <div className="flex justify-between gap-4 py-3 text-sm"><span className="text-[#705B60]">Express service</span><strong>{money(quote.express_fee)}</strong></div>
                                <div className="flex justify-between gap-4 py-3 text-sm"><span className="text-[#705B60]">Certification</span><strong>{money(quote.certification_fee)}</strong></div>
                                <div className="flex justify-between gap-4 py-3 text-sm"><span className="text-[#705B60]">Courier / physical delivery</span><strong>{money(quote.courier_fee)}</strong></div>
                                <div className="flex justify-between gap-4 py-4 text-base"><strong>Total</strong><strong className="font-serif text-xl">{money(quote.total)}</strong></div>
                            </div>
                        </section>
                        {quote.customer_notes && <section className="mt-7 rounded-2xl bg-[#FAF6F0] p-5"><h2 className="font-semibold">A note from Apoint</h2><p className="mt-2 whitespace-pre-wrap text-sm leading-7 text-[#705B60]">{quote.customer_notes}</p></section>}
                        {isActionable ? <div className="mt-8 rounded-2xl border border-[#D4AF37]/40 bg-[#FAF6F0] p-5 sm:p-6">
                            <h2 className="font-serif text-xl font-semibold">Would you like to proceed?</h2>
                            <p className="mt-2 text-sm leading-6 text-[#705B60]">Accepting creates an order using this quotation. You will be able to review the order before making payment.</p>
                            <div className="mt-5 flex flex-col gap-3 sm:flex-row">
                                <form className="flex-1" onSubmit={(event) => decide(event, 'accept')}><button className="inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#3A091A] px-5 py-3.5 text-sm font-semibold text-white hover:bg-[#4A1525]"><CheckCircle2 size={17} /> Accept quotation</button></form>
                                <form className="flex-1" onSubmit={(event) => decide(event, 'reject')}><button className="inline-flex w-full items-center justify-center gap-2 rounded-full border border-[#3A091A]/20 bg-white px-5 py-3.5 text-sm font-semibold text-[#3A091A] hover:bg-[#F8F0F1]"><XCircle size={17} /> Decline quotation</button></form>
                            </div>
                        </div> : <p className="mt-8 rounded-xl bg-[#FAF6F0] p-4 text-sm text-[#705B60]">This quotation is no longer awaiting a decision. You can see its status in your dashboard.</p>}
                    </div>
                </div>
            </div>
        </div>
    </>;
}
