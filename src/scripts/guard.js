export async function requireAuth(next) {
  try {
    const res = await fetch('/api/auth/me', { credentials: 'include' });
    const d = await res.json().catch(() => ({}));
    if (!d?.authenticated) {
      const q = next ? `?next=${encodeURIComponent(next)}` : '';
      location.href = `/app/login${q}`;
      return false;
    }
    return true;
  } catch {
    location.href = `/app/login${next ? `?next=${encodeURIComponent(next)}` : ''}`;
    return false;
  }
}
