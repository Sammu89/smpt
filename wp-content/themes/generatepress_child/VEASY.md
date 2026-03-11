# VEASY.md

## Purpose

This file is the technical runbook for automating Visiodent / Veasy.

It is not a history log.
It should describe:

- where to go
- which page model or UI surface to use
- which checks must pass before acting
- how to perform each supported action
- how to verify success
- what to do when an action is new or unsupported

If a workflow is discovered once and validated, write the reusable method here.
Do not record one-off business history unless it reveals a reusable technical rule.

## Scope

Target pages:

- `https://patient.visiodent.com/patient/parametrage/actes-v2/mode-assistant`
- `https://patient.visiodent.com/patient/parametrage/acte-ngap`
- fallback only if needed:
  - `https://patient.visiodent.com/patient/parametrage/actes/mode-assistant`

This automation is browser automation against the live Veasy application.

Primary tools:

- Playwright attached to a live Chrome session
- Chrome DevTools MCP for diagnosis, DOM inspection, Knockout inspection, and live recovery

Current local script entry point:

- `C:\Users\Sammu\Local Sites\smpt\app\public\tests-playwright\scripts\veasy-assistant.mjs`

Current npm wrapper:

- `npm run veasy:assistant -- --action check-session`

## Required Tooling

The working toolchain for this runbook is:

- Google Chrome with remote debugging enabled on `9222`
- Node.js
- Playwright installed in:
  - `C:\Users\Sammu\Local Sites\smpt\app\public\tests-playwright`
- Codex CLI
- Chrome DevTools MCP connected to the same Chrome session

Use each tool for the right job:

- Playwright:
  - repeatable execution
  - batch operations
  - scripted verification
- Chrome DevTools MCP:
  - discover page model
  - inspect bindings
  - debug selectors, modals, reload timing, and live data
  - validate a new primitive before adding it to Playwright

Operational rule:

- if a workflow is already validated, prefer Playwright
- if a workflow is unclear or broken, inspect with DevTools MCP first, then update Playwright and this file

## Core Rule

For every request:

1. identify the action type
2. go to the right page
3. verify the right specialty and selected item
4. use the most reliable technical primitive already validated
5. verify persistence
6. only if the action is new:
   - explore
   - validate
   - then document the reusable method here

## Preconditions

Before any automated run:

1. launch Chrome with remote debugging on port `9222`
2. open `https://patient.visiodent.com/patient`
3. log in manually if needed
4. ensure structure is `DENTEGO`
5. only then attach Playwright or use DevTools MCP

Important:

- credentials are never automated here
- a live authenticated browser session is assumed
- if Veasy is on `/Account/Login`, hard stop

## Launch And Attach

Launch Chrome with remote debugging:

- `@'`
- `const { spawn } = require('child_process');`
- `const chromePath = 'C:/Program Files/Google/Chrome/Application/chrome.exe';`
- `const args = [`
- `  '--remote-debugging-port=9222',`
- `  '--user-data-dir=C:/Users/Sammu/AppData/Local/Temp/smpt-chrome-mcp',`
- `  '--ignore-certificate-errors',`
- `  '--new-window',`
- `  'https://patient.visiodent.com/patient'`
- `];`
- `const child = spawn(chromePath, args, { detached: true, stdio: 'ignore', windowsHide: false });`
- `child.unref();`
- `console.log(child.pid);`
- `'@ | node -`

Verify the debugging endpoint:

- `Invoke-WebRequest -UseBasicParsing http://127.0.0.1:9222/json/version`

Run the assistant from `C:\Users\Sammu\Local Sites\smpt\app\public\tests-playwright`:

