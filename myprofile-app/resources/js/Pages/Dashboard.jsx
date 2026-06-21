import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

const routes = [
    ['GET', '/api/calendar/events', 'Listar compromissos locais'],
    ['POST', '/api/calendar/events', 'Criar compromisso'],
    ['PUT', '/api/calendar/events/{id}', 'Editar compromisso'],
    ['DELETE', '/api/calendar/events/{id}', 'Cancelar compromisso'],
    ['GET', '/admin/calendar/connect', 'Conectar ou renovar o Google'],
    ['POST', '/admin/calendar/sync', 'Solicitar sincronização'],
    ['DELETE', '/admin/calendar/revoke', 'Revogar o Google'],
];

export default function Dashboard({
    canManageCalendar,
    googleCalendarConfigured,
    googleCalendarConnected,
    googleCalendarWriteEnabled,
}) {
    return (
        <AuthenticatedLayout
            header={<div><span className="dashboard-kicker">Área administrativa</span><h1 className="dashboard-title">Painel do portfólio</h1></div>}
        >
            <Head title="Painel" />

            <div className="dashboard-shell">
                <section className="dashboard-welcome">
                    <div>
                        <span className="dashboard-kicker">Calendário</span>
                        <h2>Rotas e integrações em um só lugar.</h2>
                        <p>O banco local é a fonte principal. O Google complementa a agenda quando estiver autorizado.</p>
                    </div>
                    <a className="dashboard-primary-link" href="/#agenda">Abrir agenda pública →</a>
                </section>

                <div className="dashboard-status-grid">
                    <article className="dashboard-card">
                        <span className="dashboard-card-label">Acesso</span>
                        <strong>{canManageCalendar ? 'Administrador' : 'Usuário autenticado'}</strong>
                        <p>{canManageCalendar ? 'CRUD e integrações liberados.' : 'A agenda pública está disponível; rotas administrativas exigem o e-mail configurado.'}</p>
                    </article>
                    <article className="dashboard-card">
                        <span className="dashboard-card-label">Google Agenda</span>
                        <strong>{googleCalendarConnected ? 'Conectado' : googleCalendarConfigured ? 'Pronto para conectar' : 'Não configurado'}</strong>
                        <p>{googleCalendarWriteEnabled ? 'Permissão de leitura e escrita ativada.' : 'Permissão somente de leitura.'}</p>
                    </article>
                </div>

                {canManageCalendar ? (
                    <section className="dashboard-routes-card">
                        <div className="dashboard-routes-head">
                            <div>
                                <span className="dashboard-kicker">Referência rápida</span>
                                <h2>Rotas do calendário</h2>
                            </div>
                            <div className="dashboard-route-actions">
                                <a href={route('calendar.events.index')}>Ver eventos JSON</a>
                                {googleCalendarConfigured && <a href={route('calendar.connect')}>{googleCalendarConnected ? 'Renovar Google' : 'Conectar Google'}</a>}
                            </div>
                        </div>

                        <div className="dashboard-route-list">
                            {routes.map(([method, path, purpose]) => (
                                <div className="dashboard-route" key={`${method}-${path}`}>
                                    <span className={`dashboard-method method-${method.toLowerCase()}`}>{method}</span>
                                    <code>{path}</code>
                                    <span>{purpose}</span>
                                </div>
                            ))}
                        </div>
                    </section>
                ) : (
                    <section className="dashboard-routes-card dashboard-no-access">
                        <h2>Rotas administrativas protegidas</h2>
                        <p>Entre com a conta definida em <code>PORTFOLIO_ADMIN_EMAIL</code> para gerenciar compromissos e conectar o Google.</p>
                    </section>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
