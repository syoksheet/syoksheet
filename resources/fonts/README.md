# Geist

Vendored from the official release at https://github.com/vercel/geist-font rather than
installed from npm. The npm package is built for Next.js: 7.9MB to give us 214KB, an
`exports` map that does not expose the font files at all, and a set of `next/font`
modules none of which apply here.

Three variable files cover every weight the type scale asks for, 400 through 700 sans
and 400 through 600 mono, in one download each.

| File | Source in the release |
| --- | --- |
| `Geist-Variable.woff2` | `Geist/webfonts/Geist[wght].woff2` |
| `Geist-Italic-Variable.woff2` | `Geist/webfonts/Geist-Italic[wght].woff2` |
| `GeistMono-Variable.woff2` | `GeistMono/webfonts/GeistMono[wght].woff2` |

Renamed on the way in, because square brackets in a filename are awkward in a URL. The
files are otherwise untouched: all three are byte-identical to what the npm package
ships, verified by SHA-256.

There is no mono italic. Mono carries IDs, timestamps and hashes, which are never
italic, so the rare case can fall back to a synthesised oblique.

Note the release also has a `variable/` folder, but that holds `.ttf`. The variable
`.woff2` we want live under `webfonts/` alongside the static weights.

## License

SIL Open Font License 1.1, see `OFL.txt`. The license permits redistribution provided
the copyright notice travels with the fonts, so that file has to stay here.

## Updating

Download the latest release, copy the three `webfonts/*[wght].woff2` files over the
ones here using the mapping above, and refresh `OFL.txt` at the same time.