- `npm run veasy:assistant -- --action check-session`
- `npm run veasy:assistant -- --action rename-category --specialty "..." --old-label "..." --new-label "..."`
- `npm run veasy:assistant -- --action create-category --specialty "..." --parent-category "..." --label "..." --code-suffix "..."`
- `npm run veasy:assistant -- --action delete-ngap --specialty "..." --label "..."`
- `npm run veasy:assistant -- --action inspect-ngap --specialty "..." --label "..."`
- `npm run veasy:assistant -- --action update-ngap --specialty "..." --label "..." --patch '{"Field":"Value"}'`
- `npm run veasy:assistant -- --action copy-ngap --specialty "..." --source-label "..." --target-label "..." --patch '{"IdCategoryNgap":123,"Code":"TO45"}'`
- `npm run veasy:assistant -- --action list-children --specialty "..." --parent-label "..."`
- `npm run veasy:assistant -- --action find-ngap-under-parent --specialty "..." --parent-label "..." --contains "..."`
- `npm run veasy:assistant -- --action replace-ngap-under-parent --specialty "..." --parent-label "..." --contains "..." --replace "..."`
- `npm run veasy:assistant -- --action find-ngap-by-ancestors --specialty "..." --root-contains "..." --branch-contains "..." --contains "..."`
- `npm run veasy:assistant -- --action replace-ngap-by-ancestors --specialty "..." --root-contains "..." --branch-contains "..." --contains "..." --replace "..."`
- `npm run veasy:assistant -- --action plan-reclassify-subcategories --specialty "..." --source-parent "..." --target-parent "..." --prefix "..." --suffix "..."`
- `npm run veasy:assistant -- --action reclassify-subcategories --specialty "..." --source-parent "..." --target-parent "..." --prefix "..." --suffix "..."`
- `npm run veasy:assistant -- --action plan-reclassify-subcategories --specialty "..." --source-parent "..." --target-parent "..." --contains-any "Agile|Confort|Mini" --prefix "..." --suffix "..."`
- `npm run veasy:assistant -- --action plan-reclassify-subcategories --specialty "..." --source-parent "..." --target-parent "..." --labels-json '["Exact label 1","Exact label 2"]' --prefix "..." --suffix "..."`
- `npm run veasy:assistant -- --action plan-reclassify-subcategories --specialty "..." --source-parent-contains "Traitement par aligneurs" --target-parent-contains "Traitement multi-attaches" --source-parent-confirm "Traitement par Aligneurs (CORRECT mais pas encore visible)"`
- `npm run veasy:assistant -- --action plan-reclassify-subcategories --specialty "..." --source-child-contains "SMILLERS EXPERTS AGILE / CONFORT / MINI" --target-parent "Traitement multi-attaches" --prefix "Aligneurs - " --suffix " (ancien)"`
- `npm run veasy:assistant -- --action plan-reclassify-subcategories --specialty "..." --source-parent-contains "Traitement par Aligneurs" --target-child-contains "Trimestre — Métal simple"`

## Page Selection Rules

Use `actes-v2/mode-assistant` for:

- category selection in the tree
- category rename
- category creation
- final NGAP act inspection in the right panel
- final NGAP act edit
- final NGAP act delete

Use `acte-ngap` when:

- a task is easier in the dedicated NGAP page
- table search is preferable
- the assistant page does not expose the required surface cleanly

Use `actes/mode-assistant` only as fallback when:

- the requested workflow is not available in `actes-v2`
- or the page model changed and the old page still works

## Technical Model

### Root view model

On `actes-v2/mode-assistant`, resolve the assistant root with:

```js
const root = ko.contextFor(document.getElementById('idDivAssistantMode')).$root;
```

Validated sub-models:

- `root.actCategories`
- `root.ngap`
- `root.act`

### Category tree source

The most reliable tree source is:

```js
root.actCategories.data()
```

This returns the tree used in the UI, with:

- `id`
- `label`
- `childCategories`
- other display flags

Do not rely only on visible text scraping if the tree data is available.

### Validated category primitives

- select category or act leaf:
  - `root.actCategories.select(node)`
- save category rename:
  - `root.actCategories.save(model)`
- create category:
  - `root.createActCategoryParent(viewModel)`

### Validated NGAP primitives

- open edit model from selected act:
  - `root.ngap.edit(root.ngap.selected())`
- save NGAP act:
  - `root.ngap.save(model)`
