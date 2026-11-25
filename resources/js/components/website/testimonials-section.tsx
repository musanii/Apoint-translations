import { Star, Quote } from 'lucide-react';

type Testimonial = {
    id: number;
    customer_name: string;
    company: string | null;
    service: string | null;
    quote: string;
    rating: number | null;
    avatar_url: string | null;
    is_featured: boolean;
};

export default function TestimonialsSection({ testimonials }: { testimonials: Testimonial[] }) {
    if (testimonials.length === 0) return null;

    return (
        <section id="testimonials" className="relative overflow-hidden bg-[#FAF6F0] px-6 py-20 md:py-28">
            <div className="pointer-events-none absolute -right-24 -top-24 size-80 rounded-full border border-[#D4AF37]/20" />
            <div className="mx-auto max-w-7xl">
                <div className="mx-auto max-w-2xl text-center">
                    <p className="text-xs font-semibold tracking-[0.25em] text-[#A76D78] uppercase">Kind words</p>
                    <h2 className="mt-4 font-[var(--font-apoint-heading)] text-4xl font-medium text-[#3A091A] md:text-5xl">Trusted with words that matter.</h2>
                    <p className="mt-5 text-base leading-8 text-[#806D70]">A few words from customers who have worked with Apoint Translations.</p>
                </div>
                <div className="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    {testimonials.map((item) => (
                        <article key={item.id} className="relative flex h-full flex-col rounded-[1.75rem] border border-[#3A091A]/10 bg-white p-7 shadow-[0_18px_55px_rgba(58,9,26,0.06)]">
                            <Quote className="size-8 text-[#D4AF37]" strokeWidth={1.4} />
                            {item.rating && <div className="mt-5 flex gap-1" aria-label={`${item.rating} out of 5 stars`}>{Array.from({ length: item.rating }).map((_, index) => <Star key={index} className="size-3.5 fill-[#D4AF37] text-[#D4AF37]" />)}</div>}
                            <blockquote className="mt-4 flex-1 font-[var(--font-apoint-heading)] text-xl leading-8 text-[#3A091A]">“{item.quote}”</blockquote>
                            {item.service && <p className="mt-5 text-[11px] font-semibold tracking-[.16em] text-[#A76D78] uppercase">{item.service}</p>}
                            <div className="mt-6 flex items-center gap-3 border-t border-[#3A091A]/10 pt-5">
                                {item.avatar_url ? <img src={item.avatar_url} alt="" className="size-11 rounded-full object-cover" /> : <span className="grid size-11 place-items-center rounded-full bg-[#F4C2C2]/45 font-serif text-lg text-[#3A091A]">{item.customer_name.charAt(0).toUpperCase()}</span>}
                                <div><p className="text-sm font-semibold text-[#3A091A]">{item.customer_name}</p>{item.company && <p className="mt-0.5 text-xs text-[#806D70]">{item.company}</p>}</div>
                                {item.is_featured && <span className="ml-auto text-[10px] font-semibold tracking-wider text-[#A76D78]">FEATURED</span>}
                            </div>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}
