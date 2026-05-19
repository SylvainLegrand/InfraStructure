# Language Files — Translation Rules

## File Structure

### Required Header
```
# Dolibarr language file - <LANG_CODE> - <MODULE_NAME>
CHARSET                         = UTF-8
```

**Rules:**
- **Line 1**: Comment with exact format: `# Dolibarr language file - <LANG_CODE> - <MODULE_NAME>`
  - `<LANG_CODE>`: ISO 639-1 code (e.g., `fr_FR`, `en_US`, `es_ES`, `it_IT`)
  - `<MODULE_NAME>`: Module name (e.g., `infrastructure`, `infraspackplus`)
- **Line 2**: Charset declaration: `CHARSET = UTF-8` (always UTF-8)
- **Line 3**: Empty line

## Translation Keys and Values

### Key Format
```
KeyName = Translation text here
```

**Rules:**
- No spaces around `=` inside the key/value pair (use exact format above)
- **NEVER use tabs** — alignment must use spaces only (Dolibarr does not recognize tabs in language files)

### Vertical Alignment
```
### Chapter Name ###
InfrastructureShortKey              = Short translation
InfrastructureLongerKeyName         = A longer translation text
InfrastructureVeryLongKeyNameHere   = Another translation
```

**Rules:**
- Align `=` signs **vertically by chapter** using spaces only
- Count characters in the longest key name within a chapter
- Pad shorter keys with spaces to align all `=` at the same column
- Re-align when a new chapter starts

**Example calculation:**
```
InfrastructureOlReducePa            = La gestion des lignes / blocs...
InfrastructureOlReducePaInfo        = Attention, si vous rendez...
```
- Longest key: `InfrastructureOlReducePaInfo` (29 chars)
- Add 4 spaces after key, then `=`
- All other keys padded to match

## Chapter Headers

### Format
```
### Chapter Title ###
```

**Rules:**
- **Start**: `###` (3 hashes) + space
- **End**: space + `###` (3 hashes)
- **Content**: English chapter name (same across all language files, regardless of translation language)
- **Common chapters**:
  - `### Actions and buttons ###`
  - `### Setup page ###`
  - `### Summary ###`
  - `### Extrafields ###`
  - `### Shippable orders ###`
  - `### Shipments and deliveries ###`
  - `### Miscellaneous options ###`
  - `### Errors ###`

## Language Files — Master Reference & Systematic Updates

**Base language for updates**: `fr_FR/infrastructure.lang`

### Mandatory Rule: Synchronize All Languages on Every French Change

**When the French translation is modified, you MUST update all other languages at the same time.** This is not optional — it is a strict requirement to maintain consistency.

### Translation Workflow (Mandatory Steps)

1. **Modify the French file first**
   - Create, modify, or delete keys in `fr_FR/infrastructure.lang`
   - Update text translations
   - Ensure proper alignment and chapter structure

2. **Identify what changed**
   - List new keys (to be translated into all languages)
   - List modified keys (text to adapt in all languages)
   - List deleted keys (to be removed from all language files)
   - Note chapter changes (if any)