- delete NGAP act:
  - `root.ngap.remove(root.ngap.selected())`

## Common Verification Rules

Before mutating anything:

1. verify the correct `Spécialité`
2. verify the current structure is still `DENTEGO`
3. verify the selected tree label exactly matches the intended target
4. verify the right panel matches the selected act or category

After every save or delete:

1. wait for modal close or assistant reload
2. re-read the tree model
3. re-open or re-select the target when appropriate
4. verify the intended change persisted

Never report success from a click alone.

## Hard Stops

Stop and report if any of these happen:

- wrong `Spécialité`
- wrong structure
- target item not found
- right panel label does not match the intended target
- expected modal does not open
- expected field is missing
- a destructive action targets an ambiguous label
- the page shows `Runtime Error`
- the page redirects to login
- the save request does not lead to visible persistence
- deletion is requested but `EnableDelete = false`

Do not continue by assumption after a hard stop.

## Runtime Error Recovery

If `actes-v2` shows:

- `Server Error in '/patient' Application.`
- `Runtime Error`

Then:

1. go back to `https://patient.visiodent.com/patient`
2. verify structure is `DENTEGO`
3. reopen `Actes` from the app navigation
4. continue only after the page loads normally

## Mappings

### `Durée période (ODF)` mapping

Validated mapping from the select bound to `idPeriodeOdf`:

- `0` -> `-`
- `1` -> `2 mois`
- `2` -> `3 mois`
- `3` -> `4 mois`
- `4` -> `6 mois`
- `5` -> `12 mois`

Operational rules:

- never infer `idPeriodeOdf` from memory
- use the explicit mapping
- when copying an act, preserve the source `idPeriodeOdf` unless the request explicitly changes it

### Child CSS transposition

Validated standard pattern:

- `TranspositionCodeCMUcChild = FDO`
- `CoefficientTranspositionCodeCMUcChild = 29`

## Supported Actions

### 1. Category rename

Use when the selected tree item is a category node.

Method:

1. select the category in the tree
2. verify the right panel matches the category
3. open the category edit surface
4. change only `Libellé`
5. save through the category save action
6. reload or re-read tree data
7. verify the new label exists

Automation preference:

- use the category model and save function when available
- avoid brittle text-only DOM editing if the Knockout model is accessible

### 2. Category creation

Method:

1. open `CRÉATION`
2. choose `CATÉGORIE`
3. set parent category
4. set `Libellé`
5. set `Code`
6. save
7. verify the new category appears in the tree

Validated technical primitive:

- `root.createActCategoryParent(viewModel)`

Required fields:

- `Label`
- `Code`
- `ParentId`

### 3. NGAP act search

On `actes-v2`:

1. expand the relevant branch in the tree
2. prefer tree data from `root.actCategories.data()`
3. match by exact label when performing destructive operations

On `acte-ngap`:

1. use table search
2. confirm the row label before any edit

### 4. NGAP act creation

Preferred method: create by copying a validated source act model.

Why:

- Veasy forms are large
- copied acts preserve hidden or easy-to-forget fields
- only the requested deltas need to be changed

Method:

1. find a source act
2. select it
3. open the edit model with:
   - `root.ngap.edit(root.ngap.selected())`
4. clone the edit model in JavaScript
5. override at minimum:
   - `Id = 0`
   - `IdCategoryNgap`
   - `Label`
6. override any business fields requested by the user
7. save with:
   - `root.ngap.save(model)`
8. verify the new act appears in the target branch

Default copy rule unless the user says otherwise:

- keep the same `Code` as the source act

### 4.b. NGAP import from a structured table

Use this when the source is not a single act but a structured table that describes:

- parent category
- sub-categories
- act labels
- coefficients
- fees
- repeated tariff rules

Typical source formats:

- HTML exported from Excel
- table-like HTML
- spreadsheet exported to a machine-readable intermediate file

Recommended method:

1. parse the source table outside the browser
2. convert it into a normalized JSON payload
3. import through Playwright batch automation

Normalized intermediate structure:

