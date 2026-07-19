// Author one section on the connection designable through the NORMAL editor API (as the user
// would in the app): create a block on the Sections page, save draft HTML, publish drafts.
// Prints the section block id. Uses the session persisted by mint-token.mjs.
import { readFileSync } from 'node:fs';

const API = process.env.API || 'http://localhost:3000';
const [designableId, pageId] = process.argv.slice(2);
if (!designableId || !pageId) throw new Error('usage: author-section.mjs <designableId> <pageId>');
const cookie = readFileSync(new URL('./.e2e-session', import.meta.url), 'utf8');

const call = async (method, path, body) => {
    const res = await fetch(`${API}/${path}`, {
        method, headers: { 'content-type': 'application/json', cookie },
        body: body === undefined ? undefined : JSON.stringify(body),
    });
    if (!res.ok) throw new Error(`${method} ${path} -> ${res.status}: ${await res.text()}`);
    return res.json().catch(() => ({}));
};

const created = await call('PUT', `designables/${designableId}/blocks`, {
    block: { name: 'E2E hero' }, pageId,
});
const blockId = created.response?.block?._id ?? created.response?._id;
if (!blockId) throw new Error('no block id in create response');

await call('POST', `designables/${designableId}/blocks/${blockId}/html`, {
    html: '<section class="e2e-hero" style="padding:2rem;background:#123;color:#fff">E2E hello from ChattyPage</section>',
});
await call('POST', `designables/${designableId}/publish`);
console.log(blockId);
