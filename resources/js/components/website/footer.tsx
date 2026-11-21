
export default function Footer() {
    return (
        <footer className="bg-[#260611] px-6 pt-16 pb-8 text-white">
            <div className="mx-auto max-w-7xl">
                <div className="grid gap-12 border-b border-white/10 pb-12 md:grid-cols-2 lg:grid-cols-4">

                    {/* Brand */}
                    <div className="lg:col-span-1">
                        <a href="/" className="inline-flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#E8B4B8] font-[var(--font-apoint-heading)] text-2xl text-[#3A091A]">
                                A
                            </div>

                            <div>
                                <div className="font-[var(--font-apoint-heading)] text-xl font-semibold">
                                    Apoint
                                </div>
                                <div className="text-[9px] font-semibold tracking-[0.25em] text-[#E8B4B8]">
                                    TRANSLATIONS
                                </div>
                            </div>
                        </a>

                        <p className="mt-6 max-w-xs text-sm leading-7 text-white/60">
                            Connecting people, businesses and opportunities
                            through professional translation services.
                        </p>

                        <p className="mt-5 text-xs tracking-wide text-[#E8B4B8]">
                            Based in Kenya. Serving the world.
                        </p>
                    </div>

                    {/* Quick links */}
                    <div>
                        <h3 className="font-[var(--font-apoint-heading)] text-lg font-semibold text-[#E8B4B8]">
                            Explore
                        </h3>

                        <ul className="mt-6 space-y-4 text-sm text-white/60">
                            <li>
                                <a href="/" className="transition hover:text-white">
                                    Home
                                </a>
                            </li>
                            <li>
                                <a href="#about" className="transition hover:text-white">
                                    About Us
                                </a>
                            </li>
                            <li>
                                <a href="#services" className="transition hover:text-white">
                                    Our Services
                                </a>
                            </li>
                            <li>
                                <a href="#languages" className="transition hover:text-white">
                                    Languages
                                </a>
                            </li>
                            <li>
                                <a href="#contact" className="transition hover:text-white">
                                    Contact
                                </a>
                            </li>
                        </ul>
                    </div>

                    {/* Services */}
                    <div>
                        <h3 className="font-[var(--font-apoint-heading)] text-lg font-semibold text-[#E8B4B8]">
                            Our Services
                        </h3>

                        <ul className="mt-6 space-y-4 text-sm text-white/60">
                            <li>Document Translation</li>
                            <li>Certified Translation</li>
                            <li>Business & Legal Translation</li>
                            <li>Localization</li>
                        </ul>
                    </div>

                    {/* Contact */}
                    <div>
                        <h3 className="font-[var(--font-apoint-heading)] text-lg font-semibold text-[#E8B4B8]">
                            Get in Touch
                        </h3>

                        <p className="mt-6 text-sm leading-7 text-white/60">
                            Have a document that needs translating?
                            We'd be happy to hear from you.
                        </p>

                        <a
                            href="/quote-requests/create"
                            className="mt-6 inline-flex items-center gap-2 rounded-full bg-[#E8B4B8] px-6 py-3 text-sm font-semibold text-[#3A091A] transition hover:bg-white"
                        >
                            Request a Quote
                            <span>→</span>
                        </a>
                    </div>
                </div>

                {/* Bottom bar */}
                <div className="flex flex-col gap-4 pt-7 text-xs text-white/40 sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        © {new Date().getFullYear()} Apoint Translations.
                        All rights reserved.
                    </p>

                    <p>
                        Every word matters.
                    </p>
                </div>
            </div>
        </footer>
    );
}
