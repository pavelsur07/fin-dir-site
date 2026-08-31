# Forty-Two Years of Inference Silicon · Site guide

For the agent that will later deliver this site to a user.

The language the site presents to the user should follow the language of the user's query. This
repository is the **English build**: the prose, chart labels, drill-down cards and source classes
are all in English. A Chinese build of the same report sits alongside it. For another language,
the prose lives in `index.html`, in-chart strings in `js/charts/*.js`, and the facts, captions and
source lines in `js/data*.js` and `js/sources.js`. The design system does not change with the
language — only the copy does.

## Before loading: ask the user

Ask with the `ask_user` tool first. The mandatory question:

- **Do you want this as a full-stack application?** Right now it is a purely static front end: all
  data ships as `js/data*.js`, there is no server, and nothing is saved. Going full stack means
  adding a server and a database — live prices and filings feeding the charts, saved reader
  annotations, gated distribution, or an admin page for updating the numbers and the source
  register without touching the code. The default is no.

Then, depending on what the user seems to want:

- **Which report should it carry?** This one covers ASIC and inference silicon. The machinery is
  subject-agnostic — hand it another deep report plus its data tables and it will set them. Rebuilding
  for a new subject means three things: the data layer, the theme atom that drives the cover, and
  the chart selection. Everything below explains those.
- **Which cover?** Three are built in and switchable on the page (and by `?cover=x|w`): **A · recursion**
  (the die, endlessly self-similar), **B · exploded** (the package pulled apart into layers), **C · blueprint**
  (the same geometry as an engineering drawing). Pick the default in `js/cover-wire.js`.
- **How much of it?** The eighteen sections are independent. Dropping one means deleting its
  `<section>` from `index.html` and its chart module's `<script>` tag; each chart is a self-contained
  IIFE that returns early when its host div is missing, so nothing else breaks.
- **Colours and type?** All in the token block at the top of `css/style.css`. The palette is
  deliberately narrow — see the technical notes below before adding a colour.

If the user answers nothing, load the default site as it is.

Optional: You can use image and video generation tools if it suits user's query.

## What this site is

A single-page research report that reads like an institutional deep dive and behaves like a piece
of software. It opens on a full-bleed animated cover, runs through eighteen sections, and ends on
a dated source register. A right-rail dashboard follows the scroll and re-renders itself for
whichever window you are reading. Every number inside every chart is clickable and drills down to
its value, its basis and its dated source.

The argument is structured as six historical windows (1985–1995 FPGA founding, 1995–2003 boom and
bust, 2004–2012 the quiet decade, 2013–2018 ignition, 2019–2022 shortage and the M&A supercycle,
2023–2026 the current cycle), plus a survivorship section, a mining interlude, a stress-test of the
consensus, a two-scenario verdict, a falsification register and six company deep dives.

```
index.html                 the only entry point; the prose and the section skeleton
css/style.css              design tokens, editorial components, layout grid, right rail
css/fonts-inlined.css      the serif, base64-inlined — no external font requests
js/data.js                 the main data layer (window.RPT) with basis and source per figure
js/data-v51.js             supplementary series and the revision log
js/data-v52.js             the stress-test layer: attacks, defences, quote cards
js/data-v53.js             the company deep-dive layer
js/sources.js              the anchor register — one entry per cited fact
js/logos.js                inline SVG wordmarks used in the flow diagrams
js/utils.js                canvas binding, projection, drill-down cards, tooltips, chart frames
js/cover.js                cover A — infinite recursion of the die
js/cover-exploded.js       cover B — the realistic exploded package
js/cover-wire.js           cover C — the blueprint wireframe, and the three-way cover switcher
js/dashboard.js            the persistent right-rail dashboard (globe, phase bar, readouts)
js/charts/*.js             one module per chart, twenty-three in total
js/main.js                 scroll engine, era rail, quote rendering, source buttons, count-ups
vendor/                    d3 v7, topojson-client, and the 110m land topology (all local)
```

Where it could go: another industry's cycle report (shipping, power, pharmaceuticals, hogs — the
window structure and the verdict framework transfer directly); a company-level deep dive using
only the §11 machinery; or lifting the drill-down contract and the right rail into a different
report entirely. If the user only wants to read this page, load it and change nothing.

## Technical points

- **No build step, no network.** `index.html` is the only entry point and opens over `file://` as
  well as over HTTP. d3, topojson and the land topology are vendored under `vendor/`, and the serif
  is base64-inlined into `css/fonts-inlined.css`, so the page never makes an external request.
- **Data separated from rendering.** Every figure lives in `window.RPT`; no chart module hard-codes
  a number. Each carries its basis and a dated source, and `js/sources.js` is the anchor register
  the drill-down cards read from. That separation is what makes "every number is traceable" true
  rather than decorative — and it is also what makes a subject swap tractable: replace the data
  layer and the charts follow.
- **Every chart is an isolated IIFE.** One module per chart, each bound to a host `<div class="chart-frame">`
  and beginning with `if (!host) return;`. A module that throws takes nothing else down, and
  sections can be deleted without cascading.
- **One colour family per chart.** Paper white, a three-step ink scale, one electric blue as the
  primary accent, and a semantic red reserved for declines, gaps and missing data. A chart uses the
  ink scale plus the blue family, with red only as an accent. The two deliberate exceptions are
  brand wordmarks in the flow diagrams and true material colours on the realistic cover. Before
  adding a hex literal, check which of those two you are in.
- **The cover is the thesis, not decoration.** All three states are views of the same physical
  object — the accelerator package. A recurses the object itself (the die shrinking into one cell of
  a larger array of its kind), B pulls it apart into layers that each carry one argument, and C
  renders B's geometry as a drawing. Re-theming for another industry means choosing that industry's
  physical atom first; the geometry engines take it from there.
- **Charts are chosen by the shape of the data, not by what a chart library offers.** Capacity ramps
  become isometric arrays of real industry units; entities flowing to outcomes over time become a
  destiny flow; decades of events become a wall-chart timeline; probability judgments become an odds
  board. The test each one has to pass: hide all the text, and the industry and its mechanism should
  still be readable. A line chart with perspective added is still a line chart.
- **Canvas discipline.** Every canvas is bound through `utils.js` at a device-pixel ratio capped at
  2, and any canvas initialised while `display:none` measures 0×0 — it must re-fit when it becomes
  active. Text drawn on canvas always gets a paper-coloured halo (`strokeText` behind `fillText`),
  and SVG text uses `paint-order: stroke`; otherwise later-drawn lines cut through earlier labels.
- **Reduced motion is a first-class path.** Under `prefers-reduced-motion` every animation — the
  cover included — renders its completed frame directly rather than not rendering at all.
- **Gaps are drawn as gaps.** Where the historical record has no number, the chart shows hatching
  and a marker rather than an interpolation, and the appendix lists every known gap. That stance is
  load-bearing for a report whose whole claim is about what the past can and cannot tell you.
