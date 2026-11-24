import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { AlertTriangle, ArrowDownToLine, CreditCard, Search, WalletCards } from 'lucide-react';
import type { FormEvent } from 'react';

type Refund = { reference: string; amount: string; status: string };
type Payment = {
    id: number;
    reference: string;
    order_reference: string | null;
    customer: string;
    customer_email: string | null;
    provider: string;
    payment_method: string | null;
    provider_reference: string | null;
    checkout_request_id: string | null;
    amount: string;
    currency: string;
    status: string;
    order_payment_status: string | null;
    paid_at: string | null;
    created_at: string | null;
    reconciliation_attempts: number;
    last_reconciled_at: string | null;
    next_reconciliation_at: string | null;
    attention_reason: string | null;
    refunds: Refund[];
};
type Pagination<T> = { data: T[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
type Props = {
    payments: Pagination<Payment>;
    stats: { total: number; pending: number; successful: number; failed: number; attention: number };
    filters: { status: string; provider: string; search: string; attention: boolean };
};

const statuses = [
    { value: '', label: 'All statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'successful', label: 'Successful' },
    { value: 'failed', label: 'Failed' },
    { value: 'refunded', label: 'Refunded' },
];

function date(value: string | null) {
    return value ? new Date(value).toLocaleString() : '—';
}

function money(amount: string, currency: string) {
    return `${currency} ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export default function PaymentReconciliation({ payments, stats, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [provider, setProvider] = useState(filters.provider);
    const [attention, setAttention] = useState(filters.attention);

    function apply(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get('/admin/payments/reconciliation', { search, status, provider, attention: attention ? 1 : undefined }, { preserveState: true, preserveScroll: true });
    }

    const exportQuery = new URLSearchParams({ ...(search ? { search } : {}), ...(status ? { status } : {}), ...(provider ? { provider } : {}) }).toString();

    return (
        <>
            <Head title="Payments & reconciliation · Apoint Admin" />
            <main className="min-h-screen bg-[#FAF6F0] px-4 py-8 text-[#3A091A] sm:px-6">
                <div className="mx-auto max-w-7xl">
                    <Link href="/admin" className="text-sm font-semibold text-[#705B60] hover:underline">← Admin dashboard</Link>
                    <div className="mt-5 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-xs font-semibold tracking-[.22em] text-[#A76D78]">FINANCE OPERATIONS</p>
                            <h1 className="mt-2 font-serif text-4xl">Payments & reconciliation</h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-[#705B60]">Review payment attempts, provider references, delayed confirmations and refunds. Provider callbacks remain the source of truth for payment status.</p>
                        </div>
                        <a href={`/admin/payments/reconciliation/export${exportQuery ? `?${exportQuery}` : ''}`} className="inline-flex items-center gap-2 rounded-full bg-[#3A091A] px-5 py-3 text-sm font-semibold text-white hover:bg-[#4A1525]">
                            <ArrowDownToLine className="size-4" /> Export CSV
                        </a>
                    </div>

                    <section className="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        {[
                            { label: 'All payment attempts', value: stats.total, icon: CreditCard },
                            { label: 'Pending', value: stats.pending, icon: WalletCards },
                            { label: 'Successful', value: stats.successful, icon: CreditCard },
                            { label: 'Failed', value: stats.failed, icon: AlertTriangle },
                            { label: 'Needs attention', value: stats.attention, icon: AlertTriangle },
                        ].map(({ label, value, icon: Icon }) => (
                            <article key={label} className="rounded-2xl border border-[#3A091A]/10 bg-white p-4">
                                <div className="flex items-center justify-between text-xs text-[#705B60]"><span>{label}</span><Icon className="size-4 text-[#A76D78]" /></div>
                                <p className="mt-3 font-serif text-3xl tabular-nums">{value.toLocaleString()}</p>
                            </article>
                        ))}
                    </section>

                    <form onSubmit={apply} className="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-[#3A091A]/10 bg-white p-4">
                        <label className="flex min-w-56 flex-1 items-center gap-2 rounded-xl border border-[#3A091A]/15 px-3">
                            <Search className="size-4 text-[#806D70]" />
                            <input value={search} onChange={event => setSearch(event.target.value)} placeholder="Payment, order, receipt or customer" className="w-full py-2.5 text-sm outline-none" />
                        </label>
                        <select value={status} onChange={event => setStatus(event.target.value)} className="rounded-xl border border-[#3A091A]/15 bg-white px-3 py-2.5 text-sm">
                            {statuses.map(item => <option key={item.value} value={item.value}>{item.label}</option>)}
                        </select>
                        <select value={provider} onChange={event => setProvider(event.target.value)} className="rounded-xl border border-[#3A091A]/15 bg-white px-3 py-2.5 text-sm">
                            <option value="">All providers</option>
                            <option value="daraja">Safaricom Daraja</option>
                            <option value="payhero">PayHero</option>
                            <option value="flutterwave">Flutterwave</option>
                        </select>
                        <label className="flex items-center gap-2 text-sm text-[#705B60]">
                            <input type="checkbox" checked={attention} onChange={event => setAttention(event.target.checked)} className="rounded border-[#3A091A]/30" />
                            Needs attention only
                        </label>
                        <button className="rounded-full bg-[#3A091A] px-5 py-2.5 text-sm font-semibold text-white">Apply filters</button>
                    </form>

                    <section className="mt-5 overflow-hidden rounded-2xl border border-[#3A091A]/10 bg-white">
                        {payments.data.length === 0 ? (
                            <div className="px-6 py-16 text-center">
                                <WalletCards className="mx-auto size-8 text-[#A76D78]" />
                                <p className="mt-3 font-semibold">No payment records found</p>
                                <p className="mt-1 text-sm text-[#705B60]">Try changing your search or filters.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[1100px] text-left text-sm">
                                    <thead className="bg-[#FAF6F0] text-xs uppercase tracking-wide text-[#806D70]">
                                        <tr>
                                            <th className="px-4 py-4">Payment / order</th>
                                            <th className="px-4 py-4">Customer</th>
                                            <th className="px-4 py-4">Provider references</th>
                                            <th className="px-4 py-4">Amount</th>
                                            <th className="px-4 py-4">Status</th>
                                            <th className="px-4 py-4">Reconciliation</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[#3A091A]/10">
                                        {payments.data.map(payment => (
                                            <tr key={payment.id} className="align-top">
                                                <td className="px-4 py-4">
                                                    <p className="font-semibold">{payment.reference}</p>
                                                    <p className="mt-1 text-xs text-[#806D70]">Order: {payment.order_reference ?? '—'}</p>
                                                    <p className="mt-1 text-xs text-[#806D70]">{date(payment.created_at)}</p>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <p className="font-medium">{payment.customer}</p>
                                                    <p className="mt-1 text-xs text-[#806D70]">{payment.customer_email ?? '—'}</p>
                                                    <p className="mt-2 text-xs capitalize text-[#806D70]">{payment.provider} · {payment.payment_method ?? '—'}</p>
                                                </td>
                                                <td className="max-w-64 break-all px-4 py-4 text-xs">
                                                    <p><span className="text-[#806D70]">Receipt:</span> {payment.provider_reference ?? 'Not attached'}</p>
                                                    <p className="mt-2"><span className="text-[#806D70]">Checkout ID:</span> {payment.checkout_request_id ?? '—'}</p>
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-4 font-semibold">{money(payment.amount, payment.currency)}</td>
                                                <td className="px-4 py-4">
                                                    <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize ${payment.status === 'successful' ? 'bg-emerald-50 text-emerald-800' : payment.status === 'failed' ? 'bg-red-50 text-red-800' : 'bg-amber-50 text-amber-800'}`}>{payment.status}</span>
                                                    <p className="mt-2 text-xs text-[#806D70]">Order: {payment.order_payment_status ?? '—'}</p>
                                                    {payment.paid_at && <p className="mt-1 text-xs text-[#806D70]">Paid {date(payment.paid_at)}</p>}
                                                    {payment.refunds.length > 0 && <div className="mt-2 space-y-1">{payment.refunds.map(refund => <p key={refund.reference} className="text-xs text-[#806D70]">Refund {refund.reference}: {money(refund.amount, payment.currency)} · {refund.status}</p>)}</div>}
                                                </td>
                                                <td className="px-4 py-4">
                                                    {payment.attention_reason ? (
                                                        <p className="max-w-52 text-xs font-semibold text-amber-800"><AlertTriangle className="mr-1 inline size-3.5" />{payment.attention_reason}</p>
                                                    ) : <p className="text-xs text-emerald-800">No current exception</p>}
                                                    <p className="mt-2 text-xs text-[#806D70]">Checks: {payment.reconciliation_attempts}</p>
                                                    <p className="mt-1 text-xs text-[#806D70]">Last: {date(payment.last_reconciled_at)}</p>
                                                    <p className="mt-1 text-xs text-[#806D70]">Next: {date(payment.next_reconciliation_at)}</p>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[#3A091A]/10 px-5 py-4 text-sm text-[#705B60]">
                            <span>Showing {payments.from ?? 0}–{payments.to ?? 0} of {payments.total}</span>
                            <div className="flex flex-wrap gap-1">
                                {payments.links.map((link, index) => <button key={index} disabled={!link.url} onClick={() => link.url && router.visit(link.url)} className={`rounded-lg px-3 py-1.5 ${link.active ? 'bg-[#3A091A] text-white' : 'bg-[#FAF6F0]'} disabled:opacity-40`}>{link.label.replace('&laquo;', '«').replace('&raquo;', '»')}</button>)}
                            </div>
                        </div>
                    </section>
                    <p className="mt-4 text-xs leading-5 text-[#806D70]">Automatic Daraja verification runs in the background. A pending payment is not treated as failed simply because a callback is delayed. This screen does not provide manual payment approval or edit provider-confirmed amounts.</p>
                </div>
            </main>
        </>
    );
}
