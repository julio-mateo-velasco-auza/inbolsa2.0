export async function api(path, opts = {}) {
  const res = await fetch(path, {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', ...(opts.headers || {}) },
    ...opts
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw Object.assign(new Error(data?.error || 'API error'), { data, status: res.status });
  return data;
}

export const Auth = {
  me: () => api('/api/auth/me'),
  login: (email, password) => api('/api/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) }),
  logout: () => api('/api/auth/logout', { method: 'POST' }),
};

export const QR = {
  create: (payload) => api('/api/qr', { method: 'POST', body: JSON.stringify(payload) }),
  list: () => api('/api/qr/list'),
  revoke: (code) => api('/api/qr/revoke', { method: 'POST', body: JSON.stringify({ code }) }),
  validate: (code) => api(`/api/qr/validate?code=${encodeURIComponent(code)}`),
};
