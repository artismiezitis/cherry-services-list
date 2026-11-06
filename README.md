# Cherry Services List — Creative IT

Version **1.5.1-cit** is the PHP 8 compatibility edition of Zemez / Cherry Team's
Cherry Services List. Client installation and usage instructions are in
[readme.txt](readme.txt).

## Requirements and validation

Requires WordPress **6.0+** and PHP **8.0+**. Tested on WordPress **6.0** and
**7.1.4** with PHP **8.0.30**, **8.1.34**, **8.2.35**, **8.3.36**, **8.4.27** and
**8.5.12**. Each combination passed frontend and admin integration checks.
Elementor **4.3.6** widget registration, controls and rendering passed on
WordPress 7.1.4 with PHP 8.1–8.5. These are tested versions, not a guarantee
about future PHP, WordPress or third-party releases.

Checks cover activation over existing options, service creation/categories,
shortcodes, ID lists, query reuse, empty queries, filtering and pagination AJAX,
form nonce/field validation and confirmation, metabox rendering, cloning with
structured metadata/categories, and settings persistence. Mail is intercepted;
real delivery depends on the site's mail configuration. JavaScript DOM tests
cover readiness, two independent listings, filters, More and pagination with
and without Elementor. PHPCompatibility reports no findings for PHP 8.0–8.5;
all bundled PHP files pass lint on all six PHP branches.

No interactive browser was connected during this work. Screenshot-based layout
review, the native block editor's interactive save flow, existing client theme
overrides and the optional Cherry Testimonials integration still require
verification in the client's environment. HTTP smoke checks complement the
integration tests; DOM tests do not establish visual appearance.

## Architecture and compatibility

* Keep the installed folder `cherry-services-list` and main file
  `cherry-services-list.php`. Services use the `cherry-services` post type,
  `cherry-services_category` taxonomy, existing metadata and the
  `cherry-services` / `cherry-services_default` options. Activation preserves
  existing values and adds defaults only when absent. No new tables are used.
* `cherry-framework/` is vendored from upstream 1.5.11, commit
  `2d41f2181193495122a31144bbbfcb04f7493b61`, with compatibility patches. It is
  intentionally no longer a Git submodule: GitHub source ZIPs must include the
  dependency and its fixes. Framework and plugin version numbers are separate.
  Preserve upstream attribution and GPL licensing when distributing it.
* PHP templates use the active theme's normal header/footer for classic themes
  and the shared `header-services.php` / `footer-services.php` shell for block
  themes. Custom theme templates retain precedence. A configured archive in
  page-content mode keeps WordPress's normal block-page template.
* Service queries return `WP_Query` even for zero matches. Public listings query
  published services. AJAX input merges the existing shared defaults, and AJAX
  pagination uses the requested page. Template names cannot contain paths.
* The CTA uses WordPress nonces and `wp_mail()`. PHP sessions are opened only
  for a form submission or an existing session cookie, and locks are released
  immediately. Test code never sends email.
* `Update URI` points to this fork to prevent WordPress.org replacement. There
  is no automatic GitHub updater. Clients upload release ZIPs manually.
* `.idea`, AI instructions and local skill directories are ignored. Release
  ZIPs contain runtime files, licences and `readme.txt`; development tools and
  AI instructions are excluded.

## Run the checks

Use a disposable WordPress installation with its own database, at least one
administrator and a theme. Integration tests roll back fixture database writes
but activation flushes rewrites, and WordPress or optional plugins can write
outside the transaction during bootstrap. Do not use a production database.

```powershell
php tests/integration.php C:/path/to/test-wordpress
php tests/integration.php C:/path/to/test-wordpress admin
```

Repeat with each PHP executable. For optional Elementor integration, first
install Elementor in the disposable WordPress installation, then set:

```powershell
$env:CHERRY_TEST_ELEMENTOR = 'C:/path/to/test-wordpress/wp-content/plugins/elementor/elementor.php'
php tests/integration.php C:/path/to/test-wordpress admin
Remove-Item Env:CHERRY_TEST_ELEMENTOR
```

The bootstrap loads this checkout as the sole normal plugin. Elementor is
loaded only when the environment variable is set. Test mail is intercepted
before WordPress bootstrap. No database credentials belong in this repository.

JavaScript test dependencies can be installed outside the checkout:

```powershell
npm install --prefix C:/path/to/test-tools jsdom@26 jquery@3.7.1
$env:NODE_PATH = 'C:/path/to/test-tools/node_modules'
node tests/frontend.cjs
```

For static analysis, install PHPCompatibility 10.x and PHP_CodeSniffer in a
separate tools directory and run its `phpcs` with `--standard=PHPCompatibility
--runtime-set testVersion 8.0-8.5 --extensions=php` against this checkout.

## Build a client package

Enable PHP's zip extension, then run:

```powershell
php scripts/build-release.php
```

This creates `dist/cherry-services-list-1.5.1-cit.zip` and its SHA-256 checksum.
The ZIP has one `cherry-services-list/` directory and includes the patched
framework. An optional first argument changes the destination directory.
Upload this built ZIP as the release asset for clients. GitHub publication,
tagging and pushing are separate actions and were not performed.

Replace an existing installation through WordPress's Upload Plugin / Replace
current with uploaded workflow. Preserve modifications inside the previous
plugin directory separately and test existing theme overrides after updating.
