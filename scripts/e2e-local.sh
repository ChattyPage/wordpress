#!/usr/bin/env bash
# Local E2E for the ChattyPage Sections plugin against the dev ChattyPage stack.
#
# Drives the REAL flows end to end:
#   1. mints an integration token via the dev API (fresh registered+verified user),
#   2. installs WordPress (wp-cli) + activates the plugin,
#   3. connects the plugin with the token (same code path as the admin form),
#   4. places a section via shortcode AND Gutenberg block markup on a page,
#   5. authors + publishes section HTML in ChattyPage, then verifies the page serves it inline,
#   6. exercises the signed refresh endpoint (good + bad signature).
#
# Requires: dev api on :3000 (docker compose in saltion.com), this repo's compose up (:8080).
set -euo pipefail
cd "$(dirname "$0")/.."

API=${API:-http://localhost:3000}
WP=${WP:-http://localhost:8080}

wpcli() { docker compose run --rm --user 33:33 -e HOME=/tmp cli wp "$@"; }

echo "── 1. mint token via dev API"
TOKEN_JSON=$(node scripts/mint-token.mjs)
TOKEN=$(echo "$TOKEN_JSON" | grep -o 'cp_live_[A-Za-z0-9_-]*')
echo "   token: ${TOKEN:0:16}…"

echo "── 2. install WordPress + activate plugin"
wpcli core install --url="$WP" --title="Plugin E2E" --admin_user=admin \
  --admin_password=admin-e2e-pass --admin_email=admin@example.com --skip-email 2>/dev/null || true
wpcli plugin activate chattypage
wpcli theme activate twentytwentyfive 2>/dev/null || true
# Pretty permalinks so /wp-json/ routes (fresh installs default to plain, which 200s everything).
wpcli rewrite structure '/%postname%/' --hard 2>/dev/null || wpcli rewrite structure '/%postname%/'

echo "── 3. connect plugin (same path as the admin form)"
wpcli eval "
  \$r = ChattyPage_Api_Client::connect('$TOKEN');
  if ( is_wp_error(\$r) ) { fwrite(STDERR, \$r->get_error_message() . PHP_EOL); exit(1); }
  \$s = ChattyPage_Api_Client::settings();
  echo 'designable=' . \$s['designable_id'] . PHP_EOL;
"
DESIGNABLE=$(wpcli eval "echo ChattyPage_Api_Client::settings()['designable_id'];")
PAGEID=$(wpcli eval "echo ChattyPage_Api_Client::settings()['page_id'];")
echo "   designable: $DESIGNABLE page: $PAGEID"

echo "── 4. author + publish a section in ChattyPage (editor API, as the user would)"
SECTION=$(node scripts/author-section.mjs "$DESIGNABLE" "$PAGEID")
echo "   section block: $SECTION"

echo "── 5. place via shortcode + block, then fetch the page"
POST_ID=$(wpcli post create --post_title='E2E sections' --post_status=publish --porcelain \
  --post_content="[chattypage section=\"$SECTION\"]<!-- wp:chattypage/section {\"sectionId\":\"$SECTION\"} /-->")
PERMALINK=$(wpcli post url "$POST_ID" | tr -d '\r')
HTML=$(curl -sS "$PERMALINK")
echo "$HTML" | grep -q "E2E hello from ChattyPage" || { echo "FAIL: section HTML not inline"; exit 1; }
COUNT=$(echo "$HTML" | grep -o "E2E hello from ChattyPage" | wc -l)
echo "   OK: section appears inline ($COUNT placements)"

echo "── 6. refresh endpoint: bad signature rejected, good signature flushes"
BAD=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$WP/wp-json/chattypage/v1/refresh" \
  -H 'Content-Type: application/json' -H 'X-ChattyPage-Signature: deadbeef' -H "X-ChattyPage-Timestamp: $(date +%s)" \
  -d '{"blockIds":[],"ts":0}')
[ "$BAD" = "403" ] || { echo "FAIL: bad signature not rejected ($BAD)"; exit 1; }
SECRET=$(wpcli eval "echo ChattyPage_Api_Client::settings()['webhook_secret'];")
TS=$(date +%s)
BODY="{\"blockIds\":[\"$SECTION\"],\"ts\":$TS}"
SIG=$(printf '%s' "$TS.$BODY" | openssl dgst -sha256 -hmac "$SECRET" -hex | sed 's/^.*= //')
GOOD=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$WP/wp-json/chattypage/v1/refresh" \
  -H 'Content-Type: application/json' -H "X-ChattyPage-Signature: $SIG" -H "X-ChattyPage-Timestamp: $TS" \
  -d "$BODY")
[ "$GOOD" = "200" ] || { echo "FAIL: good signature rejected ($GOOD)"; exit 1; }
echo "   OK: refresh endpoint verifies HMAC"

echo "ALL E2E CHECKS PASSED"
