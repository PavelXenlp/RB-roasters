# Design

## Source of truth
- Status: Active
- Last refreshed: 2026-07-18
- Primary product surfaces: storefront, catalog, product, cart, account, loyalty, business, news, training
- Evidence reviewed: `assets/css/style.css`, `header.php`, `footer.php`, `pages/home.php`, `pages/loyalty.php`, `pages/news.php`, `single-rb_article.php`

## Brand
- Personality: knowledgeable local coffee roaster; direct, modern, practical
- Trust signals: production photography, team expertise, awards, transparent product information
- Avoid: generic WordPress language, decorative clutter, oversized marketing cards

## Product goals
- Goals: sell coffee, collect business requests, explain production expertise, publish useful news
- Non-goals: imitate a marketplace or add plugin-dependent commerce UI
- Success signals: clear catalog paths, readable content, usable cart and account flows

## Personas and jobs
- Primary personas: retail coffee buyers, business customers, baristas and trainees
- User jobs: choose coffee, place an order, request service, learn about the company
- Key contexts of use: mobile browsing and desktop product comparison

## Information architecture
- Primary navigation: home, about, catalog, loyalty, delivery, business, news, contacts
- Core routes/screens: catalog, product, cart, account, article, training, brewing method
- Content hierarchy: action or title first, supporting proof second, detailed content third

## Design principles
- Make operational actions obvious and content easy to scan.
- Use photography as evidence, not decoration.
- Preserve visual rhythm while allowing editorial asymmetry on content-led surfaces.
- Tradeoffs: expressive layouts must collapse to predictable single-column mobile reading.

## Visual language
- Color: white and near-black base, `#8aa319` green accent, restrained red pricing accent
- Typography: Montserrat for UI and copy, Bebas Neue for selected brand display text
- Spacing/layout rhythm: 1040px content width, generous section spacing, compact internal card spacing
- Shape/radius/elevation: 8px radius, light borders, restrained shadows
- Motion: short hover and open/close transitions; respect clarity over spectacle
- Imagery/iconography: real production photography and Lucide-style line icons

## Components
- Existing components to reuse: buttons, page heads, cards, section titles, grids, modals
- New/changed components: editorial home news grid, article intro, related-news sidebar, brewing-method detail and recommendations, loyalty progression and registration CTA, mobile business-benefits carousel
- Variants and states: desktop asymmetric grid; tablet two-column; mobile single-column
- Token/component ownership: `assets/css/style.css`

## Accessibility
- Target standard: WCAG 2.1 AA where practical
- Keyboard/focus behavior: interactive cards and links remain native focusable elements
- Contrast/readability: body text stays on solid light backgrounds; no text over busy imagery
- Screen-reader semantics: article, header, section, aside and descriptive image alternatives
- Reduced motion and sensory considerations: motion is nonessential and short

## Responsive behavior
- Supported breakpoints/devices: desktop, <=1100px, <=880px, <=620px
- Layout adaptations: article and news grids reduce columns without changing reading order
- Touch/hover differences: content remains available without hover

## Interaction states
- Loading: first-visit branded preloader
- Empty: short contextual empty messages and navigation back to broader content
- Error: native form validation plus server-side WordPress validation
- Success: explicit confirmation for account, cart and order actions
- Disabled: visually muted while retaining readable labels
- Offline/slow network: lazy-load noncritical imagery and preserve text-first rendering

## Content voice
- Tone: confident, concise, helpful
- Terminology: use Roastberry Coffee Roasters and coffee-industry terms consistently
- Microcopy rules: Russian user-facing text; no framework or CMS commentary

## Implementation constraints
- Framework/styling system: custom WordPress theme, PHP templates, vanilla CSS and JavaScript
- Design-token constraints: extend existing CSS custom properties before adding new tokens
- Performance constraints: responsive WordPress thumbnails and lazy loading outside the first viewport
- Compatibility constraints: modern browsers and WordPress 7.0
- Test/screenshot expectations: PHP lint plus desktop and mobile visual checks when a runnable WordPress environment is available

## Open questions
- [ ] Confirm final browser support matrix before production launch.
