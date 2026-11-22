import { Head } from '@inertiajs/react';
import { ClipboardList, Clock, FileCheck2, Send, ShoppingBag } from 'lucide-react';

type Props = {
    counts: {
        totalRequests: number;
        pendingRequests: number;
        underReview: number;
        draftQuotes: number;
        sentQuotes: number;
        orders: number;
    };
};

export default function AdminDashboard({ counts }: Props) {
    const cards = [
        { label: 'All requests', value: counts.totalRequests, icon: ClipboardList },
        { label: 'Awaiting review', value: counts.pendingRequests, icon: Clock },
        { label: 'Under review', value: counts.underReview, icon: ClipboardList },
        { label: 'Draft quotations', value: counts.draftQuotes, icon: FileCheck2 },
        { label: 'Published quotes', value: counts.sentQuotes, icon: Send },
        { label: 'Orders', value: counts.orders, icon: ShoppingBag },
    ];

    return (
        <>
            <Head title="Admin dashboard · Apoint" />
            <div className="min-h-screen bg-[#FAF6F0] text-[#3A091A]">
                <header className="border-b border-[#3A091A]/10 bg-white">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-5 py-5">
                        <a href="/admin" className="flex items-center gap-3">
                            <span className="grid size-10 place-items-center rounded-xl bg-[#3A091A] font-serif text-xl text-[#E8B4B8]">A</span>
                            <span><b className="block font-serif text-lg">Apoint</b><small className="tracking-[.25em] text-[#705B60]">TRANSLATIONS</small></span>
                        </a>
                        <a href="/admin/quote-requests" className="rounded-full bg-[#3A091A] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#4A1525]">Open quote inbox</a>
                    </div>
                </header>
                <main className="mx-auto max-w-7xl px-5 py-10">
                    <p className="text-xs font-semibold tracking-[.22em] text-[#A76D78]">ADMINISTRATOR WORKSPACE</p>
                    <h1 className="mt-2 font-serif text-4xl">Dashboard</h1>
                    <p className="mt-3 text-sm leading-6 text-[#705B60]">Overview of customer requests, quotations, and orders.</p>
                    <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {cards.map(({ label, value, icon: Icon }) => (
                            <div key={label} className="rounded-2xl border border-[#3A091A]/10 bg-white p-5 shadow-sm">
                                <div className="flex items-center justify-between text-sm text-[#705B60]">{label}<Icon className="size-5 text-[#A76D78]" /></div>
                                <p className="mt-3 font-serif text-4xl">{value}</p>
                            </div>
                        ))}
                    </div>
                    <section className="mt-8 rounded-2xl border border-[#D4AF37]/35 bg-white p-6">
                        <h2 className="font-serif text-2xl">Quote requests</h2>
                        <p className="mt-2 text-sm leading-6 text-[#705B60]">Review customer submissions, securely access their documents, and prepare or publish quotations.</p>
                        <a href="/admin/quote-requests" className="mt-5 inline-flex rounded-full bg-[#3A091A] px-5 py-3 text-sm font-semibold text-white hover:bg-[#4A1525]">Go to quote requests →</a>
                    </section>
                </main>
            </div>
        </>
    );
}
