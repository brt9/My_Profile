# Baseline de qualidade da home

Medição local em 20 de junho de 2026, usando Lighthouse em modo headless contra a build de produção servida em `http://127.0.0.1:8085`.

## Resultado da Release 1

| Métrica | Antes | Depois |
|---|---:|---:|
| Performance | 81 | 96 |
| Acessibilidade | 96 | 96 |
| Boas práticas | 100 | 100 |
| SEO | 100 | 100 |
| LCP | 3.295 ms | 2.436 ms |
| TTFB | 3.458 ms | 109 ms |
| CLS | 0 | 0 |
| TBT | 0 ms | 0 ms |

Principais correções:

- leitura de snapshots locais na home e atualização das integrações após a resposta;
- remoção do preset TailStackUI não utilizado no CSS público;
- separação do CSS da área autenticada;
- remoção do Axios do bundle público;
- dimensões reservadas e fallback para imagens da Steam.
- compressão HTTP no inicializador local e foto principal servida localmente.

O resultado é uma baseline, não uma garantia de produção. Servidor, rede, CDN, compressão e máquina do visitante alteram a medição. O gate adotado é Performance e Acessibilidade maiores ou iguais a 95, LCP menor que 2,5 s e CLS menor que 0,1.

## Comandos de reprodução

```powershell
npm run build
npm run test:responsive
npx lighthouse http://127.0.0.1:8085 --only-categories=performance --only-categories=accessibility --only-categories=best-practices --only-categories=seo
```
