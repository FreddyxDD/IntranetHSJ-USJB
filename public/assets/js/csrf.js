(() => {
  const token = document.querySelector('meta[name="csrf-token"]')?.content;

  if (!token || typeof window.fetch !== "function") return;

  const originalFetch = window.fetch.bind(window);
  const safeMethods = new Set(["GET", "HEAD", "OPTIONS"]);

  window.fetch = (input, init = {}) => {
    const request = input instanceof Request ? input : null;
    const method = String(init.method || request?.method || "GET").toUpperCase();
    const target = new URL(request?.url || String(input), window.location.href);

    if (safeMethods.has(method) || target.origin !== window.location.origin) {
      return originalFetch(input, init);
    }

    const headers = new Headers(request?.headers || {});
    new Headers(init.headers || {}).forEach((value, key) => headers.set(key, value));
    headers.set("X-CSRF-TOKEN", token);
    headers.set("X-Requested-With", "XMLHttpRequest");
    headers.set("Accept", headers.get("Accept") || "application/json");

    return originalFetch(input, { ...init, headers });
  };
})();
