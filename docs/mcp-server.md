# JuraCMS MCP Server — implementation plan

Goal: allow ChatGPT to list, draft, update, and publish CMS posts remotely.

## Deployment
Public HTTPS endpoint: `https://example.com/mcp` on each JuraCMS instance (NOT github.com).
Use PHP 8.1+ and the existing PDO/setting infrastructure. No Node.js required.

## Authentication
Use OAuth 2.1 authorization-code + PKCE for remote ChatGPT connections.
Register OAuth clients, per-user authorization, redirect URI allowlist, token expiration and revocation.
Store hashes of access/refresh tokens, not plaintext; protect against CSRF.
Never expose API keys, passwords, or configuration files via MCP.
Require `posts:read`, `posts:write`, and separate `posts:publish` permissions.
Bind all write operations to authenticated CMS users with appropriate CMS roles.

## MCP tools (first milestone)
- `site_info` — site name, languages and capabilities.
- `posts_list` — paginated post list with status/language filters.
- `posts_get` — full post with metadata by identifier.
- `posts_create_draft` — title, slug, language, HTML, category, tags and meta.
- `posts_update` — update an existing draft with optimistic lock.
- `posts_publish` — explicit publish operation; keep an audit entry.

Second milestone: pages, categories, media uploads, SEO, menus.

## Protocol
Implement streamable HTTP MCP with JSON-RPC, `initialize`, `tools/list`, `tools/call`, version negotiation, proper structured tool errors, and session handling according to protocol.
Serve only over HTTPS and check `Origin` as required.
Do not ship an unauthenticated endpoint even for reading site drafts.

## Rollout/acceptance
1. Implement and test OAuth and the MCP protocol in a dedicated module `modules/MCP`.
2. Verify anonymous calls return 401 and users cannot exceed role/scopes.
3. Verify listing and draft creation for one account.
4. Verify publishing requires an explicit tool action and is logged.
5. Install module using the independent module updater, connect to ChatGPT, and test on staging before production.

This document describes scope; it is not a deployed or usable MCP endpoint yet.
