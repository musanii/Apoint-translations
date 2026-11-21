import { useFlashToast } from '@/hooks/use-flash-toast';
import { useAppearance } from '@/hooks/use-appearance';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

function Toaster({ ...props }: ToasterProps) {
    const { appearance } = useAppearance();

    useFlashToast();

    return (
        <Sonner
            theme={appearance}
            className="toaster group"
            position="top-right"
            richColors
            closeButton
            duration={4500}
            style={
                {
                    '--normal-bg': '#FAF6F0',
                    '--normal-text': '#3A091A',
                    '--normal-border': 'rgba(58, 9, 26, 0.14)',
                    '--success-bg': '#F0F7F0',
                    '--success-text': '#245C36',
                    '--success-border': '#C6DEC9',
                    '--error-bg': '#FFF1F0',
                    '--error-text': '#8B2635',
                    '--error-border': '#EBC6C6',
                } as React.CSSProperties
            }
            {...props}
        />
    );
}

export { Toaster };
