<section
    id="visitantes"
    class="section visitor-map-section"
    data-visitor-map
    data-visitor-map-endpoint="{{ route('visitors.map.index') }}"
    aria-labelledby="visitor-map-title"
>
    <div class="container-shell">
        <div class="section-header visitor-map-heading">
            <div>
                <span class="section-kicker">Visitantes pelo mundo</span>
                <h2 id="visitor-map-title">De onde este portfólio é acessado.</h2>
            </div>
            <p>Um retrato coletivo das regiões que passaram por aqui — sempre com autorização e sem armazenar coordenadas exatas.</p>
        </div>

        <div class="visitor-map-card">
            <div class="visitor-map-stage">
                <canvas
                    data-visitor-map-canvas
                    role="img"
                    aria-label="Mapa-múndi com as regiões aproximadas dos visitantes"
                ></canvas>
                <div class="visitor-map-loading" data-visitor-map-loading>Carregando o mapa…</div>
            </div>

            <div class="visitor-map-details">
                <div class="visitor-map-stats" aria-live="polite">
                    <div>
                        <strong data-visitor-map-total>0</strong>
                        <span>visitantes que autorizaram</span>
                    </div>
                    <div>
                        <strong data-visitor-map-regions>0</strong>
                        <span>regiões aproximadas</span>
                    </div>
                </div>

                <div class="visitor-map-privacy">
                    <span class="visitor-map-privacy-icon" aria-hidden="true">◎</span>
                    <div>
                        <strong>Privacidade por padrão</strong>
                        <p>A posição é arredondada para uma grade de aproximadamente 11 km. O mapa não guarda nome, endereço, IP ou coordenada exata, e remove registros sem atualização após 24 meses.</p>
                    </div>
                </div>

                <p class="visitor-map-status" data-visitor-map-status>
                    Você decide se quer adicionar sua região ao mapa nas preferências de cookies.
                </p>
                <button type="button" class="button button-secondary visitor-map-settings" data-cookie-settings-open>
                    Configurar privacidade
                </button>
            </div>
        </div>
    </div>
</section>
