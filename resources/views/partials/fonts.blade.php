{{--
  Self-hosted webfonts — Plus Jakarta Sans (UI) + IBM Plex Mono (figures).

  WHY SELF-HOSTED, NOT GOOGLE FONTS: the redesign this implements loaded both
  families from fonts.googleapis.com. Every external font fetch is a blocking
  request before first paint, and removing the last one (fonts.bunny.net) took
  login from 1254ms to 103ms. It would also break the offline deployment this
  platform is being funded to support — a school in Skardu with no uplink
  cannot reach Google's CDN, and would silently fall back to a system face,
  losing the design entirely.

  Plus Jakarta Sans ships as one variable file covering 400-800, so the whole
  weight range costs 27KB. All three files together are 60KB, served from our
  own origin and cached.

  Latin subset only. Urdu is a separate face handled per-panel; do not add
  Urdu ranges here or every English page pays for them.
--}}
<style>
  @font-face {
    font-family: 'Plus Jakarta Sans';
    font-style: normal;
    font-weight: 400 800;
    font-display: swap;
    src: url('{{ asset('fonts/plus-jakarta-sans-latin-var.woff2') }}') format('woff2');
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC,
                   U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193,
                   U+2212, U+2215, U+FEFF, U+FFFD;
  }

  @font-face {
    font-family: 'IBM Plex Mono';
    font-style: normal;
    font-weight: 500;
    font-display: swap;
    src: url('{{ asset('fonts/ibm-plex-mono-latin-500.woff2') }}') format('woff2');
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC,
                   U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193,
                   U+2212, U+2215, U+FEFF, U+FFFD;
  }

  @font-face {
    font-family: 'IBM Plex Mono';
    font-style: normal;
    font-weight: 600;
    font-display: swap;
    src: url('{{ asset('fonts/ibm-plex-mono-latin-600.woff2') }}') format('woff2');
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC,
                   U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193,
                   U+2212, U+2215, U+FEFF, U+FFFD;
  }
</style>
