import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <main className="auth-shell">
            <section className="auth-intro" aria-label="Apresentação">
                <Link href="/" className="auth-brand" aria-label="Voltar ao portfólio">
                    <ApplicationLogo className="auth-logo" />
                    <span>pedrofelipe<strong>.dev</strong></span>
                </Link>
                <div className="auth-intro-copy">
                    <span className="auth-kicker">Área reservada</span>
                    <h1>Projetos, agenda e integrações em um só lugar.</h1>
                    <p>Entre para administrar os dados do portfólio com segurança.</p>
                </div>
                <p className="auth-back-link"><Link href="/">← Voltar ao portfólio</Link></p>
            </section>

            <section className="auth-form-side">
                <div className="auth-card">{children}</div>
            </section>
        </main>
    );
}
