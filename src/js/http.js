(function (global) {
    'use strict';

    class HttpError extends Error {
        constructor(message, response, data) {
            super(message);
            this.name = 'HttpError';
            this.status = response?.status ?? 0;
            this.response = response ?? null;
            this.data = data ?? null;
        }
    }

    class HttpClient {
        constructor(options = {}) {
            this.baseUrl = options.baseUrl ?? window.location.origin;
            this.timeout = options.timeout ?? 10000;
            this.headers = options.headers ?? {};
        }

        get(url, options = {}) {
            return this.request(url, { ...options, method: 'GET' });
        }

        post(url, body, options = {}) {
            return this.request(url, { ...options, method: 'POST', body });
        }

        put(url, body, options = {}) {
            return this.request(url, { ...options, method: 'PUT', body });
        }

        delete(url, options = {}) {
            return this.request(url, { ...options, method: 'DELETE' });
        }

        async request(path, options = {}) {
            const url = new URL(path, this.baseUrl);
            const base = new URL(this.baseUrl, window.location.origin);
            if (url.origin !== base.origin) {
                throw new TypeError('Cross-origin requests are disabled by this HTTP client.');
            }

            Object.entries(options.query ?? {}).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    url.searchParams.set(key, String(value));
                }
            });

            const controller = new AbortController();
            const timeout = window.setTimeout(
                () => controller.abort(),
                options.timeout ?? this.timeout
            );
            const headers = new Headers({
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...this.headers,
                ...(options.headers ?? {}),
            });
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrf && !headers.has('X-CSRF-Token')) {
                headers.set('X-CSRF-Token', csrf);
            }

            let body = options.body;
            if (body !== undefined && body !== null
                && !(body instanceof FormData)
                && !(body instanceof URLSearchParams)
                && typeof body !== 'string') {
                headers.set('Content-Type', 'application/json');
                body = JSON.stringify(body);
            }

            try {
                const response = await fetch(url, {
                    method: options.method ?? 'GET',
                    headers,
                    body,
                    credentials: 'same-origin',
                    redirect: 'follow',
                    signal: options.signal ?? controller.signal,
                });
                const contentType = response.headers.get('content-type') ?? '';
                const data = contentType.includes('application/json')
                    ? await response.json()
                    : await response.text();
                if (!response.ok) {
                    const message = typeof data === 'object' && data?.message
                        ? data.message
                        : `Request failed with status ${response.status}.`;
                    throw new HttpError(message, response, data);
                }
                return data;
            } catch (error) {
                if (error?.name === 'AbortError') {
                    throw new HttpError('The request timed out.', null, null);
                }
                throw error;
            } finally {
                window.clearTimeout(timeout);
            }
        }
    }

    global.HttpError = HttpError;
    global.http = new HttpClient();
})(window);
