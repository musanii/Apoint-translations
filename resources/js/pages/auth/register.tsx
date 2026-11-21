import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = { passwordRules: string };

export default function Register({ passwordRules }: Props) {
    return (
        <>
            <Head title="Create account" />
            <Form {...store.form()} resetOnSuccess={['password', 'password_confirmation']} disableWhileProcessing className="flex flex-col gap-5">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name" className="text-[#3A091A]">Full name</Label>
                                <Input id="name" type="text" required autoFocus tabIndex={1} autoComplete="name" name="name" placeholder="Your full name" className="h-11 border-[#3A091A]/15 bg-[#FAF6F0]/60 focus-visible:ring-[#D4AF37]" />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email" className="text-[#3A091A]">Email address</Label>
                                <Input id="email" type="email" required tabIndex={2} autoComplete="email" name="email" placeholder="you@example.com" className="h-11 border-[#3A091A]/15 bg-[#FAF6F0]/60 focus-visible:ring-[#D4AF37]" />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="password" className="text-[#3A091A]">Password</Label>
                                <PasswordInput id="password" required tabIndex={3} autoComplete="new-password" name="password" placeholder="Create a password" passwordrules={passwordRules} />
                                <InputError message={errors.password} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation" className="text-[#3A091A]">Confirm password</Label>
                                <PasswordInput id="password_confirmation" required tabIndex={4} autoComplete="new-password" name="password_confirmation" placeholder="Enter your password again" passwordrules={passwordRules} />
                                <InputError message={errors.password_confirmation} />
                            </div>
                            <Button type="submit" className="mt-2 h-12 w-full rounded-full bg-[#3A091A] text-white hover:bg-[#4A1525]" tabIndex={5} disabled={processing} data-test="register-user-button">
                                {processing && <Spinner />} Create account
                            </Button>
                        </div>
                        <p className="text-center text-sm text-[#4A1525]/65">Already have an account? <TextLink href={login()} tabIndex={6} className="font-semibold text-[#7C3D4D] hover:text-[#3A091A]">Sign in</TextLink></p>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create your account',
    description: 'Set up your Apoint account to keep your translation work in one place.',
};