```json
{
  "parentLabel": "Parent Category",
  "sourceLabel": "Existing Source Act",
  "categoryCodePrefix": "PREFIX",
  "categories": [
    {
      "label": "Sub-category Label",
      "acts": [
        {
          "label": "Act Label",
          "patch": {
            "Code": "TO",
            "IdCategoryNgap": 123,
            "CoefficientAdult": 90
          }
        }
      ]
    }
  ]
}
```

Batch strategy:

1. ensure the parent category exists
2. for each sub-category:
   - create it if missing
   - reuse it if already present
3. for each act:
   - if the exact label already exists, update it
   - otherwise create it by copying a validated source act
4. verify branch contents after each chunk

Recommended Playwright command:

```powershell
npm run veasy:assistant -- --action batch-import-json --specialty "..." --json-file import.json
```

Important generic rules:

- build the JSON first, do not parse a complex source table live inside Veasy
- keep repeated tariff logic in the generated `patch` object
- when a field depends on a coefficient or another source value, compute it during JSON generation
- if the batch is large, run a pilot on one sub-category first
- after the pilot is validated, run the remaining categories as a resume batch
- if a category was accidentally created twice during a pilot, remove only the empty duplicate
- if the source table has decorative totals or package-level prices, ignore them unless the workflow explicitly uses them

Recommended Playwright command:

```powershell
npm run veasy:assistant -- --action copy-ngap --specialty "Chirurgien dentiste" --source-label "[Métal] Trimestre 1 - Simple" --target-label "[Métal] Trimestre 21 - Simple" --patch '{"IdCategoryNgap":12558,"Code":"TO45","idPeriodeOdf":2}'
```

### 5. NGAP act modification

Method:

1. find the exact target act
2. verify the selected right-panel label
3. open the edit model with:
   - `root.ngap.edit(root.ngap.selected())`
4. change only the requested fields
5. if one tariff column is touched, keep that whole column coherent
6. save with:
   - `root.ngap.save(model)`
7. reopen and verify persistence

Important rule:

- if a tariff column is modified, related fields in that same column may need to stay internally coherent for save to behave correctly

Typical column fields:

- letter key
- coefficient
- fee amount
- exoneration
- overtaking reason
- transposition fields for child CSS

Recommended Playwright command:

```powershell
npm run veasy:assistant -- --action update-ngap --specialty "Chirurgien dentiste" --label "[Métal] Trimestre 2 - Simple" --patch '{"RegroupingCode":"ORTHODONTIE","AccountingCode":"ORTHODONTIE","idPeriodeOdf":2}'
```

### 6. NGAP act deletion

Use this exact workflow on `actes-v2/mode-assistant`.

UI button:

```html
<button class="vdBtn red" data-role="hint" data-hint="Supprimer" data-bind="click: $root.ngap.remove, visible: currentParam.enableModification()">
    <i class="fa fa-trash"></i>
</button>
```

Validated technical behavior:

- handler:
  - `root.ngap.remove(act)`
- request payload uses:
  - `actNGAPId = act.Id`
  - current `speciality`
- success triggers assistant reload

Method:

1. find the exact act by label
2. select it in the tree
3. verify the right panel label matches exactly
4. verify `EnableDelete = true`
5. call the UI button or `root.ngap.remove(selected)`
6. wait for assistant reload
7. verify the label is gone from the same branch

Important constraint:

- if `EnableDelete = false`, the act is not removable by this workflow
- do not force `root.ngap.remove`
- report it as unsupported on this page

Recommended Playwright command:

```powershell
npm run veasy:assistant -- --action delete-ngap --specialty "Chirurgien dentiste" --label "[Métal] Trimestre 2 - simple"
```

## Scriptable Patterns

### Flatten the tree

Use this whenever a task needs exact lookup by label:

```js
const flat = [];
const walk = (nodes, parentId = null) => (nodes || []).forEach(node => {
  flat.push({
    id: node.id,
    label: node.label,
    parentId,
    node,
  });
  walk(node.childCategories || [], node.id);
});
walk(root.actCategories.data());
```

