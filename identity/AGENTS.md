# Identity component

Identity is an independent vendor IAM service in the monorepo. Root AGENTS applies;
Symfony business-layer/UI rules apply to site/, not to Keycloak vendor code.

- Use root make identity-* wrappers; implementations live in identity/Makefile.
- Keycloak owns authentication/token issuance; applications own their data/object authorization.
- No custom crypto, password grant for application clients, experimental features or core fork by default.
- Only declared owned clients/scopes/mappers may be reconciled. Never adopt/delete unknown resources.
- Every client/environment/API has distinct credentials and exact issuer/audience binding.
- Never print response bodies, credentials, tokens or private keys in scripts, pytest failures or CI.
- Runtime secrets live outside git/images/artifacts. Local fixtures are under ignored .runtime/.
- Production artifacts contain identity/ only; runtime must not depend on ../site or the root checkout.
- PostgreSQL is private and Keycloak uses a nonsuperuser database role.
- Public Admin/management endpoints stay closed; operator access is private. Require MFA for human admins.
- Tests use a disposable real issuer and the real Symfony verifier; site source is a test-only dependency.
- Meaningful tests per changed behavior, one independent security review before release, no implementer agents.
- Deploy/credentials/DNS/DB changes require authorized scope; user authorized Identity deployment on the current site host.
- Keep site/Conwix/Traefik releases independent. Do not change their state through Identity up/down/rollback.
