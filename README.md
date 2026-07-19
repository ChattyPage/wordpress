# ChattyPage Sections for WordPress

AI-designed page sections for the WordPress site you already have. Keep WordPress running your
content, theme, and plugins; let [ChattyPage](https://chattypage.com) design the parts that need
to look great.

**Plugin homepage:** https://chattypage.com/en/wordpress/

## What it does

- **Design with AI, in ChattyPage.** Describe the section you want, or point it at a page of
  your current site and let it redesign from your real content. Fine-tune by drawing on the
  design and telling it what to change.
- **Place it anywhere in WordPress.** A native Gutenberg block, an Elementor widget, or the
  `[chattypage section="…"]` shortcode. All three render the same thing.
- **Stays in sync.** Publish in ChattyPage and your WordPress site updates within seconds via a
  signed webhook. No copy-pasting, ever.
- **Fast and SEO-safe by construction.** Sections are fetched server-side and cached by your
  own WordPress, then served as plain inline HTML. No iframes, no client-side fetching, no
  builder runtime on the page. Search engines see the full content in your page source.
- **Redesign a page in one click.** From wp-admin, pick any of your pages; ChattyPage distills
  its real content (nothing invented) and designs a modern section from it.

## Installation

1. Download the plugin zip from the [plugin homepage](https://chattypage.com/en/wordpress/)
   (or install from the WordPress.org directory once listed) and activate it.
2. Create a free account at [chattypage.com](https://chattypage.com) and generate an API token
   under **My Account → Integrations**.
3. In wp-admin, open **ChattyPage**, paste the token, and click **Connect**.
4. Design sections in ChattyPage, then place them with the block, the widget, or the shortcode.

Deactivating the plugin simply stops rendering the sections; nothing else on your site is
touched, and your designs stay in your ChattyPage account.

## Service disclosure

This plugin connects to the ChattyPage service to function. It sends your site URL and section
requests to chattypage.com, authenticated with the API token you create in your account. Design
generation uses your account's credits. No visitor data is sent to ChattyPage: section HTML is
fetched server-to-server and cached locally, so your visitors only ever talk to your own site.
See the [terms](https://chattypage.com/terms) and [privacy policy](https://chattypage.com/privacy).

## Repository layout

| Path | Role |
|---|---|
| `chattypage/` | the plugin as shipped (WP.org SVN `trunk`) |
| `chattypage/includes/class-chattypage-api-client.php` | transport: all calls to the `/integration/v1/*` API (bearer token) |
| `chattypage/includes/class-chattypage-renderer.php` | THE caching funnel: section html + template fragments (transients; `flush_all` = the webhook-bust point) |
| `chattypage/includes/class-chattypage-head.php` | head assets: bundled Tailwind (preflight off) + scoped reset + article typography |
| `chattypage/includes/class-chattypage-template.php` + `templates/takeover.php` | the site-design takeover (`template_include`; owner's theme stays active, rollback = toggle) |
| `chattypage/includes/class-chattypage-rest.php` | `/wp-json/chattypage/v1/refresh` (HMAC webhook target) + `/sections` (picker feed) |
| `chattypage/includes/class-chattypage-admin.php` | connect screen, site-design toggle, redesign card, section browser, cache controls |
| shortcode + `chattypage/blocks/section` + Elementor widget | three placements over the same renderer output |
| `docker-compose.yml` | local WordPress (:8080) with the plugin mounted |
| `scripts/e2e-local.sh` | end-to-end harness against a local ChattyPage dev stack |

The mental model: **WordPress owns all pages, posts, URLs, menus, and content; ChattyPage owns
the design.** Design HTML is pulled server-to-server and cached locally; publishing in
ChattyPage sends a signed ping that busts the caches. No iframes, no client-side fetching, no
page mirroring.

Publishing in ChattyPage triggers a signed webhook to `/refresh`, which busts the section
transients, so connected sites update within one request.

## Development

```bash
docker compose up -d     # WordPress on :8080 (install wizard on first run)
# activate the plugin in wp-admin, connect with a token from your dev ChattyPage stack
./scripts/e2e-local.sh   # drives token mint → connect → place → publish → HMAC refresh
```

The dev compose points the plugin at a local API via `CHATTYPAGE_API_BASE`
(`http://host.docker.internal:3000`); production builds default to `https://chattypage.com/api`.

Releasing to WordPress.org: copy `chattypage/` into the SVN `trunk/`, tag the release, and bump
`Stable tag` in `chattypage/readme.txt`.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE). WordPress plugin headers and `readme.txt` carry the
same license, as required for WordPress.org distribution.