### Select exact label

```js
const target = flat.find(item => item.label === targetLabel);
if (!target) throw new Error(`Not found: ${targetLabel}`);

root.actCategories.select(target.node);
```

### Safe pre-mutation check

```js
const selected = root.ngap.selected && root.ngap.selected();
if (!selected || selected.Label !== targetLabel) {
  throw new Error(`Selected label mismatch: ${selected ? selected.Label : 'none'}`);
}
```

### Clone edit model for creation

```js
const cloneModel = src => {
  const out = {};
  for (const [k, v] of Object.entries(src)) {
    if (typeof v === 'function') out[k] = ko.observable(ko.unwrap(v));
    else if (Array.isArray(v)) out[k] = JSON.parse(JSON.stringify(v));
    else if (v && typeof v === 'object') out[k] = JSON.parse(JSON.stringify(v));
    else out[k] = v;
  }
  return out;
};
```

### Read an act before editing

Use this when the request first needs a reliable snapshot of the source act:

```powershell
npm run veasy:assistant -- --action inspect-ngap --specialty "Chirurgien dentiste" --label "[Métal] Trimestre 2 - Simple"
```

### List exact children of a category

Use this before batch normalization or cleanup:

```powershell
npm run veasy:assistant -- --action list-children --specialty "Chirurgien dentiste" --parent-label "Trimestre — Métal simple"
```

## Testing Strategy

When a workflow already exists in this file:

1. do not rediscover it
2. execute it
3. verify persistence

When a workflow is partially known:

1. inspect the page model
2. identify the most reliable primitive
3. validate on one controlled example
4. then batch
5. then update this file

When a workflow is new:

1. ask only if a business rule is missing
2. otherwise explore in the browser
3. find the stable method
4. write the reusable procedure here

## Decision Rules For Future Requests

If the request is:

- rename a category:
  - use the category rename procedure
- create a category:
  - use the category creation procedure
- create acts from existing ones:
  - use copy-based NGAP creation
- edit prices, durations, grouping codes, accounting codes, motives, or transpositions:
  - use NGAP act modification
- delete an act:
  - use NGAP act deletion
- move an item and the page exposes a family/category move surface:
  - use that surface
- move many sub-categories from one parent branch to another:
  - use sub-category reclassification with preview first
- unsupported or hidden:
  - inspect once, validate, then document

## Recommended User Input Format

The AI works fastest if the request includes:

- `Spécialité`
- action type:
  - rename
  - create
  - edit
  - delete
  - copy
  - move
- exact source label
- exact target label
- target category if relevant
- field rules if relevant:
  - code
  - duration
  - grouping code
  - accounting code
  - fees
  - motives
  - transposition

If a rule is missing and cannot be safely inferred, ask once, then proceed.

## Maintenance Rule

Keep this file technical.

Good additions:

- a stable DOM or Knockout entry point
- a verified mutation primitive
- a field mapping
- a hard-stop condition
- a repeatable verification method

Bad additions:

- narrative history of what was changed for one specialty
- lists of every one-off act already processed
- operation logs that do not teach a reusable method

## Optimization Rules

The goal is not just to make actions possible.
The goal is to make repeated actions fast, safe, and predictable.

### 1. Prefer the fastest reliable tool

Use Playwright when:

- the workflow is already known
- the action is repetitive
- the action can be expressed as:
  - inspect
  - list
  - copy
  - update
  - delete
  - rename
  - create

Use DevTools MCP when:

- the UI changed
- a binding or field is unclear
- a save behaves unexpectedly
- an item is visible but does not behave like a normal NGAP act
- a destructive action must be diagnosed before retrying

Operational rule:

- discover with MCP once
- automate with Playwright after validation

### 2. Prefer model-level automation over DOM automation

If a Knockout primitive exists, prefer it over clicking and filling DOM fields.

Prefer:

- `root.actCategories.data()`
- `root.actCategories.select(...)`
- `root.actCategories.save(...)`
- `root.createActCategoryParent(...)`
- `root.ngap.edit(...)`
- `root.ngap.save(...)`
- `root.ngap.remove(...)`

