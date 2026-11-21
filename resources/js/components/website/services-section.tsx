import type { Service } from '@/types/service';


type ServicesSectionProps = {
    services: Service[];
};

export default function ServicesSection({ services }: ServicesSectionProps) {
    return (
        <section
            id="services"
            className="bg-white px-6 py-20 md:py-28"
        >
            <div className="mx-auto max-w-7xl lg:px-2">
                <div className="mx-auto max-w-2xl text-center">
                    <p className="text-xs font-semibold tracking-[0.25em] text-[#A76D78] uppercase">
                        What We Do
                    </p>

                    <h2 className="mt-4 font-[var(--font-apoint-heading)] text-4xl font-medium text-[#3A091A] md:text-5xl">
                        Language services for a connected world.
                    </h2>

                    <p className="mt-5 text-base leading-8 text-[#806D70]">
                        From personal documents to complex business content,
                        we help you communicate clearly across languages.
                    </p>
                </div>

                <div className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {services.map((service, index) => (
                        <article
                            key={service.id}
                            className="group rounded-2xl border border-[#3A091A]/10 bg-[#FAF6F0] p-7 transition duration-300 hover:-translate-y-2 hover:border-[#D4AF37]/60 hover:bg-white hover:shadow-xl hover:shadow-[#3A091A]/5"
                        >
                            <div className="flex items-center justify-between">
                                <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-[#3A091A] font-[var(--font-apoint-heading)] text-xl text-[#E8B4B8]">
                                    {['文', '✧', '§', '◎', '✦', '◇'][index % 6]}
                                </span>

                                <span className="text-xs font-semibold tracking-widest text-[#B69A9E]">
                                    {String(index + 1).padStart(2, '0')}
                                </span>
                            </div>

                            <h3 className="mt-8 font-[var(--font-apoint-heading)] text-2xl font-semibold text-[#3A091A]">
                                {service.name}
                            </h3>

                            <p className="mt-4 text-sm leading-7 text-[#806D70]">
                                {service.description}
                            </p>

                            <a
                                href="#quote"
                                className="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-[#8B5260] transition group-hover:text-[#3A091A]"
                            >
                                Get started
                                <span className="transition-transform group-hover:translate-x-1">
                                    →
                                </span>
                            </a>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}
