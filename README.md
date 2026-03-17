# NeoChirp

> **This is an independent fork of [Chirp](https://github.com/actuallyaridan/chirp) by Adnan Bukvic.**
> NeoChirp is not affiliated with, endorsed by, or connected to the original Chirp team in any way.
> Credit to the original authors — original MIT license preserved.

A self-hosted, invite-only social network. Run it on anything — a Raspberry Pi, an old Optiplex, a VPS — and expose it to the internet via Cloudflare Tunnels.

---

## What's new in NeoChirp

| Feature | Status |
|---|---|
| **Latest / What's Hot / Following** feeds | ✅ |
| **Semantic Trends** via Ollama embeddings | ✅ |
| **Like & Rechirp** with notifications | ✅ |
| **Grouped Notifications** with green dot | ✅ |
| **REST API** with personal API keys | ✅ |
| **OAuth 2.0** — third-party app authorization | ✅ |
| `via` field — shows client source on every post | ✅ |
| **Dev Console** with dummy post generator | ✅ |
| **API Docs** at `/docs/` | ✅ |
| **Docker** — one-command production deploy | ✅ |
| **Cloudflare Tunnel** — no port forwarding needed | ✅ |
| **Invite-only** — you control who joins | ✅ |

---

## Quick Start (Docker + Cloudflare Tunnel)

### 1. Prerequisites

- [Docker](https://docs.docker.com/get-docker/) + [Docker Compose](https://docs.docker.com/compose/)
- A [Cloudflare](https://cloudflare.com) account with your domain pointed at it
- (Optional) [Ollama](https://ollama.com) for semantic trend analysis

### 2. Clone & configure

```bash
git clone https://github.com/j4ckxyz/neochirp
cd neochirp
cp .env.example .env
```

Edit `.env`:

```env
APP_DOMAIN=chirp.j4ck.xyz       # your domain
APP_HTTPS=true                   # always true with Cloudflare
DEV_MODE=false                   # NEVER true in production
CLOUDFLARE_TUNNEL_TOKEN=...      # from Cloudflare Zero Trust dashboard
OLLAMA_HOST=http://172.17.0.1:11434  # host machine's Ollama (Linux)
```

### 3. Set up the Cloudflare Tunnel

1. Go to [Cloudflare Zero Trust](https://one.dash.cloudflare.com/) → **Tunnels** → **Create a tunnel**
2. Name it (e.g. `neochirp`), click Save
3. Copy the **tunnel token** → paste into `.env` as `CLOUDFLARE_TUNNEL_TOKEN`
4. In the tunnel's **Public Hostnames** tab, add:
   - **Subdomain:** `chirp` (or whatever matches your domain)
   - **Domain:** `j4ck.xyz`
   - **Service:** `http://app:80`

### 4. Start

```bash
docker-compose up -d
```

The app starts, initialises the database automatically, and is immediately accessible at your domain through the tunnel.

### 5. Generate your first invite codes

You are the only admin. Generate invite codes to let people join:

```bash
# Generate 1 code (default)
docker-compose exec app php /var/www/html/scripts/create_invite.php

# Generate 5 codes at once
docker-compose exec app php /var/www/html/scripts/create_invite.php 5
```

Output:
```
  NeoChirp Invite Codes
  ─────────────────────
  A3F9-B2C1-D4E5
  9AB0-1234-5678

  Share each code with exactly one person.
  Codes are single-use and are burned on signup.
```

### 6. Create your admin account

Visit `https://chirp.j4ck.xyz/signup` — use one of the invite codes you just generated.

---

## Local development

No Docker needed for local dev:

```bash
git clone https://github.com/j4ckxyz/neochirp
cd neochirp
cp chirp.db.sample ../chirp.db   # DB lives one level above project root
```

Set `DEV_MODE=true` in `.env` OR temporarily edit `config.php`, then:

```bash
php -d display_errors=Off -S localhost:8080
```

Visit `http://localhost:8080` — in dev mode signup requires no invite code.

### Dev Console

Go to **Settings → Dev Console** (visible only in dev mode) to:
- Generate random posts to populate feeds
- Rescan trends (runs the Ollama embedding pipeline)

---

## Ollama (Semantic Trends)

NeoChirp uses a local embedding model to deduplicate trending topics semantically (e.g. "JS" and "JavaScript" count as the same trend).

```bash
# Install Ollama: https://ollama.com
ollama pull qwen3-embedding:0.6b
```

In Docker on Linux, Ollama running on the host is reachable at `http://172.17.0.1:11434`. Set `OLLAMA_HOST` in `.env` accordingly. If Ollama isn't running, trends fall back gracefully to plain word frequency.

---

## API

Full documentation lives at `/docs/` on your instance.

### Authentication

Generate an API key at **Settings → API Keys**. Pass it as a Bearer token:

```bash
curl https://chirp.j4ck.xyz/api/v1/feed/latest.php \
  -H "Authorization: Bearer chirp_your_key_here"
```

### OAuth 2.0

Third-party apps can request access on behalf of users.

1. Register your app at **Settings → Developer Apps**
2. Redirect users to `/oauth/authorize?client_id=...&redirect_uri=...&response_type=code&state=...`
3. Exchange the code for a token at `POST /oauth/token`
4. Use the token exactly like an API key (`chirpoa_...` prefix)

Users can revoke app access at **Settings → Authorized Apps** at any time.

### Key endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/feed/latest.php` | Latest chirps |
| GET | `/api/v1/feed/hot.php` | Trending chirps |
| GET | `/api/v1/chirp/getChirp.php?id=N` | Single chirp |
| GET | `/api/v1/chirp/getReplies.php?id=N` | Replies |
| GET | `/api/v1/likes/getLikes.php?id=N` | Who liked |
| GET | `/api/v1/rechirps/getRechirps.php?id=N` | Who rechirped |
| POST | `/api/v1/chirp/post.php` | Post a chirp |
| POST | `/api/v1/chirp/reply.php` | Reply |
| POST | `/api/v1/chirp/like.php` | Like / unlike |
| POST | `/api/v1/chirp/rechirp.php` | Rechirp / undo |
| GET | `/api/v1/user/getUser.php?username=X` | User profile |
| GET | `/api/v1/notifications/get.php` | Notifications |

---

## Security notes

- **Invite-only** — no one can sign up without a code you generate
- **HTTPS always** — Cloudflare handles TLS termination; the tunnel encrypts traffic end-to-end
- **PHP errors never reach the browser** — logged to Docker stderr only
- **API keys stored as SHA-256 hashes** — raw keys are never written to the DB
- **OAuth secrets hashed** — same pattern; shown to the developer exactly once
- **SQLite DB outside web root** — mounted at `/var/www/chirp.db`, not accessible via HTTP
- **Rate limiting** — 300 reads / 100 writes per hour per API key / OAuth token
- **Session hardening** — `cookie_httponly` and `use_strict_mode` enforced in PHP config

---

## Updating

```bash
git pull
docker-compose build --no-cache
docker-compose up -d
```

The database volume persists across rebuilds — your data is safe.

---

## Credits

- Original [Chirp](https://github.com/actuallyaridan/chirp) by [Adnan Bukvic](https://aridan.net) — MIT License
- Emoji: [Twemoji](https://github.com/twitter/twemoji) by Twitter/X — CC-BY 4.0
- NeoChirp additions by [@jglypt](https://github.com/j4ckxyz)