3. **Update all other language files immediately**
   - 🇬🇧 `en_US/infrastructure.lang`
   - 🇪🇸 `es_ES/infrastructure.lang`
   - 🇮🇹 `it_IT/infrastructure.lang`
   
   For each file:
   - Add/delete/modify the same keys as in French
   - Translate new keys appropriately (don't just copy French text)
   - Update existing translations to stay coherent with French meaning
   - Maintain the same chapter structure
   - Realign `=` signs for all modified sections

4. **Validate consistency** (before considering the work done)
   - ✓ All files have **identical key names** (same keys, same order)
   - ✓ All files have **identical chapter structure** (same chapters, same English titles)
   - ✓ All files have **same chapter order**
   - ✓ No orphaned keys (key present in one language but missing in another)
   - ✓ All `=` signs aligned vertically per chapter
   - ✓ No tab characters

### Consistency Rules

- **All language files must have**:
  - The **same key names** and **same key order**
  - The **same chapter structure** (chapters in English, identical across files)
  - **No orphaned keys** — if a key exists in French, it must exist in all languages
  - Proper **vertical alignment** of `=` signs (spaces only)

- **Missing translations**:
  - If you cannot translate a new key in a language, use the French version as a fallback (temporarily)
  - Mark with a comment if needed: `# TODO: translate to <LANG>`
  - Do not leave keys untranslated in the final version — translate or use French

### Example: Modifying a Key

**Step 1: Update French**
```
# Before
InfrastructureOldKey = Old text

# After
InfrastructureNewKey = New text
```

**Step 2: Update ALL other languages**
```
# en_US
InfrastructureNewKey = New text (in English)

# es_ES
InfrastructureNewKey = Nuevo texto (en español)

# it_IT
InfrastructureNewKey = Nuovo testo (in italiano)
```

**Step 3: Validate**
- Check that all 4 files now have `InfrastructureNewKey`
- Check that the old key `InfrastructureOldKey` is removed from all 4 files
- Check that all files have the same chapter structure

## Alignment Tools

### Manual Alignment (Spaces Only)

Count characters in the longest key within a chapter:

```
# Key: "InfrastructureOlReducePaInfo" = 29 characters
# Format: KEY + SPACES + =
InfrastructureOlReducePa            = [Value here]
InfrastructureOlReducePaInfo        = [Value here]
```

### Algorithm
```
longest_key_length = 29
required_padding_after_key = longest_key_length - current_key_length + 1
format = KEY + (' ' * required_padding_after_key) + '= VALUE'
```

## Validation Checklist (Before Committing)

### Per-File Validation
- ✓ File starts with `# Dolibarr language file - <LANG_CODE> - <MODULE_NAME>`
- ✓ Second line is `CHARSET = UTF-8`
- ✓ All `=` signs aligned vertically within each chapter (spaces only, no tabs)
- ✓ Chapter headers follow format: `### English Title ###`
- ✓ No tab characters anywhere in the file
- ✓ Encoding is UTF-8 (no BOM)

### Cross-Language Validation (Critical)
- ✓ **All language files have IDENTICAL key names** (no orphaned keys)
- ✓ **All language files have IDENTICAL chapter structure** (same chapters, same titles)
- ✓ **All language files have SAME chapter order**
- ✓ **French file (`fr_FR`) updated first** — then all others updated simultaneously
- ✓ **All 4 files modified when French changes** (fr_FR, en_US, es_ES, it_IT)

### When French Is Modified
- ✓ Changes applied to `fr_FR/infrastructure.lang` first
- ✓ Changes immediately propagated to ALL other language files
- ✓ No orphaned keys introduced (if a key is added/deleted in French, apply to all languages)
- ✓ Realignment done across all affected sections in all files

## Examples

### Correct Alignment

```
# Dolibarr language file - fr_FR - infrastructure
CHARSET                         = UTF-8

### Setup page ###
InfrastructureOlReducePa        = La gestion des lignes / blocs optionnel(le)s vide aussi le prix de revient
InfrastructureOlReducePaInfo    = Attention, si vous rendez de nouveau la ligne comprise, il faudra de nouveau saisir le prix de revient

### Errors ###
InfrastructureErrorDeleteLine   = Erreur lors de la suppression d'une ligne du module InfraStructure
```

### Incorrect (Will Fail)

```
# WRONG: Missing lang code
# Dolibarr language file - infrastructure
CHARSET=UTF-8

### Setup page ###
InfrastructureOlReducePa = La gestion...    [WRONG: = not aligned]
	InfrastructureOlReducePaInfo = ...        [WRONG: Tab used instead of spaces]
```

## Related Files

- `/mnt/web/fitantanana/htdocs/custom/infrastructure/langs/fr_FR/infrastructure.lang`
- `/mnt/web/fitantanana/htdocs/custom/infrastructure/langs/en_US/infrastructure.lang`
- `/mnt/web/fitantanana/htdocs/custom/infrastructure/langs/es_ES/infrastructure.lang`
- `/mnt/web/fitantanana/htdocs/custom/infrastructure/langs/it_IT/infrastructure.lang`
