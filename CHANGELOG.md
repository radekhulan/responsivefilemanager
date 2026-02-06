# CHANGELOG - ResponsiveFilemanager (Modernized Fork)

**Author:** Radek Hulán (https://mywebdesign.dev/)
**Base Version:** 9.15.0 (JavaScript: 9.14.0)
**PHP Support:** 8.0 - 8.5
**TinyMCE Support:** TinyMCE 8.x
**Original Project:** https://github.com/trippo/ResponsiveFilemanager

---

## 1. PHP 8.x COMPATIBILITY
- Full support for PHP 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5
- Fixed multibyte string functions for UTF-8
- Updated error reporting (`E_ERROR | E_PARSE`)
- Removed deprecated PHP 5.x patterns

---

## 2. TINYMCE 8 INTEGRATION

**New plugin:** `tinymce/plugins/responsivefilemanager/plugin.min.js`

- Native TinyMCE 8.x API support
- Uses `editor.options.register()` for configuration
- Modern `window.postMessage()` instead of legacy window.opener
- Dialog management via `editor.windowManager.openUrl()`
- Converts absolute URLs to relative paths
- Registered toolbar button and menu item

---

## 3. DIALOG.PHP - TINYMCE 8 SUPPORT

**File:** `filemanager/dialog.php` (lines 1228-1297)

- Override `apply_any()`, `apply_img()`, `apply_link()`, `apply_video()`
- PostMessage communication instead of legacy methods
- Backward compatibility with field_id mode preserved

---

## 4. NEW RESPONSE CLASS

**File:** `filemanager/include/Response.php`

- Simplified Symfony HTTP Foundation Response
- Compatibility with Laravel and other PHP frameworks
- HTTP status codes, JSON encoding, header management

---

## 5. UTILS.PHP IMPROVEMENTS

**File:** `filemanager/include/utils.php`

- Enhanced `checkRelativePath()` with URL decode validation
- New `response()` helper function with framework detection
- Improved `trans()` function
- New `AddErrorLocation()` for debug information
- Support for nested folder .config.php files

---

## 6. UPLOAD HANDLER

**File:** `filemanager/upload.php`

- URL upload support with CURL
- Tempfile validation
- Improved error handling

---

## 7. CONFIGURATION

**File:** `filemanager/config/config.php`

- Version 9.15.0
- PHP 8.x compatible settings
- Complete UTF-8 encoding chain
- Timezone default: 'Europe/Rome'

---

## 8. JAVASCRIPT MODERNIZATION

**File:** `filemanager/javascript/include.js`

- Version 9.14.0
- PostMessage support for TinyMCE 8
- Improved URL handling for crossdomain

---

## 9. OPTIONAL DARK MODE

**File:** `filemanager/css/style.dark.css`

- Added a complete dark mode theme for the file manager UI
- Can be enabled via configuration in `config.php`
- Dark backgrounds, adjusted contrast and colors for comfortable use in low-light environments

---

## 10. SVG ICONS

**Directory:** `filemanager/svg/`

- Replaced all legacy raster (PNG) icons with clean, scalable SVG icons
- Resolution-independent icons that look sharp on all displays (including HiDPI/Retina)
- Removed old PNG images from `filemanager/img/`

---

## 11. LATEST TUI IMAGE EDITOR

**Directory:** `filemanager/javascript/vendor/`

- Bundled the newest version of TUI Image Editor for in-browser image editing
- Includes TUI Color Picker and Fabric.js dependencies
- Added corresponding CSS files (`tui-image-editor.min.css`, `tui-color-picker.min.css`)

---

## 12. JAVASCRIPT LIBRARIES UPDATE

**Directory:** `filemanager/javascript/`

All JavaScript libraries upgraded to latest versions with local copies (removing unreliable CDN dependencies):

| Library | Version | Purpose |
|---------|---------|---------|
| jQuery | 3.7.1 | Core framework |
| jQuery Migrate | 3.5.2 | Compatibility layer |
| jQuery UI | 1.14.1 | UI components |
| jPlayer | 2.9.2 | Audio/video playback |
| load-image | Latest | Image processing |
| canvas-to-blob | Latest | Canvas support |
| tmpl | Latest | Templating |

**Rationale:** Many CDNs referenced in the original version are unreliable or no longer exist. All libraries are now served locally for better reliability and performance.

---

## 13. SECURITY IMPROVEMENTS

- Path traversal prevention in `checkRelativePath()`
- URL pattern validation
- Improved extension checking
- Session token verification

---

## SUMMARY OF CHANGES

| Aspect | Original | Modernized |
|--------|----------|------------|
| **PHP** | 5.x - 7.x | 8.0 - 8.5 |
| **TinyMCE** | 3.x - 5.x | 8.x native |
| **Communication** | window.opener | postMessage |
| **UTF-8** | Partial | Full support |
| **JS Libraries** | CDN-based | Local copies |
| **Theme** | Light only | Light + Dark mode |
| **Icons** | PNG raster | SVG scalable |
| **Image Editor** | Old TUI version | Latest TUI Image Editor |
