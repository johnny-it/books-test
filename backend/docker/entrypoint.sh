#!/bin/sh
set -eu

if [ "${1:-}" = "apache2-foreground" ]; then
  attempts=0
  until php yii migrate --interactive=0; do
    attempts=$((attempts + 1))
    if [ "$attempts" -ge 20 ]; then
      echo "Database did not become ready after $attempts attempts." >&2
      exit 1
    fi
    sleep 2
  done
fi

exec "$@"
