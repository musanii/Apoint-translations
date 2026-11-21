import { Form, Head } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { ArrowLeft, Mail } from 'lucide-react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    useEffect(() => {
        if (status) toast.success(status);
    }, [status]);

    return (
        <>
            <Head title="Forgot password" />
            <div className="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-[#F4C2C2]/45 text-[#4A1525]">
                <Mail className="h-5 w-5" />
            </div>
            <div className="space-y-5">
                <Form {...email.form()}>
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="email"
                                    className="text-[#3A091A]"
                                >
                                    Email address
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoComplete="email"
                                    autoFocus
                                    placeholder="you@example.com"
                                    className="h-12 border-[#3A091A]/15 bg-[#FAF6F0]/60 focus-visible:ring-[#D4AF37]"
                                />
                                <InputError message={errors.email} />
                            </div>
                            <Button
                                type="submit"
                                className="h-12 w-full rounded-full bg-[#3A091A] text-white hover:bg-[#4A1525]"
                                disabled={processing}
                                data-test="email-password-reset-link-button"
                            >
                                {processing && (
                                    <span className="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                                )}
                                Email me a reset link
                            </Button>
                        </>
                    )}
                </Form>
                <TextLink
                    href={login()}
                    className="inline-flex w-full items-center justify-center gap-2 text-sm font-medium text-[#7C3D4D] hover:text-[#3A091A]"
                >
                    <ArrowLeft className="h-4 w-4" /> Return to sign in
                </TextLink>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot your password?',
    description:
        'No worries. Enter your email address and we’ll send you a secure link to reset it.',
};
