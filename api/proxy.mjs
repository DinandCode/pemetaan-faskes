// Proxy keluar untuk OSRM & Nominatim.
// Runtime PHP di Vercel memakai OpenSSL lama (1.0.2) sehingga handshake HTTPS ke
// server modern gagal. Runtime Node memakai TLS modern, jadi panggilan keluar
// dilakukan di sini, lalu Laravel memanggil proxy ini.

const OSRM_BASE = 'https://router.project-osrm.org';
const NOMINATIM_SEARCH = 'https://nominatim.openstreetmap.org/search';

const OSRM_PARAMS = ['overview', 'geometries', 'steps', 'radiuses'];
const NOMINATIM_PARAMS = ['q', 'format', 'addressdetails', 'limit', 'countrycodes', 'viewbox', 'bounded'];

const COORDS_PATTERN = /^-?\d{1,3}(\.\d+)?,-?\d{1,3}(\.\d+)?;-?\d{1,3}(\.\d+)?,-?\d{1,3}(\.\d+)?$/;

function pick(searchParams, allowed) {
  const out = new URLSearchParams();
  for (const key of allowed) {
    const value = searchParams.get(key);
    if (value !== null && value.length <= 300) out.set(key, value);
  }
  return out;
}

export default async function handler(req, res) {
  const secret = process.env.OUTBOUND_PROXY_SECRET;

  if (!secret) {
    res.status(500).json({ error: 'OUTBOUND_PROXY_SECRET belum diatur.' });
    return;
  }

  if (req.method !== 'GET') {
    res.status(405).json({ error: 'Method tidak diizinkan.' });
    return;
  }

  if (req.headers['x-proxy-secret'] !== secret) {
    res.status(401).json({ error: 'Tidak diizinkan.' });
    return;
  }

  const { searchParams } = new URL(req.url, 'http://localhost');
  const target = searchParams.get('target');

  let upstream;
  const headers = { Accept: 'application/json' };

  if (target === 'osrm') {
    const coords = searchParams.get('coords') || '';
    if (!COORDS_PATTERN.test(coords)) {
      res.status(400).json({ error: 'Format koordinat tidak valid.' });
      return;
    }
    upstream = `${OSRM_BASE}/route/v1/driving/${coords}?${pick(searchParams, OSRM_PARAMS)}`;
  } else if (target === 'nominatim') {
    headers['User-Agent'] = req.headers['x-forward-user-agent'] || 'SIG-Faskes-Banyumas';
    upstream = `${NOMINATIM_SEARCH}?${pick(searchParams, NOMINATIM_PARAMS)}`;
  } else {
    res.status(400).json({ error: 'Target tidak dikenal.' });
    return;
  }

  try {
    const response = await fetch(upstream, {
      headers,
      signal: AbortSignal.timeout(8000),
    });
    const body = await response.text();

    res.setHeader('Content-Type', response.headers.get('content-type') || 'application/json');
    res.status(response.status).send(body);
  } catch (error) {
    res.status(502).json({ error: 'Gagal menghubungi server tujuan.', detail: String(error?.message || error) });
  }
}
