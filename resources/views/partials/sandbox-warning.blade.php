<div data-sandbox-warning hidden role="alert" style="position:fixed;z-index:2147483647;inset:16px auto auto 50%;width:min(680px,calc(100% - 32px));transform:translateX(-50%);border:2px solid #ff7a59;border-radius:14px;background:#18181e;color:#fff;padding:18px 20px;font:600 15px/1.5 system-ui,sans-serif;box-shadow:0 20px 60px rgba(0,0,0,.35)">
    <strong style="display:block;margin-bottom:6px;font-size:17px">Esta aba está sendo exibida em um frame sandboxado.</strong>
    <span>A aplicação está tentando abrir a agenda fora desse frame. Se a navegação não ocorrer automaticamente, use:</span>
    <a href="{{ url('/dashboard') }}" target="_blank" rel="noopener noreferrer" style="display:block;margin-top:8px;color:#c9bdff;text-decoration:underline;overflow-wrap:anywhere">{{ url('/dashboard') }}</a>
</div>

<script>
    (() => {
        const escapeMessage = 'myprofile:escape-sandbox';
        const directUrl = @js(url('/dashboard'));

        const removeSandboxedFrames = () => {
            if (window.top !== window || !document.body) return;

            document.querySelectorAll('iframe[srcdoc], iframe[sandbox]').forEach((frame) => {
                const sandbox = frame.getAttribute('sandbox') ?? '';
                const srcdoc = frame.getAttribute('srcdoc') ?? '';

                if (srcdoc || !sandbox.includes('allow-forms') || !sandbox.includes('allow-same-origin')) {
                    frame.remove();
                }
            });
        };

        if (window.top === window) {
            removeSandboxedFrames();

            new MutationObserver(removeSandboxedFrames).observe(document.documentElement, {
                childList: true,
                subtree: true,
            });
        }

        window.addEventListener('message', (event) => {
            if (window.top !== window || event.data?.type !== escapeMessage) return;

            let target;
            try {
                target = new URL(event.data.url, window.location.origin);
            } catch {
                return;
            }

            if (target.origin !== window.location.origin) return;

            const sourceFrame = [...document.querySelectorAll('iframe')]
                .find((frame) => frame.contentWindow === event.source);

            if (target.href === window.location.href) {
                sourceFrame?.remove();
                return;
            }

            window.location.assign(target.href);
        });

        if (window.location.origin !== 'null' && window.location.protocol !== 'about:') return;

        document.querySelector('[data-sandbox-warning]')?.removeAttribute('hidden');
        document.addEventListener('submit', (event) => event.preventDefault(), true);

        if (window.parent !== window) {
            window.parent.postMessage({ type: escapeMessage, url: directUrl }, '*');
        }
    })();
</script>
