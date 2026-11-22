import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    ClipboardList,
    Clock3,
    FileCheck2,
    PackageCheck,
    Plus,
    ReceiptText,
} from 'lucide-react';
import { dashboard } from '@/routes';

type Quote = {
    id: number;
    reference: string;
    status: 'sent' | 'accepted' | 'rejected' | 'expired';
    total: string;
    currency: { code: string; symbol: string };
    valid_until: string | null;
};

type RequestItem = {
    id: number;
    status: string;
    created_at: string;
    documents_count: number;
    service: { name: string };
    currency: { code: string; symbol: string };
    quotes: Quote[];
};

type OrderItem = {
    id: number;
    reference: string;
    status: string;
    payment_status: string;
    total: string;
    currency: { code: string; symbol: string };
    quote: { quote_request: { service: { name: string } } };
};

type Props = {
    summary: {
        requests: number;
        quotesAwaitingDecision: number;
        activeOrders: number;
        completedOrders: number;
    };
    recentRequests: RequestItem[];
    recentOrders: OrderItem[];
};

const requestStatus: Record<string, { label: string; className: string }> = {
    pending: { label: 'Received', className: 'bg-[#F9E9D8] text-[#8B4D24]' },
    under_review: { label: 'In review', className: 'bg-[#F5E8F0] text-[#7C4266]' },
    quote_draft: { label: 'Preparing quotation', className: 'bg-[#F5E8F0] text-[#7C4266]' },
    quote_sent: { label: 'Quotation ready', className: 'bg-[#E7F1E8] text-[#386547]' },
};

