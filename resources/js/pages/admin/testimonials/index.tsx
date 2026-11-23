import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Star, Plus, Pencil, Trash2, MessageSquareQuote } from 'lucide-react';

type Testimonial = {
    id: number; customer_name: string; company: string | null; service: string | null;
    quote: string; rating: number | null; avatar_path: string | null;
    is_published: boolean; is_featured: boolean; sort_order: number;
};
type Props = { testimonials: Testimonial[]; flash?: { success?: string } };
const empty = { customer_name: '', company: '', service: '', quote: '', rating: '5', is_published: false, is_featured: false, sort_order: '0', avatar: null as File | null };

export default function AdminTestimonials({ testimonials }: Props) {
    const [editing, setEditing] = useState<Testimonial | null>(null);
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm(empty);
    const { flash } = usePage<Props>().props;

    function open(item?: Testimonial) {
        setEditing(item ?? null);
        setData(item ? { customer_name: item.customer_name, company: item.company ?? '', service: item.service ?? '', quote: item.quote, rating: String(item.rating ?? 5), is_published: item.is_published, is_featured: item.is_featured, sort_order: String(item.sort_order), avatar: null } : empty);
        setShowForm(true);
    }
    function submit(event: React.FormEvent) {
        event.preventDefault();
        const options = { forceFormData: true, preserveScroll: true, onSuccess: () => { setShowForm(false); setEditing(null); reset(); } };
        if (editing) post(`/admin/testimonials/${editing.id}?_method=PUT`, options);
        else post('/admin/testimonials', options);
    }
    return <><Head title="Testimonials · Apoint Admin" /><main className="min-h-screen bg-[#FAF6F0] px-5 py-9 text-[#3A091A]"><div className="mx-auto max-w-7xl">
        <a href="/admin" className="text-sm font-semibold text-[#705B60]">← Admin dashboard</a>
        <div className="mt-5 flex flex-wrap items-end justify-between gap-4"><div><p className="text-xs font-semibold tracking-[.22em] text-[#A76D78]">WEBSITE CONTENT</p><h1 className="mt-2 font-serif text-4xl">Testimonials</h1><p className="mt-2 text-sm text-[#705B60]">Manage genuine customer feedback shown on the public website.</p></div><button onClick={() => open()} className="inline-flex items-center gap-2 rounded-full bg-[#3A091A] px-5 py-3 text-sm font-semibold text-white"><Plus className="size-4" /> Add testimonial</button></div>
        {flash?.success && <p role="status" className="mt-5 rounded-xl bg-white px-4 py-3 text-sm">{flash.success}</p>}
        {showForm && <form onSubmit={submit} className="mt-7 rounded-3xl border border-[#D4AF37]/30 bg-white p-6 shadow-sm"><div className="flex items-center justify-between"><h2 className="font-serif text-2xl">{editing ? 'Edit testimonial' : 'New testimonial'}</h2><button type="button" onClick={() => setShowForm(false)} className="text-sm text-[#806D70]">Close</button></div>
            <div className="mt-5 grid gap-4 md:grid-cols-2">
                <label className="text-sm font-medium">Customer name<input required maxLength={120} value={data.customer_name} onChange={e => setData('customer_name', e.target.value)} className="mt-1 w-full rounded-xl border p-3" />{errors.customer_name && <small className="text-red-700">{errors.customer_name}</small>}</label>
                <label className="text-sm font-medium">Company (optional)<input value={data.company} onChange={e => setData('company', e.target.value)} className="mt-1 w-full rounded-xl border p-3" /></label>
                <label className="text-sm font-medium">Service<input value={data.service} onChange={e => setData('service', e.target.value)} placeholder="e.g. Certified translation" className="mt-1 w-full rounded-xl border p-3" /></label>
                <label className="text-sm font-medium">Rating<select value={data.rating} onChange={e => setData('rating', e.target.value)} className="mt-1 w-full rounded-xl border p-3"><option value="">No rating</option>{[1,2,3,4,5].map(n => <option key={n} value={n}>{n} / 5</option>)}</select></label>
                <label className="text-sm font-medium md:col-span-2">Customer feedback<textarea required minLength={20} maxLength={2000} rows={4} value={data.quote} onChange={e => setData('quote', e.target.value)} className="mt-1 w-full rounded-xl border p-3" />{errors.quote && <small className="text-red-700">{errors.quote}</small>}</label>
                <label className="text-sm font-medium">Customer photo (optional)<input type="file" accept="image/*" onChange={e => setData('avatar', e.target.files?.[0] ?? null)} className="mt-2 block w-full text-sm" />{errors.avatar && <small className="text-red-700">{errors.avatar}</small>}</label>
                <label className="text-sm font-medium">Display order<input type="number" min="0" value={data.sort_order} onChange={e => setData('sort_order', e.target.value)} className="mt-1 w-full rounded-xl border p-3" /></label>
                <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_published} onChange={e => setData('is_published', e.target.checked)} /> Published on website</label>
                <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.is_featured} onChange={e => setData('is_featured', e.target.checked)} /> Featured testimonial</label>
            </div>
            <div className="mt-6 flex justify-end gap-3"><button type="button" onClick={() => setShowForm(false)} className="rounded-full border px-5 py-2.5 text-sm">Cancel</button><button disabled={processing} className="rounded-full bg-[#3A091A] px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{processing ? 'Saving…' : 'Save testimonial'}</button></div>
        </form>}
        <section className="mt-7 grid gap-4 lg:grid-cols-2">{testimonials.map(item => <article key={item.id} className="rounded-2xl border border-[#3A091A]/10 bg-white p-6"><div className="flex items-start justify-between gap-4"><div><div className="flex flex-wrap items-center gap-2"><h3 className="font-semibold">{item.customer_name}</h3><span className={`rounded-full px-2.5 py-1 text-xs ${item.is_published ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800'}`}>{item.is_published ? 'Published' : 'Draft'}</span>{item.is_featured && <span className="text-xs text-[#A76D78]">Featured</span>}</div><p className="mt-1 text-xs text-[#806D70]">{[item.company, item.service].filter(Boolean).join(' · ') || 'No additional details'}</p></div><MessageSquareQuote className="size-5 text-[#D4AF37]" /></div><p className="mt-4 line-clamp-4 text-sm leading-7 text-[#705B60]">“{item.quote}”</p><div className="mt-4 flex items-center justify-between border-t pt-4"><span className="flex items-center gap-1 text-xs text-[#A76D78]">{item.rating ? <><Star className="size-3 fill-[#D4AF37] text-[#D4AF37]" /> {item.rating}/5</> : 'No rating'} · Order {item.sort_order}</span><div className="flex gap-2"><button onClick={() => open(item)} className="inline-flex items-center gap-1 rounded-full border px-3 py-2 text-xs"><Pencil className="size-3" /> Edit</button><button onClick={() => { if (window.confirm('Delete this testimonial permanently?')) router.delete(`/admin/testimonials/${item.id}`, { preserveScroll: true }); }} className="inline-flex items-center gap-1 rounded-full border border-red-200 px-3 py-2 text-xs text-red-700"><Trash2 className="size-3" /> Delete</button></div></div></article>)}</section>
        {testimonials.length === 0 && !showForm && <div className="mt-7 rounded-3xl border border-dashed border-[#3A091A]/20 bg-white p-14 text-center"><MessageSquareQuote className="mx-auto size-9 text-[#A76D78]" /><h2 className="mt-4 font-serif text-2xl">No testimonials yet</h2><p className="mt-2 text-sm text-[#705B60]">Add genuine customer feedback when the client has permission to publish it.</p></div>}
    </div></main></>;
}
