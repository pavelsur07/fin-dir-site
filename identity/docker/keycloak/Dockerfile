FROM quay.io/keycloak/keycloak:26.8.2@sha256:7e66a96e4739239070aeaf72e4c5decf0f7f682cfc725b9f7ea2b336eaba38b3 AS builder
ENV KC_DB=postgres KC_HEALTH_ENABLED=true KC_METRICS_ENABLED=true
COPY config/keycloak.conf /opt/keycloak/conf/keycloak.conf
RUN /opt/keycloak/bin/kc.sh build
FROM quay.io/keycloak/keycloak:26.8.2@sha256:7e66a96e4739239070aeaf72e4c5decf0f7f682cfc725b9f7ea2b336eaba38b3
COPY --from=builder /opt/keycloak/ /opt/keycloak/
COPY --chmod=755 scripts/keycloak-entrypoint.sh /identity-entrypoint.sh
ARG IDENTITY_REVISION=local
LABEL org.opencontainers.image.revision="$IDENTITY_REVISION"
LABEL org.opencontainers.image.source="https://github.com/pavelsur07/fin-dir-site"
USER 1000
ENTRYPOINT ["/identity-entrypoint.sh"]
CMD ["start", "--optimized"]
