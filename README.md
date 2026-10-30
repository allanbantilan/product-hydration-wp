# Hydration Product Launch | Elementor Landing Page

A product launch page for a smart hydration bottle, built as a portfolio project. The page sections live in Elementor. The companion theme supplies the design, motion, light/dark mode, pricing toggle, countdown, referral waitlist, and optional Mailchimp syncing. Elementor is required.

## Install

### Local Docker test

Docker Desktop must be running. From this folder, run `py -3 dev.py`. It creates `.env` with local passwords, starts WordPress and MariaDB, installs WordPress and Elementor, activates the theme, and creates the Elementor homepage. Open `http://localhost:8080`; WordPress admin is at `http://localhost:8080/wp-admin` with user `admin` and the `ADMIN_PASSWORD` in `.env`. If port 8080 is occupied, set `WP_PORT` in `.env` to a free port and rerun `py -3 dev.py`. On this machine the current URL is `http://localhost:60848/`. The theme folder is mounted read-only; theme file edits appear after a refresh. Elementor edits are saved in the local WordPress database. Run `docker compose down` to stop it; the named database volume preserves signups and Elementor edits. Do not use `down -v` unless you intend to discard all local WordPress data.

Edit the landing page at **Pages → Hydration Launch Page → Edit with Elementor**. The hero headline, copy, and button use native Elementor widgets. Other static sections are separate Elementor containers with HTML widgets, so you can edit and reorder their markup in the editor. The early access and signup sections are shortcode widgets because they show live WordPress data. Edit their markup in `template-parts/landing-voices.php` and `template-parts/landing-waitlist.php`. The theme controls global styling in `style.css`. `elementor-page.json` is the initial page layout; the importer leaves an existing Elementor page alone so later editor changes survive `dev.py`.

For a local Mailchimp test, fill `AERA_MAILCHIMP_API_KEY` and `AERA_MAILCHIMP_AUDIENCE_ID` in `.env`, then run `docker compose up -d --force-recreate wordpress`. Keep `.env` private.

Run `py -3 tests/test_local.py` for a live signup and referral smoke test. It removes the generated test signups afterward.
Run `py -3 tests/test_ui.py` for desktop/mobile interaction checks and screenshots in `screenshots/` (requires Playwright and its Chromium browser).
Run `py -3 tests/audit_lighthouse.py` for a local mobile Lighthouse report in `screenshots/` (requires Lighthouse through `npx`). The theme bundles the two fonts it uses under their included licenses, so the page does not need a Google Fonts request.

### Existing WordPress site

1. Install and activate the free **Elementor** plugin.
2. Upload `hydration-launch.zip` in **Appearance → Themes** and activate **Hydration Product Launch**. Activation creates the waitlist table.
3. Run `wp eval-file wp-content/themes/hydration-launch/install-elementor.php` from WP CLI. This creates the Elementor homepage and sets it as the front page. The importer preserves an existing Elementor page. Set the site URL and name as usual.
4. For Mailchimp, add these constants to `wp-config.php` using your own values:

   ```php
   define('AERA_MAILCHIMP_API_KEY', 'your-key-us21');
   define('AERA_MAILCHIMP_AUDIENCE_ID', 'your-audience-id');
   ```

   A new address is sent as **pending**, so Mailchimp manages confirmation. WordPress keeps the local signup even if its API is unavailable. Check **Launch Waitlist** in the admin menu to see sync status and retry failed syncs.

5. Change the launch date in `template-parts/landing-waitlist.php` (`data-launch`) and Elementor's FAQ section, and the sample offer before using this for a real campaign. Update the site privacy notice for email and referral data retention.

After editing theme files, run `py -3 package_theme.py` to rebuild the installable ZIP. The Docker site already uses the current source files, so it needs only a refresh. Elementor editor changes live in the database and are not included in the theme ZIP; export the page from Elementor after editing if you need to move those changes to another site.

## Page wireframe

1. **Hero:** product promise, bottle visual, one waitlist CTA.
2. **Story:** the hydration problem and the gentle reminder solution.
3. **Features:** sticky bottle visual responds to three scroll stages.
4. **How it works:** fill, notice, feel.
5. **Comparison:** the smart bottle against a standard bottle.
6. **Early access:** current signup status and product benefit cards.
7. **Pricing:** monthly/yearly estimate and toggle.
8. **FAQ:** native disclosure controls.
9. **Final CTA:** launch countdown and signup/share state.
10. **Footer:** section links and product brand.

## Referral behavior

Each new signup receives a random share code. A distinct new email submitted with `?ref=CODE` credits one referral to that code. The member page shows the referral count and position, sorted by referral count descending, then signup order. Repeated signup with the same email does not create a new credit. The admin page shows the latest 100 signups. The local build rate limits signups per IP for one minute; production campaigns may need a stronger abuse check and email verification before rewarding referrals.

## Case study

**Problem:** hydration is easy to forget, while many wellness products add another distracting notification.

**Solution:** a calm, design-led bottle with a visual reminder, explained through an editorial page and one clear early access action.

**Build:** an Elementor page, a companion WordPress theme, hand-drawn SVG product art, CSS and native browser interactions, a WordPress waitlist table, and Mailchimp API syncing.

**Result to measure:** signup conversion, successful email confirmation, referral rate, and mobile performance once deployed. The page has no live campaign data yet.

## Checks

Run `node --check assets/site.js`, `py -3 tests/test_rank.py`, `py -3 tests/test_local.py`, `py -3 tests/test_ui.py`, `py -3 tests/test_elementor.py`, `py -3 tests/test_chrome_mcp.py`, and `py -3 tests/audit_lighthouse.py`. Mailchimp delivery requires valid credentials. The Chrome MCP test starts an isolated Chrome profile, so it does not disturb browser sessions for other projects.
Run `docker compose run --rm cli wp eval-file wp-content/themes/hydration-launch/tests/mailchimp_mock.php` to check Mailchimp failure and retry behavior without sending email.
