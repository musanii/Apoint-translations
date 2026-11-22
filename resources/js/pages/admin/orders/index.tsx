import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Search, ShoppingBag } from 'lucide-react';

type OrderRow = {
    id: number;
    reference: string;
    customer: string;
    email: string;
    service: string;
    status: string;
    payment_status: string;
    total: string;
    currency: string;
    created_at: string | null;
};
type Pagination<T> = { data: T[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
type Props = { orders: Pagination<OrderRow>; filters: { status: string; search: string } };
type SharedProps = Record<string, unknown> & { errors?: { order?: string }; flash?: { success?: string } };

const statuses = [
    { value: '', label: 'All statuses' },
    { value: 'pending_payment', label: 'Awaiting payment' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

export default function AdminOrdersIndex({ orders, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const { errors, flash } = usePage<SharedProps>().props;

    function filter(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get('/admin/orders', { search, status }, { preserveState: true, preserveScroll: true });
    }

    function transition(url: string, label: string) {
        if (window.confirm(label)) router.post(url, {}, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Orders · Apoint Admin" />
            <main className="min-h-screen bg-[#FAF6F0] px-4 py-8 text-[#3A091A] sm:px-6">
                <div className="mx-auto max-w-7xl">
                    <Link href="/admin" className="text-sm font-semibold text-[#705B60] hover:underline">← Admin dashboard</Link>
                    <div className="mt-5 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-xs font-semibold tracking-[.22em] text-[#A76D78]">ADMINISTRATOR WORKSPACE</p>
                            <h1 className="mt-2 font-serif text-4xl">Orders</h1>
                            <p className="mt-2 text-sm text-[#705B60]">Track paid orders and update their fulfilment status.</p>
                        </div>
                        <span className="rounded-full border border-[#3A091A]/10 bg-white px-4 py-2 text-sm">{orders.total} orders</span>
                    </div>
                    <form onSubmit={filter} className="mt-7 flex flex-wrap gap-3 rounded-2xl border border-[#3A091A]/10 bg-white p-4">
                        <label className="flex min-w-56 flex-1 items-center gap-2 rounded-xl border border-[#3A091A]/15 px-3">
                            <Search className="size-4 text-[#806D70]" />
                            <input value={search} onChange={event => setSearch(event.target.value)} placeholder="Search order, customer or email" className="w-full py-2.5 text-sm outline-none" />
                        </label>
                        <select value={status} onChange={event => setStatus(event.target.value)} className="rounded-xl border border-[#3A091A]/15 bg-white px-3 py-2.5 text-sm">
                            {statuses.map(item => <option key={item.value} value={item.value}>{item.label}</option>)}
                        </select>
                        <button className="rounded-full bg-[#3A091A] px-5 py-2.5 text-sm font-semibold text-white">Apply filters</button>
                    </form>
                    {flash?.success && <p role="status" className="mt-4 rounded-xl border border-[#9B6877]/25 bg-white px-4 py-3 text-sm">{flash.success}</p>}
                    {errors?.order && <p role="alert" className="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{errors.order}</p>}
                    <section className="mt-5 overflow-hidden rounded-2xl border border-[#3A091A]/10 bg-white">
                        {orders.data.length === 0 ? (
                            <div className="px-6 py-16 text-center">
                                <ShoppingBag className="mx-auto size-8 text-[#A76D78]" />
                                <p className="mt-3 font-semibold">No orders found</p>
                                <p className="mt-1 text-sm text-[#705B60]">Try changing your search or status filter.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[850px] text-left text-sm">
                                    <thead className="bg-[#FAF6F0] text-xs uppercase tracking-wide text-[#806D70]">
                                        <tr><th className="px-5 py-4">Order</th><th className="px-5 py-4">Customer</th><th className="px-5 py-4">Service</th><th className="px-5 py-4">Total</th><th className="px-5 py-4">Payment</th><th className="px-5 py-4">Status / actions</th></tr>
                                    </thead>
                                    <tbody className="divide-y divide-[#3A091A]/10">
                                        {orders.data.map(order => (
                                            <tr key={order.id} className="align-top">
                                                <td className="px-5 py-4"><strong>{order.reference}</strong><span className="mt-1 block text-xs text-[#806D70]">{order.created_at ? new Date(order.created_at).toLocaleDateString() : ''}</span></td>
                                                <td className="px-5 py-4"><span className="block font-medium">{order.customer}</span><span className="text-xs text-[#806D70]">{order.email}</span></td>
                                                <td className="px-5 py-4">{order.service}</td>
                                                <td className="px-5 py-4 font-semibold">{order.currency} {Number(order.total).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                <td className="px-5 py-4 capitalize">{order.payment_status.replaceAll('_', ' ')}</td>
                                                <td className="px-5 py-4">
                                                    <span className="block font-semibold capitalize">{order.status.replaceAll('_', ' ')}</span>
                                                    <div className="mt-2 flex flex-wrap gap-2">
                                                        {order.status === 'confirmed' && <button onClick={() => transition(`/admin/orders/${order.id}/start-processing`, 'Start processing this order? The customer will receive an in-app notification.')} className="rounded-full bg-[#3A091A] px-3 py-1.5 text-xs font-semibold text-white">Start work</button>}
                                                        {order.status === 'in_progress' && <button onClick={() => transition(`/admin/orders/${order.id}/complete`, 'Mark this order as completed? The customer will receive an email.')} className="rounded-full bg-[#3A091A] px-3 py-1.5 text-xs font-semibold text-white">Complete</button>}
                                                        {order.status === 'pending_payment' && order.payment_status === 'unpaid' && <button onClick={() => transition(`/admin/orders/${order.id}/cancel`, 'Cancel this order? The customer will be notified by email.')} className="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700">Cancel</button>}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[#3A091A]/10 px-5 py-4 text-sm text-[#705B60]">
                            <span>Showing {orders.from ?? 0}–{orders.to ?? 0} of {orders.total}</span>
                            <div className="flex flex-wrap gap-1">{orders.links.map((link, index) => <button key={index} disabled={!link.url} onClick={() => link.url && router.visit(link.url)}  className={`rounded-lg px-3 py-1.5 ${link.active ? 'bg-[#3A091A] text-white' : 'bg-[#FAF6F0]'} disabled:opacity-40`}>{link.label.replace('&laquo;', '«').replace('&raquo;', '»')}</button>)}</div>
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}
