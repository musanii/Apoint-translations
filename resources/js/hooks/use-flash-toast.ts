import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

type FlashPayload = {
    toast?: FlashToast;
    success?: string | null;
    error?: string | null;
};

type InertiaPageFlashEvent = CustomEvent<{
    page?: {
        props?: {
            flash?: FlashPayload;
        };
    };
}>;

type InertiaFlashEvent = CustomEvent<{
    flash?: FlashPayload;
}>;

function showFlash(flash?: FlashPayload): void {
    if (!flash) return;

    if (flash.toast?.message) {
        toast[flash.toast.type](flash.toast.message);
        return;
    }

    if (flash.success) toast.success(flash.success);
    if (flash.error) toast.error(flash.error);
}

export function useFlashToast(): void {
    useEffect(() => {
        const removeNavigateListener = router.on('navigate', (event) => {
            const flash = (event as InertiaPageFlashEvent).detail?.page?.props
                ?.flash;

            showFlash(flash);
        });

        const removeFlashListener = router.on('flash', (event) => {
            showFlash((event as InertiaFlashEvent).detail?.flash);
        });

        return () => {
            removeNavigateListener();
            removeFlashListener();
        };
    }, []);
}