function money(value: string, code: string) {
    return `${code} ${Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function humanize(value: string) {
    return value.replaceAll('_', ' ');
}

export default function Dashboard({ summary, recentRequests, recentOrders }: Props) {
    const cards = [
        { label: 'My requests', value: summary.requests, icon: ClipboardList },
        { label: 'Quotes to review', value: summary.quotesAwaitingDecision, icon: FileCheck2 },
        { label: 'Active orders', value: summary.activeOrders, icon: Clock3 },
        { label: 'Completed orders', value: summary.completedOrders, icon: PackageCheck },
    ];

    return (
        <>
            <Head title="My dashboard" />
            <div className="min-h-full bg-[#FAF6F0] px-4 py-7 text-[#3A091A] sm:px-6 lg:px-8">
                <div className="mx-auto max-w-6xl">
                    <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.22em] text-[#9B6877] uppercase">
                                Your Apoint account
                            </p>
                            <h1 className="mt-2 font-serif text-3xl font-medium sm:text-4xl">
                                My dashboard
                            </h1>
                            <p className="mt-2 max-w-xl text-sm leading-6 text-[#705B60]">
                                Keep track of your translation requests, quotations and orders in one place.
                            </p>
                        </div>
                        <Link
                            href="/quote-requests/create"
                            className="inline-flex items-center justify-center gap-2 rounded-full bg-[#3A091A] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#4A1525]"
                        >
                            <Plus size={17} /> New quote request
                        </Link>
                    </div>

                    <div className="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {cards.map(({ label, value, icon: Icon }) => (
                            <div key={label} className="rounded-2xl border border-[#3A091A]/10 bg-white p-5 shadow-sm shadow-[#3A091A]/5">
                                <div className="flex items-center justify-between text-sm text-[#705B60]">
                                    {label}
                                    <Icon className="size-5 text-[#A76D78]" />
                                </div>
                                <p className="mt-3 font-serif text-3xl font-semibold tabular-nums">{value}</p>
                            </div>
                        ))}
                    </div>

                    <section id="requests" className="mt-7 overflow-hidden rounded-2xl border border-[#3A091A]/10 bg-white shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[#3A091A]/10 px-5 py-4 sm:px-6">
                            <div>
                                <h2 className="font-serif text-xl font-semibold">Recent quote requests</h2>
                                <p className="mt-1 text-xs text-[#806D70]">Your latest submissions and their progress</p>
                            </div>
                            <Link href="/quote-requests/create" className="text-sm font-semibold text-[#8B5362] hover:text-[#3A091A]">
                                Submit another request <ArrowRight className="ml-1 inline size-4" />
                            </Link>
                        </div>

                        {recentRequests.length === 0 ? (
                            <div className="px-6 py-12 text-center">
                                <ClipboardList className="mx-auto size-9 text-[#B98D96]" />
                                <h3 className="mt-4 font-serif text-xl">Your requests will appear here</h3>
                                <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-[#705B60]">
                                    Start by telling us what you need translated. You can upload your documents securely after submitting the request.
                                </p>
                                <Link href="/quote-requests/create" className="mt-5 inline-flex items-center gap-2 rounded-full bg-[#3A091A] px-5 py-3 text-sm font-semibold text-white hover:bg-[#4A1525]">
                                    Request a quote <ArrowRight size={16} />
                                </Link>
                            </div>
                        ) : (
                            <div className="divide-y divide-[#3A091A]/10">
                                {recentRequests.map((item) => {
                                    const status = requestStatus[item.status] ?? {
                                        label: humanize(item.status),
                                        className: 'bg-[#F4F0EC] text-[#705B60]',
                                    };
                                    const latestQuote = item.quotes[0];

                                    return (
                                        <div key={item.id} className="flex flex-col gap-3 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-semibold">Request #{item.id}</span>
                                                    <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${status.className}`}>
                                                        {status.label}
                                                    </span>
                                                </div>
                                                <p className="mt-1 text-sm text-[#3A091A]">{item.service.name}</p>
                                                <p className="mt-1 text-xs text-[#806D70]">
                                                    {new Date(item.created_at).toLocaleDateString()} · {item.documents_count} {item.documents_count === 1 ? 'document' : 'documents'} · {item.currency.code}
                                                </p>
                                                {latestQuote && (
                                                    <div className="mt-2 flex flex-wrap items-center gap-3">
                                                        <p className="text-sm font-medium text-[#8B5362]">
                                                            Quotation {latestQuote.reference}: {money(latestQuote.total, latestQuote.currency.code)} · {humanize(latestQuote.status)}
                                                        </p>
                                                        {latestQuote.status === 'sent' && (
                                                            <Link href={`/quotes/${latestQuote.id}`} className="text-sm font-semibold text-[#3A091A] underline decoration-[#D4AF37] underline-offset-4">
                                                                Review quotation
                                                            </Link>
                                                        )}
                                                        {latestQuote.status === 'accepted' && (
                                                            <span className="text-xs font-semibold text-[#386547]">Accepted</span>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                            <Link href={`/quote-requests/${item.id}`} className="inline-flex shrink-0 items-center gap-2 self-start rounded-full border border-[#3A091A]/15 px-4 py-2.5 text-sm font-semibold text-[#3A091A] transition hover:border-[#9B6877] hover:bg-[#FAF6F0] sm:self-center">
                                                View request <ArrowRight size={15} />
                                            </Link>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </section>

                    <section className="mt-7 overflow-hidden rounded-2xl border border-[#3A091A]/10 bg-white shadow-sm">
                        <div className="border-b border-[#3A091A]/10 px-5 py-4 sm:px-6">
                            <h2 className="font-serif text-xl font-semibold">My orders</h2>
                            <p className="mt-1 text-xs text-[#806D70]">Orders created from accepted quotations</p>
                        </div>
                        {recentOrders.length === 0 ? (
                            <div className="flex items-center gap-4 px-5 py-7 sm:px-6">
                                <ReceiptText className="size-8 shrink-0 text-[#B98D96]" />
                                <div>
                                    <p className="text-sm font-medium">No orders yet</p>
                                    <p className="mt-1 text-xs leading-5 text-[#806D70]">Once you accept a quotation and an order is created, you can follow its progress here.</p>
                                </div>
                            </div>
                        ) : (
                            <div className="divide-y divide-[#3A091A]/10">
                                {recentOrders.map((order) => (
                                    <div key={order.id} className="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                        <div>
                                            <p className="font-semibold">{order.reference}</p>
                                            <p className="mt-1 text-xs capitalize text-[#806D70]">{order.quote.quote_request.service.name} · {humanize(order.status)} · payment {humanize(order.payment_status)}</p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <p className="font-semibold tabular-nums">{money(order.total, order.currency.code)}</p>
                                            <Link href={`/orders/${order.id}`} className="text-sm font-semibold text-[#3A091A] underline decoration-[#D4AF37] underline-offset-4">View order</Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'My dashboard', href: dashboard() }],
};
