import { useState } from 'react';

export default function Navbar() {
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    return (
        <header className="x-50 sticky top-0 border-b border-[#3A091A]/10 bg-[#FAF6F0]/95 backdrop-blur-md">
            <nav className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
                {/*Brand*/}
                <a href="/" className="flex items-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#3A091A] text-2xl font-[var(--font-apoint-heading)] text-[#E8B4B8]">
                        A
                    </div>

                    <div>
                        <div className="text-xl leading-tight font-[var(--font-apoint-heading)] font-semibold text-[#3A091A]">
                            Apoint
                        </div>
                        <div className="text-[9px] font-semibold tracking-[0.25em] text-[#4A1525]">
                            TRANSLATIONS
                        </div>
                    </div>
                </a>

                {/* Desktop navigation */}
                <div className="hidden items-center gap-8 md:flex">
                    <a
                        href="#services"
                        className="text-sm text-[#4A1525] transition hover:text-[#D4AF37]"
                    >
                        Services
                    </a>
                    <a
                        href="#languages"
                        className="text-sm text-[#4A1525] transition hover:text-[#D4AF37]"
                    >
                        Languages
                    </a>
                    <a
                        href="#about"
                        className="text-sm text-[#4A1525] transition hover:text-[#D4AF37]"
                    >
                        About
                    </a>
                    <a
                        href="#contact"
                        className="text-sm text-[#4A1525] transition hover:text-[#D4AF37]"
                    >
                        Contact
                    </a>

                    <a
                        href="/quote-requests/create"
                        className="rounded-full bg-[#3A091A] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#4A1525]"
                    >
                        Get a Quote
                    </a>
                </div>

                {/*Mobile menu button */}
                <button
                    type="button"
                    onClick={() => setIsMenuOpen(!isMenuOpen)}
                    aria-label={
                        isMenuOpen
                            ? 'Close navigation menu'
                            : 'Open navigation menu'
                    }
                    aria-expanded={isMenuOpen}
                    className="rounded-lg p-2 text-[#3A091A] hover:bg-[#E8B4B8]/20 md:hidden"
                >
                    {isMenuOpen ? (
                        <span className="text-2xl"> × </span>
                    ) : (
                        <span className="text-2xl">☰</span>
                    )}
                </button>
            </nav>
            {/* Mobile navigation */}
            {isMenuOpen && (
                <div className="border-t border-[#3A091A]/10 bg-[#FAF6F0] px-6 py-5 md:hidden">
                    <div className="flex flex-col gap-5">
                        <a
                            href="#services"
                            onClick={() => setIsMenuOpen(false)}
                            className="text-sm text-[#4A1525]"
                        >
                            Services
                        </a>
                        <a
                            href="#languages"
                            onClick={() => setIsMenuOpen(false)}
                            className="text-sm text-[#4A1525]"
                        >
                            Languages
                        </a>
                        <a
                            href="#about"
                            onClick={() => setIsMenuOpen(false)}
                            className="text-sm text-[#4A1525]"
                        >
                            About
                        </a>
                        <a
                            href="#contact"
                            onClick={() => setIsMenuOpen(false)}
                            className="text-sm text-[#4A1525]"
                        >
                            Contact
                        </a>
                        <a
                            href="/quote-requests/create"
                            onClick={() => setIsMenuOpen(false)}
                            className="rounded-full bg-[#3A091A] px-6 py-3 text-center text-sm font-semibold text-white"
                        >
                            Get a Quote
                        </a>
                    </div>
                </div>
            )}
        </header>
    );
}
