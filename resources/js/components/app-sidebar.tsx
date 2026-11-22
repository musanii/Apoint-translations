import { Link, usePage } from '@inertiajs/react';
import { ClipboardList, LayoutGrid, Plus } from 'lucide-react';
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
              { title: 'Admin dashboard', href: dashboard(), icon: LayoutGrid },
              { title: 'Quote requests inbox', href: '/admin/quote-requests', icon: ClipboardList },
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
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>
            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>
            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
