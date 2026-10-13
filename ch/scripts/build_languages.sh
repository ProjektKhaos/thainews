#!/usr/bin/env bash
set -euo pipefail
project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
for language in en th sv; do
  for page in home house gallery experience location book; do
    php "$project_dir/scripts/render_language.php" "$language" "$page"
  done
done
