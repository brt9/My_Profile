import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword, googleLoginEnabled }) {
    const { data, setData, post, processing, errors, reset } = useForm({ email: '', password: '', remember: false });
    const submit = (event) => {
        event.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <GuestLayout>
            <Head title="Entrar" />
            <header className="auth-card-head">
                <span className="auth-kicker">Bem-vindo de volta</span>
                <h2>Entre na sua conta</h2>
                <p>Use o e-mail cadastrado para continuar.</p>
            </header>

            {status && <div className="auth-success">{status}</div>}

            {googleLoginEnabled && (
                <>
                    <a className="auth-google-button" href={route('google.login')}>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.91h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.4Z" />
                            <path fill="#34A853" d="M12 22c2.7 0 4.98-.9 6.63-2.43l-3.24-2.53c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.61A10 10 0 0 0 12 22Z" />
                            <path fill="#FBBC05" d="M6.39 13.87A6 6 0 0 1 6.08 12c0-.65.11-1.28.31-1.87V7.52H3.04A10 10 0 0 0 2 12c0 1.61.38 3.14 1.04 4.48l3.35-2.61Z" />
                            <path fill="#EA4335" d="M12 6c1.47 0 2.79.51 3.83 1.5l2.87-2.87A9.62 9.62 0 0 0 12 2a10 10 0 0 0-8.96 5.52l3.35 2.61C7.18 7.76 9.39 6 12 6Z" />
                        </svg>
                        Continuar com Google
                    </a>
                    <div className="auth-divider"><span>ou entre com e-mail</span></div>
                </>
            )}

            <form onSubmit={submit} className="auth-form">
                <div>
                    <InputLabel htmlFor="email" value="E-mail" />
                    <TextInput id="email" type="email" name="email" value={data.email} autoComplete="username" isFocused onChange={(e) => setData('email', e.target.value)} required />
                    <InputError message={errors.email} />
                </div>
                <div>
                    <InputLabel htmlFor="password" value="Senha" />
                    <TextInput id="password" type="password" name="password" value={data.password} autoComplete="current-password" onChange={(e) => setData('password', e.target.value)} required />
                    <InputError message={errors.password} />
                </div>
                <div className="auth-options">
                    <label className="auth-remember">
                        <Checkbox name="remember" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
                        <span>Lembrar de mim</span>
                    </label>
                    {canResetPassword && <Link href={route('password.request')}>Esqueci minha senha</Link>}
                </div>
                <PrimaryButton className="auth-submit" disabled={processing}>Entrar</PrimaryButton>
            </form>

            <p className="auth-switch">Ainda não tem conta? <Link href={route('register')}>Criar conta</Link></p>
        </GuestLayout>
    );
}