Avoid relying only on:

- brittle text selectors
- modal layout assumptions
- visual order in the tree

Reason:

- model-level automation is faster
- it survives small UI changes better
- it reduces flakiness from scrolling, visibility, and delayed rendering

### 3. Always read before write

Before editing or deleting:

1. inspect the exact act or category
2. verify the selected label
3. verify the expected flags and fields

Useful commands:

```powershell
npm run veasy:assistant -- --action inspect-ngap --specialty "..." --label "..."
```

```powershell
npm run veasy:assistant -- --action list-children --specialty "..." --parent-label "..."
```

This prevents:

- editing the wrong item
- creating duplicates unnecessarily
- attempting deletion on `EnableDelete = false`

### 4. For creation, copy instead of building from scratch

The fastest safe way to create a new NGAP act is:

1. choose a valid source act close to the target
2. copy it
3. override only the required deltas

This is better than filling every field manually because:

- hidden defaults are preserved
- specialty-specific fields are less likely to be missed
- batch creation is much faster

### 5. For edits, patch only the needed fields

Do not rewrite the whole act unless required.

Prefer:

- targeted `--patch` updates

Example:

```powershell
npm run veasy:assistant -- --action update-ngap --specialty "..." --label "..." --patch '{"RegroupingCode":"ORTHODONTIE"}'
```

But when changing a tariff column, make the whole column coherent in the patch:

- key letter
- coefficient
- fee
- exoneration
- overtaking reason
- child CSS transposition if relevant

### 6. List a branch before batch work

Before normalizing a branch:

1. list its children
2. compare expected labels vs actual labels
3. reuse malformed-but-correctable acts before creating new ones

This reduces:

- duplicates
- cleanup work
- accidental over-creation

### 7. Batch in small chunks

For heavy operations, do not run very large live batches blindly.

Prefer:

- one branch at a time
- or one chunk of 5 to 10 items at a time

Reason:

- Veasy may reload silently
- CDP execution contexts may reset
- smaller chunks make recovery easier

### 8. Re-verify after every destructive or structural action

After:

- delete
- create
- rename
- bulk update

always re-read the tree or inspect the created/updated act.

Do not assume that a successful click means persistence.

### 9. Keep reusable business rules in command input, not in memory

When a task has many repeated rules, express them clearly in:

- the user request
- the patch payload
- the copy payload

Do not depend on hidden memory of a prior run.

Good pattern:

- inspect source
- define rules
- apply patch or copy with explicit overrides

### 10. If a workflow becomes common, promote it

When the same sequence is used repeatedly:

- do not keep executing it manually through MCP
- add or improve a Playwright action
- document the command in this file

Examples of good candidates:

- batch normalization from a source template
- category branch inventory
- duplicate detection
- selective delete by exact label

### 11. Use ancestor filters for mass renames

When the user request is not tied to one exact sub-category label but to a path rule, use the ancestor-based search actions.

Typical case:

- main category contains a token such as `aligneurs`
- descendant sub-category contains a token such as `Comprehensive`
- final act label contains a token such as `2A/`
- the action is to replace or remove that token in every matching descendant act

Use a preview first:

```powershell
npm run veasy:assistant -- --action find-ngap-by-ancestors --specialty "..." --root-contains "aligneurs" --branch-contains "Comprehensive" --contains "2A/"
```

Then apply the replacement:

```powershell
npm run veasy:assistant -- --action replace-ngap-by-ancestors --specialty "..." --root-contains "aligneurs" --branch-contains "Comprehensive" --contains "2A/" --replace ""
```

Operational rules:

- matching is case-insensitive
- `root-contains` matches any ancestor in the path, typically the main category branch
- `branch-contains` matches the nearer descendant branch, typically the target sub-category family
- `contains` matches only the final act label
- always run `find-*` before `replace-*`
- use exact replacement strings, not fuzzy text editing

### 12. Treat unsupported items explicitly

