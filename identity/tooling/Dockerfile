FROM python:3.13-alpine@sha256:9ef1a599f863231c3d91e941389bfdc4a2d6070557243d54df1a4e57015b960e
WORKDIR /identity
COPY tooling/requirements.lock /requirements.lock
RUN pip install --no-cache-dir --require-hashes -r /requirements.lock
COPY scripts /identity/scripts
COPY config /identity/config
COPY tests /identity/tests
COPY versions.env /identity/versions.env
ENV PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1
ARG IDENTITY_REVISION=local
LABEL org.opencontainers.image.revision="$IDENTITY_REVISION"
LABEL org.opencontainers.image.source="https://github.com/pavelsur07/fin-dir-site"
USER 1000:1000
ENTRYPOINT ["python"]
