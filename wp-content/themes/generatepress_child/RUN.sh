#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_DIR="$(env -u LD_LIBRARY_PATH git -C "$SCRIPT_DIR" rev-parse --show-toplevel 2>/dev/null || true)"
if [[ -z "$REPO_DIR" ]]; then
  REPO_DIR="$SCRIPT_DIR"
  NEED_BOOTSTRAP=1
else
  NEED_BOOTSTRAP=0
fi
REMOTE="origin"
BRANCH="main"

# Local's bundled libraries can break git-remote-https. Always run git with a clean loader path.
git_safe() {
  env -u LD_LIBRARY_PATH git -C "$REPO_DIR" "$@"
}

# Convenience alias for any subshell spawned from this script.
alias git-clean='env -u LD_LIBRARY_PATH git -C "$REPO_DIR"'

pause() {
  echo
  read -rp "Press Enter to continue..." _
}

bootstrap_repo() {
  echo "No git repository detected in:"
  echo "  $SCRIPT_DIR"
  echo
  echo "Safe bootstrap mode:"
  echo "  1) Create local git repo from CURRENT files"
  echo "  2) Create an initial local snapshot commit"
  echo "  3) Optional remote link (no pull/reset/overwrite)"
  echo

  read -rp "Create protected local repo now? (Y/n): " init_confirm
  if [[ "${init_confirm:-Y}" =~ ^[Nn]$ ]]; then
    echo "Bootstrap cancelled."
    return 1
  fi

  git_safe init
  git_safe add .

  if ! git_safe diff --cached --quiet; then
    init_message="Initial local snapshot $(date +'%Y-%m-%d %H:%M:%S')"
    echo "Creating local snapshot commit: $init_message"
    git_safe commit -m "$init_message"
  else
    echo "No files changed; repository initialized without snapshot commit."
  fi

  git_safe branch -M "$BRANCH"

  echo
  echo "Optional: set or update remote URL now."
  echo "This does NOT pull from server and does NOT overwrite local files."
  read -rp "Remote URL (leave blank to skip): " remote_url
  if [[ -n "${remote_url}" ]]; then
    if git_safe remote get-url "$REMOTE" >/dev/null 2>&1; then
      git_safe remote set-url "$REMOTE" "$remote_url"
    else
      git_safe remote add "$REMOTE" "$remote_url"
    fi
  fi

  echo
  echo "Bootstrap completed safely."
  echo "Local files were preserved."
}

if [ "$NEED_BOOTSTRAP" -eq 1 ]; then
  bootstrap_repo || exit 1
fi

show_header() {
  clear
  echo "========================================"
  echo " SMPT Git Sync"
  echo "========================================"
  echo "Repo   : $REPO_DIR"
  echo "Remote : $REMOTE/$BRANCH"
  echo
}

run_pull() {
  echo "This will replace local files with $REMOTE/$BRANCH."
  echo "Any local changes will be stashed first."
  echo
  git_safe status --short
  echo

  read -rp "Continue with pull-and-replace? (y/N): " pull_confirm
  if [[ ! "$pull_confirm" =~ ^[Yy]$ ]]; then
    echo "Pull cancelled."
    return
  fi

  read -rp "Type REPLACE to confirm overwrite: " pull_word
  if [[ "$pull_word" != "REPLACE" ]]; then
    echo "Pull cancelled."
    return
  fi

  if ! git_safe diff --quiet || ! git_safe diff --cached --quiet || [ -n "$(git_safe ls-files --others --exclude-standard)" ]; then
    stash_message="Auto stash before pull $(date +'%Y-%m-%d %H:%M:%S')"
    echo "Stashing local changes: $stash_message"
    git_safe stash push --include-untracked -m "$stash_message"
  fi

  echo "Fetching latest changes..."
  git_safe fetch "$REMOTE" "$BRANCH"

  echo "Replacing local branch with $REMOTE/$BRANCH ..."
  git_safe reset --hard "$REMOTE/$BRANCH"
  git_safe clean -fd

  echo
  echo "Local branch now matches $REMOTE/$BRANCH."
}

run_push() {
  echo "Current status:"
  git_safe status --short
  echo

  git_safe add .

  if git_safe diff --cached --quiet; then
    echo "Nothing staged. Nothing to push."
    return
  fi

  commit_message="Auto sync $(date +'%Y-%m-%d %H:%M:%S')"
  echo "Using commit message: $commit_message"

  git_safe commit -m "$commit_message"

  echo
  echo "Pushing to $REMOTE/$BRANCH ..."
  git_safe push -u "$REMOTE" "$BRANCH"
  echo "Push completed."
}

while true; do
  show_header
  echo "[1] Pull latest from GitHub"
  echo "[2] Commit and push local changes"
  echo "[0] Exit"
  echo
  read -rp "Select an option: " choice

  case "$choice" in
    1)
      echo
      run_pull
      pause
      ;;
    2)
      echo
      run_push
      pause
      ;;
    0)
      echo
      echo "Goodbye."
      exit 0
      ;;
    *)
      echo
      echo "Invalid option."
      pause
      ;;
  esac
done
