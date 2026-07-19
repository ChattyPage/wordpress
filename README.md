# ChattyPage Sections, WordPress plugin

The free WordPress plugin for [ChattyPage](https://chattypage.com): AI-designed sections on an
existing WordPress site. The user keeps WordPress (content, theme, hosting); ChattyPage owns the
design-heavy sections, which the plugin fetches server-side, caches, and serves inline.

- `chattypage/` is the plugin as shipped (the WP.org SVN `trunk`).
- `docker-compose.yml` runs a local WordPress with the plugin mounted, wired to a local
  ChattyPage dev stack (`http://host.docker.internal:3000`).

## Architecture

| Piece | File | Role |
|---|---|---|
| API client | `includes/class-chattypage-api-client.php` | all calls to `/integration/v1/*` (bearer token) |
| Renderer | `includes/class-chattypage-renderer.php` | single render funnel: transient cache -> server-side fetch -> inline HTML |
| REST | `includes/class-chattypage-rest.php` | `/wp-json/chattypage/v1/refresh` (HMAC webhook target) + `/sections` (picker feed) |
| Admin | `includes/class-chattypage-admin.php` | connect screen, section browser, cache controls |
| Placements | shortcode + `blocks/section` (Gutenberg) + `class-chattypage-elementor-widget.php` | three ways to place the same renderer output |

Publishing in ChattyPage triggers a signed webhook to `/refresh`, which busts the transients, so
sections update on the WordPress site within one request.

## Development

```bash
docker compose up -d          # WordPress on :8080, first run shows the install wizard
# activate the plugin in wp-admin, then connect with a token minted from the local app
```

Release to WP.org: copy `chattypage/` into the SVN `trunk/`, tag, bump `Stable tag` in readme.txt.
