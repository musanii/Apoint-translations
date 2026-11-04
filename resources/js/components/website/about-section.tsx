
export default function AboutSection() {
    return (
        <section
            id="about"
            className="bg-white px-6 py-20 md:py-28"
        >
            <div className="mx-auto grid max-w-7xl gap-14 lg:grid-cols-2 lg:items-center">
                <div className="relative">
                    <div className="absolute -left-4 -top-4 h-full w-full rounded-[2rem] border border-[#D4AF37]/40" />

                    <div className="relative rounded-[2rem] bg-[#3A091A] px-8 py-12 text-[#FAF6F0] md:px-12 md:py-16">
                        <p className="text-xs font-semibold tracking-[0.25em] text-[#E8B4B8] uppercase">
                            Our Philosophy
                        </p>

                        <h2 className="mt-6 font-[var(--font-apoint-heading)] text-4xl leading-tight font-medium md:text-5xl">
                            Language is more than words.
                        </h2>

                        <p className="mt-6 max-w-md text-sm leading-8 text-white/70 md:text-base">
                            It carries meaning, identity, culture and
                            opportunity. Every translation deserves the
                            attention required to preserve those things.
                        </p>

                        <div className="mt-10 h-px w-20 bg-[#D4AF37]" />

                        <p className="mt-5 font-[var(--font-apoint-heading)] text-xl italic text-[#E8B4B8]">
                            Clarity across every boundary.
                        </p>
                    </div>
                </div>

                <div>
                    <p className="text-xs font-semibold tracking-[0.25em] text-[#A76D78] uppercase">
                        About Apoint
                    </p>

                    <h2 className="mt-4 font-[var(--font-apoint-heading)] text-4xl leading-tight font-medium text-[#3A091A] md:text-5xl">
                        Helping your message reach the world.
                    </h2>

                    <p className="mt-6 text-base leading-8 text-[#806D70]">
                        Apoint Translations is a Kenya-based translation
                        service supporting individuals and businesses
                        communicating across languages and borders.
                    </p>

                    <p className="mt-4 text-base leading-8 text-[#806D70]">
                        We understand that a document can represent an
                        important opportunity, a business relationship or
                        a personal milestone. Our approach puts accuracy,
                        clarity and the intended meaning first.
                    </p>

                    <div className="mt-9 grid gap-6 sm:grid-cols-2">
                        <div>
                            <h3 className="font-[var(--font-apoint-heading)] text-xl font-semibold text-[#3A091A]">
                                Accuracy
                            </h3>
                            <p className="mt-2 text-sm leading-7 text-[#806D70]">
                                Respecting the original meaning and context
                                of every document.
                            </p>
                        </div>

                        <div>
                            <h3 className="font-[var(--font-apoint-heading)] text-xl font-semibold text-[#3A091A]">
                                Confidentiality
                            </h3>
                            <p className="mt-2 text-sm leading-7 text-[#806D70]">
                                Treating customer documents and information
                                with care.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
