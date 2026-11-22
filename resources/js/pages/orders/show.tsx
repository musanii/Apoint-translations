import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import type { FormEvent } from 'react';
import { ArrowLeft, CheckCircle2, Clock3, ReceiptText } from 'lucide-react';

type Order = {
    id: number; reference: string; status: string; payment_status: string;
    subtotal: string; express_fee: string; certification_fee: string;
    courier_fee: string; total: string; delivery_method: string;
    currency: { code: string; symbol: string }; service: string;
    quote_reference: string;
    latest_payment: { status: string; provider_checkout_request_id: string | null; provider_initiation_started_at: string | null; failure_reason: string | null } | null;
};

export default function ShowOrder({ order, canPayOnline }: { order: Order; canPayOnline: boolean }) {
    const { data, setData, post, processing, errors } = useForm({ phone_number: '' });
    const page = usePage();
    const flash = (page.props as { flash?: { success?: string; error?: string } }).flash;
    const pageErrors = (page.props as { errors?: { payment?: string } }).errors;
    const money = (value: string) => order.currency.code + ' ' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    useEffect(() => {
        const paymentPending = order.payment_status === 'pending'
            && order.latest_payment?.status === 'pending';

        if (!paymentPending) return;

        const interval = window.setInterval(() => {
            router.reload({ only: ['order'], preserveState: true, preserveScroll: true });
        }, 4000);

        return () => window.clearInterval(interval);
    }, [order.payment_status, order.latest_payment?.status]);

    const rows: [string, string][] = [['Subtotal', order.subtotal], ['Express service', order.express_fee], ['Certification', order.certification_fee], ['Courier / delivery', order.courier_fee]];

    function pay(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        post('/orders/' + order.id + '/payments', { preserveScroll: true });
    }

    return <>
        <Head title={'Order ' + order.reference} />
        <div className="min-h-screen bg-[#FAF6F0] px-4 py-8 text-[#3A091A] sm:px-6">
            <div className="mx-auto max-w-3xl">
                <Link href="/dashboard" className="inline-flex items-center gap-2 text-sm font-semibold text-[#705B60]"><ArrowLeft size={16} /> Back to dashboard</Link>
                <div className="mt-6 rounded-3xl border border-[#3A091A]/10 bg-white p-6 shadow-sm sm:p-9">
                    <div className="flex size-12 items-center justify-center rounded-full bg-[#E7F1E8] text-[#386547]"><CheckCircle2 size={24}/></div>
                    <p className="mt-5 text-xs font-semibold tracking-[.22em] text-[#9B6877] uppercase">Quotation accepted</p>
                    <h1 className="mt-2 font-serif text-3xl">Order {order.reference}</h1>
                    <p className="mt-2 text-sm leading-6 text-[#705B60]">Your order for {order.service.toLowerCase()} has been created. We have saved the agreed quotation and delivery details.</p>
                    {flash?.success && <p role="status" className="mt-5 rounded-xl border border-[#9B6877]/25 bg-[#FAF6F0] px-4 py-3 text-sm">{flash.success}</p>}
                    {flash?.error && <p role="alert" className="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{flash.error}</p>}
                    <div className="mt-6 grid gap-3 sm:grid-cols-2">
                        <div className="rounded-xl bg-[#FAF6F0] p-4"><p className="text-xs text-[#806D70]">Order status</p><p className="mt-1 font-semibold capitalize">{order.status.replaceAll('_',' ')}</p></div>
                        <div className="rounded-xl bg-[#FAF6F0] p-4"><p className="text-xs text-[#806D70]">Payment status</p><p className="mt-1 font-semibold capitalize">{order.payment_status.replaceAll('_',' ')}</p></div>
                    </div>
                    <section className="mt-8">
                        <h2 className="font-serif text-xl font-semibold">Order total</h2>
                        <div className="mt-3 divide-y divide-[#3A091A]/10 rounded-xl border border-[#3A091A]/10 px-4">
                            {rows.map(([label,value]) => <div key={label} className="flex justify-between gap-3 py-3 text-sm"><span className="text-[#705B60]">{label}</span><strong>{money(value)}</strong></div>)}
                            <div className="flex justify-between py-4"><strong>Total due</strong><strong className="font-serif text-xl">{money(order.total)}</strong></div>
                        </div>
                    </section>
                    {order.payment_status === 'paid' ? <div className="mt-7 rounded-2xl border border-[#9B6877]/25 bg-[#E7F1E8] p-5 text-sm font-semibold text-[#386547]"><div className="flex items-center gap-2"><CheckCircle2 size={18}/> Payment received and confirmed</div><p className="mt-2 font-normal leading-6">Safaricom has confirmed your payment. Your order is now confirmed and Apoint can begin processing it.</p></div> : order.latest_payment?.status === 'pending' && (order.latest_payment.provider_checkout_request_id || order.latest_payment.provider_initiation_started_at) ? <div className="mt-7 rounded-2xl border border-[#D4AF37]/40 bg-[#FAF6F0] p-5 sm:p-6">
                        <div className="flex flex-col items-center text-center">
                            <div className="flex size-14 items-center justify-center rounded-full bg-white text-[#3A091A] shadow-sm"><Clock3 size={26}/></div>
                            <h2 className="mt-4 font-serif text-2xl">Complete your M-Pesa payment</h2>
                            <p className="mt-2 max-w-md text-sm leading-6 text-[#705B60]">{order.latest_payment.provider_checkout_request_id ? 'We have received an acknowledgement from Safaricom. Check your phone for the M-Pesa prompt and enter your PIN to approve the payment.' : 'We are checking whether Safaricom accepted your payment request. Please keep this page open and do not start another payment.'}</p>
                        </div>
                        <ol className="mt-7 space-y-5">
                            <li className="flex gap-3">
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E7F1E8] text-[#386547]"><CheckCircle2 size={18}/></span>
                                <div><p className="text-sm font-semibold">Payment request {order.latest_payment.provider_checkout_request_id ? 'accepted by Safaricom' : 'submitted'}</p><p className="mt-1 text-xs text-[#806D70]">Your checkout request is being processed.</p></div>
                            </li>
                            <li className="flex gap-3">
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-white text-[#9B6877]"><span className="size-3 animate-pulse rounded-full bg-[#D4AF37]"/></span>
                                <div><p className="text-sm font-semibold">Approve the prompt on your phone</p><p className="mt-1 text-xs text-[#806D70]">Check the phone you entered. Confirm the amount and enter your M-Pesa PIN on your phone.</p></div>
                            </li>
                            <li className="flex gap-3">
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-full border border-[#D4AF37]/50 bg-white text-[#9B6877]"><Clock3 size={17}/></span>
                                <div><p className="text-sm font-semibold">Waiting for payment confirmation</p><p className="mt-1 text-xs text-[#806D70]">This page checks for Safaricom's confirmation automatically. It will update when payment succeeds or fails.</p></div>
                            </li>
                        </ol>
                        <div className="mt-6 rounded-xl bg-white/80 p-3 text-xs leading-5 text-[#705B60]">Do not share your M-Pesa PIN with anyone, including Apoint. We will never ask you to enter it on this website. Do not submit another payment while this request is pending.</div>
                    </div> : canPayOnline ? <div className="mt-7 rounded-2xl border border-[#D4AF37]/40 bg-[#FAF6F0] p-5">
                        {order.latest_payment?.status === 'failed' && <div role="alert" className="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">The previous M-Pesa attempt was not completed. You can try again below.</div>}
                        <div className="flex items-center gap-2 font-semibold"><ReceiptText size={17}/> Pay securely with M-Pesa</div>
                        <p className="mt-2 text-sm leading-6 text-[#705B60]">Enter the Safaricom number that should receive the STK Push. Confirm the amount on your phone before entering your M-Pesa PIN.</p>
                        <form onSubmit={pay} className="mt-4 space-y-3">
                            <label className="block text-sm font-semibold" htmlFor="phone_number">M-Pesa phone number</label>
                            <input id="phone_number" inputMode="numeric" autoComplete="tel" placeholder="2547XXXXXXXX" value={data.phone_number} onChange={event => setData('phone_number', event.target.value)} className="w-full rounded-xl border border-[#3A091A]/15 bg-white px-4 py-3 text-sm outline-none focus:border-[#D4AF37]"/>
                            {errors.phone_number && <p className="text-xs text-red-700">{errors.phone_number}</p>}
                            {pageErrors?.payment && <p className="text-xs text-red-700">{pageErrors.payment}</p>}
                            <button disabled={processing || !data.phone_number.trim()} className="w-full rounded-full bg-[#3A091A] px-5 py-3.5 text-sm font-semibold text-white hover:bg-[#4A1525] disabled:opacity-50">{processing ? 'Requesting prompt…' : 'Request M-Pesa payment prompt'}</button>
                        </form>
                    </div> : <div className="mt-7 rounded-2xl border border-[#D4AF37]/40 bg-[#FAF6F0] p-5">
                        <div className="flex items-center gap-2 font-semibold"><Clock3 size={17}/> Payment is the next step</div>
                        <p className="mt-2 text-sm leading-6 text-[#705B60]">Your order is safely recorded and no payment has been taken. Online checkout is not available for this order yet. Please contact Apoint to arrange payment; we will update this page when checkout is available.</p>
                    </div>}
                    <p className="mt-6 text-center text-xs text-[#806D70]">Quotation {order.quote_reference} · Delivery preference: {order.delivery_method}</p>
                    <Link href="/dashboard" className="mt-6 inline-flex w-full items-center justify-center rounded-full bg-[#3A091A] px-5 py-3 font-semibold text-white hover:bg-[#4A1525]"><ReceiptText size={17} className="mr-2"/> View my dashboard</Link>
                </div>
            </div>
        </div>
    </>;
}
