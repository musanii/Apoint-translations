import { Head } from '@inertiajs/react';
import Navbar from '@/components/website/navbar';
import ServicesSection from '@/components/website/services-section';
import LanguagesSection from '@/components/website/languages-section';
import AboutSection from '@/components/website/about-section';
import ContactSection from '@/components/website/contact-section';
import Footer from '@/components/website/footer';
import type { Service } from '@/types/service';
import PriceCalculator from '@/components/website/price-calculator';

export default function Welcome({ services }: { services: Service[] }) {
    return (
        <>
            <Head title="Professional Translation Services" />

            <div className="min-h-screen bg-[#FAF6F0]">
                <Navbar />

                <main>
                    <section className="mx-auto grid max-w-7xl items-center gap-12 px-6 py-16 md:grid-cols-2 md:py-24 lg:px-8">
                        {/* Hero text */}
                        <div className="max-w-xl">
                            <p className="mb-6 inline-flex items-center gap-3 text-xs font-semibold tracking-[0.25em] text-[#8B5260] uppercase">
                                <span className="h-px w-8 bg-[#D4AF37]" />
                                Words without boundaries
                            </p>

                            <h1 className="text-5xl leading-[1.12] font-[var(--font-apoint-heading)] font-medium text-[#3A091A] sm:text-6xl lg:text-7xl">
                                Every word
                                <span className="block text-[#A76D78] italic">
                                    matters.
                                </span>
                            </h1>

                            <p className="mt-7 max-w-lg text-base leading-8 text-[#705B60] md:text-lg">
                                Professional translation services that help your
                                words travel further. We bring clarity, accuracy
                                and understanding to every document and every
                                conversation.
                            </p>

                            <div className="mt-9 flex flex-col gap-4 sm:flex-row">
                                <a
                                    href="/quote-requests/create"
                                    className="inline-flex items-center justify-center rounded-full bg-[#3A091A] px-8 py-4 text-sm font-semibold text-white transition duration-300 hover:-translate-y-1 hover:bg-[#4A1525]"
                                >
                                    Request a Translation
                                    <span className="ml-3">→</span>
                                </a>

                                <a
                                    href="#services"
                                    className="inline-flex items-center justify-center rounded-full border border-[#3A091A]/20 px-8 py-4 text-sm font-semibold text-[#3A091A] transition duration-300 hover:border-[#D4AF37] hover:bg-white"
                                >
                                    Explore Our Services
                                </a>
                            </div>

                            <div className="mt-10 flex items-center gap-3 text-sm text-[#806D70]">
                                <span className="h-2 w-2 rounded-full bg-[#D4AF37]" />
                                Based in Kenya. Connecting you to the world.
                            </div>
                        </div>

                        {/* Hero image */}
                        <div className="relative">
                            <div className="absolute -right-4 -bottom-4 h-full w-full rounded-[2rem] border border-[#D4AF37]/40" />

                            <div className="relative overflow-hidden rounded-[2rem] shadow-2xl shadow-[#3A091A]/15">
                                <img
                                    src="/images/hero-translator.jpg"
                                    alt="Hands signing a document on a desk"
                                    className="h-[420px] w-full object-cover sm:h-[520px]"
                                />

                                <div className="absolute inset-0 bg-gradient-to-t from-[#3A091A]/20 to-transparent" />
                            </div>

                            <div className="absolute -bottom-6 -left-4 rounded-2xl border border-[#3A091A]/5 bg-white/95 px-6 py-4 shadow-xl backdrop-blur-sm sm:-left-8">
                                <p className="text-lg font-[var(--font-apoint-heading)] font-semibold text-[#3A091A]">
                                    Your words, understood.
                                </p>
                                <p className="mt-1 text-xs tracking-wide text-[#806D70]">
                                    Across languages and borders
                                </p>
                            </div>
                        </div>
                    </section>
                    <ServicesSection services={services} />
                    <PriceCalculator />
                    <LanguagesSection />
                    <AboutSection />
                    <ContactSection />
                </main>
                <Footer />
            </div>
        </>
    );
}
