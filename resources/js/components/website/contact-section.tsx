
export default function ContactSection() {
    return (
        <section
            id="contact"
            className="bg-[#FAF6F0] px-6 py-20 md:py-28"
        >
            <div className="mx-auto max-w-7xl">
                <div
                    id="quote"
                    className="relative overflow-hidden rounded-[2rem] bg-[#3A091A] px-8 py-14 text-center md:px-16 md:py-20"
                >
                    {/* Decorative elements */}
                    <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full border border-white/10" />
                    <div className="absolute -bottom-32 -left-16 h-80 w-80 rounded-full border border-[#D4AF37]/20" />

                    <div className="relative mx-auto max-w-3xl">
                        <p className="text-xs font-semibold tracking-[0.25em] text-[#E8B4B8] uppercase">
                            Let's Get Started
                        </p>

                        <h2 className="mt-5 font-[var(--font-apoint-heading)] text-4xl leading-tight font-medium text-white md:text-6xl">
                            Your next conversation starts with a word.
                        </h2>

                        <p className="mx-auto mt-6 max-w-xl text-sm leading-8 text-white/70 md:text-base">
                            Whether it's a personal document or a business
                            project, tell us what you need translated.
                            We'll help you take the next step.
                        </p>

                        <div className="mt-9 flex flex-col justify-center gap-4 sm:flex-row">
                            <a
                                href="#quote"
                                className="rounded-full bg-[#E8B4B8] px-8 py-4 text-sm font-semibold text-[#3A091A] transition duration-300 hover:-translate-y-1 hover:bg-white"
                            >
                                Request a Quote
                                <span className="ml-3">→</span>
                            </a>

                            <a
                                href="#services"
                                className="rounded-full border border-white/30 px-8 py-4 text-sm font-semibold text-white transition duration-300 hover:border-[#D4AF37] hover:bg-white/10"
                            >
                                Explore Our Services
                            </a>
                        </div>

                        <p className="mt-8 text-xs tracking-wide text-white/50">
                            Apoint Translations · Based in Kenya, serving
                            clients worldwide.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}
