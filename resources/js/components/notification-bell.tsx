import { Link, router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { Bell, CheckCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type NotificationItem = {
    id: string;
    data: {
        title?: string;
        message?: string;
        url?: string;
        category?: string;
    };
    read_at: string | null;
    created_at: string;
};

type SharedProps = Record<string, unknown> & {
    notifications?: {
        items: NotificationItem[];
        unreadCount: number;
    };
};

export function NotificationBell() {
    const { notifications } = usePage<SharedProps>().props;
    const items = notifications?.items ?? [];
    const unreadCount = notifications?.unreadCount ?? 0;

    useEffect(() => {
        const interval = window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                router.reload({
                    only: ['notifications'],
                    preserveScroll: true,
                });
            }
        }, 30000);

        return () => window.clearInterval(interval);
    }, []);

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative"
                    aria-label={`Notifications, ${unreadCount} unread`}
                >
                    <Bell className="size-5" />
                    {unreadCount > 0 && (
                        <span className="absolute -right-0.5 -top-0.5 flex min-w-4 items-center justify-center rounded-full bg-[#3A091A] px-1 text-[10px] leading-4 text-white">
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                className="w-[min(22rem,calc(100vw-2rem))] p-0"
            >
                <div className="flex items-center justify-between px-4 py-3">
                    <DropdownMenuLabel className="p-0">
                        Notifications
                    </DropdownMenuLabel>
                    {unreadCount > 0 && (
                        <Link
                            href="/notifications/read-all"
                            method="post"
                            as="button"
                            className="inline-flex items-center gap-1 text-xs text-[#7A344C] hover:underline"
                        >
                            <CheckCheck className="size-3.5" />
                            Mark all read
                        </Link>
                    )}
                </div>
                <DropdownMenuSeparator />

                {items.length === 0 ? (
                    <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                        You're all caught up.
                    </p>
                ) : (
                    <div className="max-h-96 overflow-y-auto">
                        {items.map((item) => (
                            <DropdownMenuItem
                                key={item.id}
                                asChild
                                className="cursor-pointer items-start whitespace-normal px-4 py-3 focus:bg-[#FAF6F0]"
                            >
                                <Link
                                    href={`/notifications/${item.id}/read`}
                                    method="post"
                                    as="button"
                                    className="flex w-full gap-3 text-left"
                                >
                                    <span
                                        className={`mt-1.5 size-2 shrink-0 rounded-full ${item.read_at ? 'bg-transparent' : 'bg-[#D4AF37]'}`}
                                    />
                                    <span className="min-w-0 flex-1">
                                        <span className="block text-sm font-medium text-[#3A091A]">
                                            {item.data.title ?? 'Apoint update'}
                                        </span>
                                        <span className="mt-1 block text-xs leading-relaxed text-muted-foreground">
                                            {item.data.message}
                                        </span>
                                        <span className="mt-1 block text-[11px] text-muted-foreground">
                                            {new Date(item.created_at).toLocaleString()}
                                        </span>
                                    </span>
                                </Link>
                            </DropdownMenuItem>
                        ))}
                    </div>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
