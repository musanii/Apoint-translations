
const languages = [
    { name: 'English', region: 'Global' },
    { name: 'Swahili', region: 'East Africa' },
    { name: 'French', region: 'Global' },
    { name: 'Arabic', region: 'Middle East & North Africa' },
    { name: 'German', region: 'Europe' },
    { name: 'Spanish', region: 'Global' },
    { name: 'Chinese', region: 'East Asia' },
    { name: 'Portuguese', region: 'Global' },
    { name: 'Kikuyu', region: 'Kenya' },
    { name: 'Dholuo', region: 'Kenya' },
    { name: 'Luhya', region: 'Kenya' },
    { name: 'Kalenjin', region: 'Kenya' },
];

export default function LanguagesSection() {
    return (
        <section
            id="languages"
            className="bg-[#FAF6F0] px-6 py-20 md:py-28"
        >
            <div className="mx-auto max-w-7xl lg:px-2">
                <div className="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.25em] text-[#A76D78] uppercase">
                            Languages We Work With
                        </p>

                        <h2 className="mt-4 font-[var(--font-apoint-heading)] text-4xl font-medium leading-tight text-[#3A091A] md:text-5xl">
                            Many languages.
                            <span className="block text-[#A76D78] italic">
                                One clear message.
                            </span>
                        </h2>

                        <p className="mt-6 max-w-md text-base leading-8 text-[#806D70]">
                            We help individuals and businesses communicate
                            across borders, cultures and communities through
                            thoughtful language services.
                        </p>

                        <a
                            href="#quote"
                            className="mt-8 inline-flex items-center gap-3 text-sm font-semibold text-[#3A091A] transition hover:text-[#A76D78]"
                        >
                            Request a translation
                            <span>→</span>
                        </a>
                    </div>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {languages.map((language) => (
                            <div
                                key={language.name}
                                className="rounded-xl border border-[#3A091A]/10 bg-white px-4 py-5 transition duration-300 hover:-translate-y-1 hover:border-[#D4AF37]/60 hover:shadow-lg hover:shadow-[#3A091A]/5"
                            >
                                <div className="mb-3 h-1 w-8 rounded-full bg-[#D4AF37]" />

                                <h3 className="font-[var(--font-apoint-heading)] text-lg font-semibold text-[#3A091A]">
                                    {language.name}
                                </h3>

                                <p className="mt-1 text-xs text-[#9A8185]">
                                    {language.region}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
