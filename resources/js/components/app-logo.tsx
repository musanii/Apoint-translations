import { usePage } from '@inertiajs/react';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <div className="ml-1 grid min-w-0 flex-1 text-left">
            <span className="truncate font-serif text-base font-semibold leading-tight tracking-tight text-sidebar-foreground">
                {name}
            </span>
            <span className="mt-0.5 truncate text-[10px] font-medium uppercase tracking-[0.18em] text-sidebar-foreground/60">
                Official document services
            </span>
        </div>
    );
}
