import { Link, usePage } from '@inertiajs/react';
import { ClipboardList, BriefcaseBusiness, Coins, Languages, LayoutGrid, MessageSquareQuote, Plus, Settings2, ShoppingBag } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const isAdmin = Boolean(auth.user.is_admin);

    const mainNavItems: NavItem[] = isAdmin
        ? [
              { title: 'Admin dashboard', href: '/admin', icon: LayoutGrid },
              { title: 'Quote requests inbox', href: '/admin/quote-requests', icon: ClipboardList },
              { title: 'Orders', href: '/admin/orders', icon: ShoppingBag },
              { title: 'Services', href: '/admin/services', icon: BriefcaseBusiness },
              { title: 'Testimonials', href: '/admin/testimonials', icon: MessageSquareQuote },
              { title: 'Languages', href: '/admin/languages', icon: Languages },
              { title: 'Currencies', href: '/admin/currencies', icon: Coins },
              { title: 'Pricing rules', href: '/admin/pricing-rules', icon: Settings2 },
          ]
        : [
              { title: 'My dashboard', href: dashboard(), icon: LayoutGrid },
              { title: 'My requests', href: '/dashboard#requests', icon: ClipboardList },
              { title: 'New quote request', href: '/quote-requests/create', icon: Plus },
          ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={isAdmin ? '/admin' : dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>
            <SidebarContent><NavMain items={mainNavItems} /></SidebarContent>
            <SidebarFooter><NavUser /></SidebarFooter>
        </Sidebar>
    );
}
