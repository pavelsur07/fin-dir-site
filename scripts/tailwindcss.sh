#!/bin/sh

set -eu

TAILWIND_VERSION='4.3.3'
REPOSITORY_ROOT=$(CDPATH='' cd -- "$(dirname -- "$0")/.." && pwd)
CACHE_DIRECTORY=${TAILWIND_CACHE_DIRECTORY:-"${REPOSITORY_ROOT}/site/var/tools"}

case "$(uname -s):$(uname -m)" in
    Linux:x86_64|Linux:amd64)
        ASSET='tailwindcss-linux-x64'
        CHECKSUM='dc61b3ac6b8c9ca874c0cc4c57b2409791a64c5540404ca5f5367360babc313a'
        if ldd --version 2>&1 | grep -qi musl; then
            ASSET='tailwindcss-linux-x64-musl'
            CHECKSUM='a04d34ceacc8f52cbe8920ad846cdeb61d3d0021dba32db0d1f77c9d9fad7a6c'
        fi
        ;;
    Linux:aarch64|Linux:arm64)
        ASSET='tailwindcss-linux-arm64'
        CHECKSUM='55fd0b241214eff3de1e8ee4f22796662f2d2e7a49bcfca7477cfd0bac398195'
        if ldd --version 2>&1 | grep -qi musl; then
            ASSET='tailwindcss-linux-arm64-musl'
            CHECKSUM='71ea4be79c9de9827545682df3e040053fb535d37c71ed2cfdedf9385a0868e0'
        fi
        ;;
    Darwin:arm64)
        ASSET='tailwindcss-macos-arm64'
        CHECKSUM='cdf646702987a743464dff4d9c60fd4480d1c1e73dd819a9a67f1078815dce9d'
        ;;
    Darwin:x86_64)
        ASSET='tailwindcss-macos-x64'
        CHECKSUM='7922e0953f2110c05976e3bf58f14e643d90427575e766b7d433f5f80cbee7e1'
        ;;
    *)
        echo "Unsupported Tailwind CLI platform: $(uname -s) $(uname -m)" >&2
        exit 1
        ;;
esac

checksum_file() {
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$1" | cut -d ' ' -f 1
    else
        shasum -a 256 "$1" | cut -d ' ' -f 1
    fi
}

mkdir -p "$CACHE_DIRECTORY"
BINARY="${CACHE_DIRECTORY}/tailwindcss-v${TAILWIND_VERSION}-${ASSET}"

if [ ! -x "$BINARY" ] || [ "$(checksum_file "$BINARY")" != "$CHECKSUM" ]; then
    TEMPORARY=$(mktemp "${CACHE_DIRECTORY}/.tailwindcss.XXXXXX")
    trap 'rm -f "$TEMPORARY"' EXIT HUP INT TERM
    curl --fail --location --silent --show-error --retry 3 --connect-timeout 10 \
        "https://github.com/tailwindlabs/tailwindcss/releases/download/v${TAILWIND_VERSION}/${ASSET}" \
        --output "$TEMPORARY"

    if [ "$(checksum_file "$TEMPORARY")" != "$CHECKSUM" ]; then
        echo "Tailwind CLI checksum mismatch for ${ASSET}" >&2
        exit 1
    fi

    chmod 755 "$TEMPORARY"
    mv "$TEMPORARY" "$BINARY"
    trap - EXIT HUP INT TERM
fi

exec "$BINARY" "$@"
