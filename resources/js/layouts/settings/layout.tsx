import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    { title: 'Profile', href: edit(), icon: null },
    { title: 'Security', href: editSecurity(), icon: null },
    { title: 'Appearance', href: editAppearance(), icon: null },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="min-h-full bg-[#FAF6F0] px-4 py-6 text-[#3A091A] sm:px-6">
            <div className="mb-7 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#3A091A]/10 bg-white p-4">
                <Link href="/dashboard" className="flex items-center gap-3">
                    <span className="grid size-10 place-items-center rounded-xl bg-[#3A091A] font-serif text-xl text-[#E8B4B8]">A</span>
                    <span><b className="block font-serif text-lg">Apoint</b><small className="tracking-[.25em] text-[#705B60]">TRANSLATIONS</small></span>
                </Link>
                <Link href="/dashboard" className="text-sm font-semibold text-[#705B60] hover:text-[#3A091A">← Back to dashboard</Link>
            </div>
            <Heading
                title="Account settings"
                description="Manage your profile, security, and preferences."
            />
            <div className="mt-5 flex flex-col lg:flex-row lg:gap-10">
                <aside className="w-full max-w-xl lg:w-52 lg:shrink-0">
                    <nav className="flex flex-col gap-1" aria-label="Settings">
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start rounded-xl px-4 py-3 text-[#705B60] hover:bg-[#E8B4B8]/20 hover:text-[#3A091A]', {
                                    'bg-[#3A091A] text-white hover:bg-[#4A1525] hover:text-white': isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && <item.icon className="h-4 w-4" />}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>
                <Separator className="my-6 lg:hidden" />
                <div className="min-w-0 flex-1">
                    <section className="max-w-2xl rounded-2xl border border-[#3A091A]/10 bg-white p-5 shadow-sm sm:p-7">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
