import { Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Globe2, ShieldCheck } from 'lucide-react';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <main className="min-h-screen bg-[#FAF6F0] text-[#3A091A]">
            <div className="grid min-h-screen lg:grid-cols-[1.02fr_0.98fr]">
                <section className="relative hidden overflow-hidden bg-[#3A091A] px-12 py-10 text-[#FAF6F0] lg:flex lg:flex-col lg:justify-between xl:px-20">
                    <div className="pointer-events-none absolute top-1/4 -right-36 h-[30rem] w-[30rem] rounded-full border border-[#E8B4B8]/15" />
                    <div className="pointer-events-none absolute top-[30%] -right-20 h-[22rem] w-[22rem] rounded-full border border-[#D4AF37]/20" />
                    <Link
                        href="/"
                        className="relative z-10 inline-flex w-fit items-center gap-3"
                    >
                        <span className="flex h-12 w-12 items-center justify-center rounded-xl border border-[#E8B4B8]/30 bg-[#E8B4B8] text-3xl font-[var(--font-apoint-heading)] font-semibold text-[#3A091A]">
                            A
                        </span>
                        <span>
                            <span className="block text-2xl leading-none font-[var(--font-apoint-heading)] font-semibold">
                                Apoint
                            </span>
                            <span className="mt-1 block text-[9px] font-semibold tracking-[0.28em] text-[#E8B4B8]">
                                TRANSLATIONS
                            </span>
                        </span>
                    </Link>

                    <div className="relative z-10 max-w-xl py-16">
                        <p className="mb-6 text-xs font-semibold tracking-[0.28em] text-[#D4AF37] uppercase">
                            Every word matters
                        </p>
                        <h1 className="text-5xl leading-[1.12] font-[var(--font-apoint-heading)] font-medium xl:text-6xl">
                            Your words deserve to be understood.
                        </h1>
                        <p className="mt-7 max-w-lg text-base leading-8 text-[#FAF6F0]/70">
                            Manage your translation requests, keep your
                            documents organised and stay connected with our
                            language team.
                        </p>
                        <div className="mt-12 grid gap-5">
                            <div className="flex items-center gap-4">
                                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-[#E8B4B8]">
                                    <ShieldCheck className="h-5 w-5" />
                                </span>
                                <span className="text-sm text-[#FAF6F0]/85">
                                    Your documents handled with care
                                </span>
                            </div>
                            <div className="flex items-center gap-4">
                                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-[#E8B4B8]">
                                    <Globe2 className="h-5 w-5" />
                                </span>
                                <span className="text-sm text-[#FAF6F0]/85">
                                    Language support across borders
                                </span>
                            </div>
                            <div className="flex items-center gap-4">
                                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-[#E8B4B8]">
                                    <CheckCircle2 className="h-5 w-5" />
                                </span>
                                <span className="text-sm text-[#FAF6F0]/85">
                                    A clear view of your requests and progress
                                </span>
                            </div>
                        </div>
                    </div>

                    <p className="relative z-10 text-xs tracking-wide text-[#FAF6F0]/45">
                        Based in Kenya. Serving the world.
                    </p>
                </section>

                <section className="flex min-h-screen flex-col px-5 py-6 sm:px-8 lg:px-12 xl:px-20">
                    <div className="flex items-center justify-between lg:justify-end">
                        <Link
                            href="/"
                            className="inline-flex items-center gap-2 text-sm text-[#4A1525]/65 transition hover:text-[#3A091A] lg:hidden"
                        >
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#3A091A] text-xl font-[var(--font-apoint-heading)] text-[#E8B4B8]">
                                A
                            </span>
                            <span className="text-lg font-[var(--font-apoint-heading)] font-semibold text-[#3A091A]">
                                Apoint
                            </span>
                        </Link>
                        <Link
                            href="/"
                            className="hidden items-center gap-2 text-sm text-[#4A1525]/60 transition hover:text-[#3A091A] lg:inline-flex"
                        >
                            <ArrowLeft className="h-4 w-4" /> Back to website
                        </Link>
                        <Link
                            href="/"
                            className="text-xs font-medium text-[#4A1525]/60 hover:text-[#3A091A] lg:hidden"
                        >
                            Back to website
                        </Link>
                    </div>

                    <div className="flex flex-1 items-center justify-center py-12">
                        <div className="w-full max-w-md">
                            <div className="mb-9">
                                <p className="mb-3 text-[10px] font-semibold tracking-[0.25em] text-[#9A6A70] uppercase">
                                    APOINT TRANSLATIONS
                                </p>
                                <h2 className="text-4xl font-[var(--font-apoint-heading)] font-semibold tracking-tight text-[#3A091A]">
                                    {title}
                                </h2>
                                <p className="mt-3 text-sm leading-6 text-[#4A1525]/65">
                                    {description}
                                </p>
                            </div>
                            <div className="rounded-2xl border border-[#3A091A]/[0.08] bg-white p-6 shadow-[0_18px_60px_-30px_rgba(58,9,26,0.22)] sm:p-8">
                                {children}
                            </div>
                            <p className="mt-8 text-center text-xs text-[#4A1525]/45">
                                © {new Date().getFullYear()} Apoint
                                Translations. Every word matters.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    );
}
