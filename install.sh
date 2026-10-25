#!/usr/bin/env bash
#
# Mothball installer
#
# Downloads the latest release into the project's plugins folder.
#
# Run it from the root of a WordPress or Bedrock project:
#
#   curl -sSL https://raw.githubusercontent.com/jonbp/mothball/main/install.sh | bash
#

set -euo pipefail

REPO="jonbp/mothball"
PLUGIN_SLUG="mothball"

PROJECT_PATH="$PWD"
CONTENT_DIR=""
VERSION="latest"
FORCE=0

task()    { printf '\n\033[34m%s: %s\n\033[0m' "${2:-Task}" "$1"; }
success() { printf '\n\033[32mSuccess: %s\n\033[0m' "$1"; }
warning() { printf '\n\033[33mWarning: %s\n\033[0m' "$1"; }
error()   { printf '\n\033[31mError: %s\n\033[0m' "$1" >&2; }
die()     { error "$1"; exit 1; }

usage() {
  cat <<'USAGE'
Mothball installer

Installs the plugin into the project's plugins folder, or updates an install
that's already there.

Usage:
  install.sh [options]

Options:
  --path=<path>         WordPress project root. Defaults to the current directory.
  --content-dir=<path>  Content directory, if it isn't <project>/wp-content, or
                        <project>/web/app on Bedrock. Relative paths are
                        resolved against the project root.
  --version=<tag>       Release to install, such as 1.0.0. Defaults to the latest.
  --force               Replace the target directory even when it doesn't look
                        like an existing Mothball install, or is a git checkout.
  -h, --help            Show this message.
USAGE
}

while [ $# -gt 0 ]; do
  case "$1" in
    --path=*)        PROJECT_PATH="${1#*=}" ;;
    --content-dir=*) CONTENT_DIR="${1#*=}" ;;
    --version=*)     VERSION="${1#*=}" ;;
    --force)         FORCE=1 ;;
    -h|--help)       usage; exit 0 ;;
    *)               error "Unknown option: $1"; usage >&2; exit 1 ;;
  esac
  shift
done

for dependency in curl tar; do
  command -v "$dependency" >/dev/null 2>&1 \
    || die "$dependency is required but wasn't found in PATH."
done

# Locate the project
[ -d "$PROJECT_PATH" ] || die "No such directory: $PROJECT_PATH"
PROJECT_PATH="$(cd "$PROJECT_PATH" && pwd)"

BEDROCK=0
if [ -f "$PROJECT_PATH/config/application.php" ] && [ -d "$PROJECT_PATH/web/app" ]; then
  BEDROCK=1
elif [ ! -f "$PROJECT_PATH/wp-load.php" ] && [ ! -f "$PROJECT_PATH/wp-settings.php" ]; then
  die "$PROJECT_PATH doesn't look like a WordPress or Bedrock root. Run this from the project root, or pass --path."
fi

# Locate the content directory
if [ -z "$CONTENT_DIR" ]; then
  if [ "$BEDROCK" -eq 1 ]; then
    CONTENT_DIR="$PROJECT_PATH/web/app"
  else
    CONTENT_DIR="$PROJECT_PATH/wp-content"

    if [ -f "$PROJECT_PATH/wp-config.php" ] \
      && grep -qE "define\([[:space:]]*['\"](WP_CONTENT_DIR|WP_PLUGIN_DIR)['\"]" "$PROJECT_PATH/wp-config.php"; then
      warning "wp-config.php sets a custom content directory. Pass --content-dir if $CONTENT_DIR isn't the right one."
    fi
  fi
else
  case "$CONTENT_DIR" in
    /*) ;;
    *) CONTENT_DIR="$PROJECT_PATH/$CONTENT_DIR" ;;
  esac
fi

[ -d "$CONTENT_DIR" ] || die "No content directory at $CONTENT_DIR."

PLUGINS_DIR="$CONTENT_DIR/plugins"
TARGET_DIR="$PLUGINS_DIR/$PLUGIN_SLUG"

# Resolve the version to install
if [ "$VERSION" = "latest" ]; then
  task "Looking up the latest release" "Version"

  VERSION="$(curl -fsSL "https://api.github.com/repos/$REPO/releases/latest" \
    | sed -n 's/.*"tag_name"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' \
    | head -n1)"

  [ -n "$VERSION" ] \
    || die "Couldn't work out the latest release. Pass --version=<tag> to install a specific one."
fi

# Don't clobber anything that isn't ours, or a working copy
plugin_version() {
  sed -n 's/^[[:space:]*]*Version:[[:space:]]*\([^[:space:]]*\).*/\1/p' "$1" 2>/dev/null | head -n1
}

if [ -e "$TARGET_DIR" ]; then
  if [ -d "$TARGET_DIR/.git" ] && [ "$FORCE" -ne 1 ]; then
    die "$TARGET_DIR is a git checkout. Update it with git pull, or re-run with --force to replace it with the release."
  elif [ -f "$TARGET_DIR/$PLUGIN_SLUG.php" ]; then
    installed_version="$(plugin_version "$TARGET_DIR/$PLUGIN_SLUG.php")"

    if [ -n "$installed_version" ]; then
      task "Replacing version $installed_version" "Update"
    else
      task "Replacing the existing install" "Update"
    fi
  elif [ "$FORCE" -eq 1 ]; then
    warning "$TARGET_DIR isn't a Mothball install, replacing it anyway."
  else
    die "$TARGET_DIR already exists and isn't a Mothball install. Re-run with --force to replace it."
  fi
fi

mkdir -p "$PLUGINS_DIR"

# Stage inside the plugins folder so the swap is a rename on the same
# filesystem. WordPress skips directories starting with a dot.
TMP_DIR="$(mktemp -d)"
STAGING_DIR="$PLUGINS_DIR/.$PLUGIN_SLUG-install-$$"
trap 'rm -rf "$TMP_DIR" "$STAGING_DIR"' EXIT INT TERM

task "Downloading $REPO $VERSION" "Download"
curl -fsSL -o "$TMP_DIR/plugin.tar.gz" "https://codeload.github.com/$REPO/tar.gz/refs/tags/$VERSION" \
  || die "Couldn't download version $VERSION. Check the tag exists at https://github.com/$REPO/releases."

mkdir -p "$STAGING_DIR"
tar -xzf "$TMP_DIR/plugin.tar.gz" -C "$STAGING_DIR" --strip-components=1

[ -f "$STAGING_DIR/$PLUGIN_SLUG.php" ] \
  || die "The downloaded archive doesn't contain $PLUGIN_SLUG.php."

task "Installing into $TARGET_DIR" "Install"
rm -rf "$TARGET_DIR"
mv "$STAGING_DIR" "$TARGET_DIR"

success "Mothball $VERSION installed. Activate it under Plugins, or with 'wp plugin activate $PLUGIN_SLUG'."