If an item is visible but does not support the normal workflow:

- inspect its flags
- document the constraint
- stop retrying the same unsupported path

Example:

- if `EnableDelete = false`, the item is not removable with standard assistant-mode deletion

### 13. Optimize for recovery, not only speed

A fast workflow is only useful if it is easy to recover from failure.

Prefer commands and scripts that:

- identify exact labels
- return structured output
- stop on mismatch

## Reclassify Sub-Categories

Use this when the task is:

- move direct child categories from one main branch to another
- optionally rename each moved sub-category with a common prefix or suffix

This is for category branches, not final NGAP acts.

Validated method:

1. preview the direct child categories under the source parent
2. compute the optional new label for each item
3. open the category modal with the category edit button:
   - `<button onclick="$('#edit-category').modal('show');" class="vdBtn blue" data-hint="Editer">`
4. set both fields in the same modal when needed:
   - `Libellé`
   - `Famille`
5. verify that the category now has the target parent

Preview first:

```powershell
npm run veasy:assistant -- --action plan-reclassify-subcategories --specialty "..." --source-parent "Traitement par Aligneurs (CORRECT mais pas encore visible)" --target-parent "Traitement multi-attaches" --prefix "Aligneurs - " --suffix " (ancien)"
```

Or resolve parents by approximate label:

```powershell
npm run veasy:assistant -- --action plan-reclassify-subcategories --specialty "..." --source-parent-contains "Traitement par aligneurs" --target-parent-contains "Traitement multi-attaches" --prefix "Aligneurs - " --suffix " (ancien)"
```

Or infer the source main category from one of its child sub-categories:

```powershell
npm run veasy:assistant -- --action plan-reclassify-subcategories --specialty "..." --source-child-contains "SMILLERS EXPERTS AGILE / CONFORT / MINI" --target-parent "Traitement multi-attaches" --prefix "Aligneurs - " --suffix " (ancien)"
```

Or infer the target main category from one of its child sub-categories:

```powershell
npm run veasy:assistant -- --action plan-reclassify-subcategories --specialty "..." --source-parent-contains "Traitement par Aligneurs" --target-child-contains "Trimestre — Métal simple"
```

Then execute:

```powershell
npm run veasy:assistant -- --action reclassify-subcategories --specialty "..." --source-parent "Traitement par Aligneurs (CORRECT mais pas encore visible)" --target-parent "Traitement multi-attaches" --prefix "Aligneurs - " --suffix " (ancien)"
```

Operational rules:

- only direct child categories of the source parent are targeted
- final NGAP acts are not moved by this action
- for category reclassification, prefer doing rename and move in the same modal submit
- `--prefix` and `--suffix` are optional
- always run the plan action first
- verify collisions manually if the target branch may already contain similar labels

Edge-case rules:

- do not assume `source-parent` means all child categories must move
- if the request targets only a family such as `Smilers Expert Agile / Confort / Mini`, narrow the plan first
- for broad but pattern-based selection, use:
  - `--contains "Smilers Expert"`
  - or `--contains-any "Agile|Confort|Mini"`
- for high-risk or business-critical moves, prefer exact targeting with:
  - `--labels-json '["Exact label 1","Exact label 2"]'`
- if the preview includes unexpected labels, stop and tighten the filters before execution
- if `--source-parent-contains` or `--target-parent-contains` returns multiple close matches, the script stops and prints candidates
- in that case, rerun with:
  - the exact parent label using `--source-parent` or `--target-parent`
  - or explicit confirmation using `--source-parent-confirm` or `--target-parent-confirm`
- if the main category is not known but one representative sub-category is known, use `--source-child-contains`
- this resolves the source parent from the matching child sub-category, then targets all direct child categories under that resolved parent
- the same rule works for the target side with `--target-child-contains`
- if one child keyword matches categories under multiple parents, the script stops and asks for explicit confirmation with `--source-child-confirm` or `--target-child-confirm`
- save debug artifacts on failure

That makes the next correction much faster than a fragile one-shot script.
