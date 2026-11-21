import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Clock3, ReceiptText } from 'lucide-react';

type Order = {
    reference: string; status: string; payment_status: string; subtotal: string;
    express_fee: string; certification_fee: string; courier_fee: string; total: string;
    delivery_method: string; currency: { code: string; symbol: string };
    service: string; quote_reference: string;
};

export default function ShowOrder({ order }: { order: Order }) {
    const money = (value: string) => order.currency.symbol + ' ' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const rows: [string, string][] = [['Subtotal', order.subtotal], ['Express service', order.express_fee], ['Certification', order.certification_fee], ['Courier / delivery', order.courier_fee]];
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
                    <div className="mt-7 rounded-2xl border border-[#D4AF37]/40 bg-[#FAF6F0] p-5">
                        <div className="flex items-center gap-2 font-semibold"><Clock3 size={17}/> Payment is the next step</div>
                        <p className="mt-2 text-sm leading-6 text-[#705B60]">Your order is safely recorded and no payment has been taken. Online checkout is not enabled on this order yet. Please contact Apoint to arrange payment; we will update this page when checkout is available.</p>
                    </div>
                    <p className="mt-6 text-center text-xs text-[#806D70]">Quotation {order.quote_reference} · Delivery preference: {order.delivery_method}</p>
                    <Link href="/dashboard" className="mt-6 inline-flex w-full items-center justify-center rounded-full bg-[#3A091A] px-5 py-3 font-semibold text-white hover:bg-[#4A1525]"><ReceiptText size={17} className="mr-2"/> View my dashboard</Link>
                </div>
            </div>
        </div>
    </>;
}
