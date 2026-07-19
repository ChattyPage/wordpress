// Mint a fresh integration token against the DEV ChattyPage API: register + verify a throwaway
// user (via the admin test-helper endpoint, master API_KEY from ../saltion.com/api/.env), then
// POST /integration/tokens. Prints the plaintext token.
import { readFileSync } from 'node:fs';

const API = process.env.API || 'http://localhost:3000';
const envFile = readFileSync(new URL('../../saltion.com/api/.env', import.meta.url), 'utf8');
const API_KEY = envFile.match(/^API_KEY=(.*)$/m)?.[1]?.trim();
if (!API_KEY) throw new Error('API_KEY not found in saltion.com/api/.env');

const j = (r) => r.json();
const email = `wp-e2e-${Date.now()}@example.com`;

const reg = await fetch(`${API}/auth/register`, {
    method: 'POST', headers: { 'content-type': 'application/json' },
    body: JSON.stringify({ email, password: 'wp-e2e-password-1' }),
});
if (reg.status !== 201) throw new Error(`register ${reg.status}`);
const cookie = reg.headers.getSetCookie().map((c) => c.split(';')[0]).join('; ');
const userId = (await j(reg)).response.user._id;

const tok = await j(await fetch(`${API}/admin/test/action-token`, {
    method: 'POST', headers: { 'content-type': 'application/json', 'x-api-key': API_KEY },
    body: JSON.stringify({ userId, purpose: 'verify' }),
}));
const verify = await fetch(`${API}/auth/verify`, {
    method: 'POST', headers: { 'content-type': 'application/json' },
    body: JSON.stringify({ token: tok.response.token }),
});
if (verify.status !== 200) throw new Error(`verify ${verify.status}`);

const mint = await j(await fetch(`${API}/integration/tokens`, {
    method: 'POST', headers: { 'content-type': 'application/json', cookie },
    body: JSON.stringify({ label: 'Local E2E site' }),
}));
console.log(JSON.stringify({ token: mint.response.token, cookieFile: null }));

// Persist the session cookie so author-section.mjs can act as the same user.
import { writeFileSync } from 'node:fs';
writeFileSync(new URL('./.e2e-session', import.meta.url), cookie);
