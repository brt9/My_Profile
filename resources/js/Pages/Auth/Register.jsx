import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', email: '', password: '', password_confirmation: '' });
    const submit = (event) => {
        event.preventDefault();
        post(route('register'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <GuestLayout>
            <Head title="Criar conta" />
            <header className="auth-card-head">
                <span className="auth-kicker">Primeiro acesso</span>
                <h2>Crie sua conta</h2>
                <p>Preencha os dados abaixo. A verificação por e-mail protege o acesso.</p>
            </header>

            <form onSubmit={submit} className="auth-form">
                <div>
                    <InputLabel htmlFor="name" value="Nome" />
                    <TextInput id="name" name="name" value={data.name} autoComplete="name" isFocused onChange={(e) => setData('name', e.target.value)} required />
                    <InputError message={errors.name} />
                </div>
                <div>
                    <InputLabel htmlFor="email" value="E-mail" />
                    <TextInput id="email" type="email" name="email" value={data.email} autoComplete="username" onChange={(e) => setData('email', e.target.value)} required />
                    <InputError message={errors.email} />
                </div>
                <div>
                    <InputLabel htmlFor="password" value="Senha" />
                    <TextInput id="password" type="password" name="password" value={data.password} autoComplete="new-password" onChange={(e) => setData('password', e.target.value)} required />
                    <InputError message={errors.password} />
                </div>
                <div>
                    <InputLabel htmlFor="password_confirmation" value="Confirmar senha" />
                    <TextInput id="password_confirmation" type="password" name="password_confirmation" value={data.password_confirmation} autoComplete="new-password" onChange={(e) => setData('password_confirmation', e.target.value)} required />
                    <InputError message={errors.password_confirmation} />
                </div>
                <PrimaryButton className="auth-submit" disabled={processing}>Criar conta</PrimaryButton>
            </form>

            <p className="auth-switch">Já possui cadastro? <Link href={route('login')}>Entrar</Link></p>
        </GuestLayout>
    );
}
