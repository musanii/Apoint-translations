import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

type FlashPayload = {
    toast?: FlashToast;
    success?: string | null;
    error?: string | null;
};

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent<{ flash?: FlashPayload }>)
                .detail?.flash;

            if (!flash) return;

            if (flash.toast?.message) {
                toast[flash.toast.type](flash.toast.message);
                return;
            }

            if (flash.success) toast.success(flash.success);
            if (flash.error) toast.error(flash.error);
        });
    }, []);
}
