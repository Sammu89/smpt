#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_DIR="$(git -C "$SCRIPT_DIR" rev-parse --show-toplevel 2>/dev/null || true)"
REMOTE="origin"
BRANCH="main"

if [ -z "$REPO_DIR" ]; then
  echo "ERROR: Could not locate the git repository root from: $SCRIPT_DIR"
  exit 1
fi

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
