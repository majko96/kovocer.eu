// Generates design/og/og.svg (text converted to outlines) and public/og-image.png.
// Usage: OG_FONT='/path/RobotoCondensed[wght].ttf' node design/og/generate.cjs
// Needs fontkit (npm i --no-save fontkit) and the variable TTF from github.com/google/fonts (ofl/robotocondensed).
// Zloží OG SVG s textom prevedeným na krivky (bez závislosti na systémovom písme) a vyrenderuje PNG.
const fontkit = require("fontkit");
const fs = require("fs");
const path = require("path");
const root = path.resolve(__dirname, "../..");
const sharp = require(path.join(root, "node_modules/sharp"));
const base = fontkit.openSync(process.env.OG_FONT);

const G = "#C9B57E";
const cache = {};
const fontAt = (w) => (cache[w] ??= base.getVariation({ wght: w }));

// text -> SVG <path>; spacing v px, anchor: start|end
function text(str, { x, y, size, weight = 400, spacing = 0, fill, anchor = "start" }) {
  const f = fontAt(weight);
  const run = f.layout(str);
  const k = size / f.unitsPerEm;
  let total = 0;
  run.glyphs.forEach((g, i) => (total += run.positions[i].xAdvance * k + spacing));
  total -= spacing;
  let cx = anchor === "end" ? x - total : x;
  let d = "";
  run.glyphs.forEach((g, i) => {
    const p = run.positions[i];
    const gp = g.path;
    if (gp && gp.commands.length) {
      d += gp.toFunction ? "" : "";
      const m = `translate(${(cx + p.xOffset * k).toFixed(2)} ${(y - p.yOffset * k).toFixed(2)}) scale(${k.toFixed(5)} ${(-k).toFixed(5)})`;
      d += `<path transform="${m}" d="${gp.toSVG()}" fill="${fill}"/>`;
    }
    cx += p.xAdvance * k + spacing;
  });
  return { svg: d, width: total };
}

const emb = fs.readFileSync(path.join(root, "src/components/Emblem.astro"), "utf8");
const inner = emb.match(/<svg[^>]*>([\s\S]*)<\/svg>/)[1].trim().replace(/currentColor/g, G);

let out = "";
const wm1 = text("KOVOCER", { x: 178, y: 116, size: 52, weight: 700, spacing: 9, fill: "#F3F1EC" });
const wm2 = text(".eu", { x: 178 + wm1.width + 9, y: 116, size: 52, weight: 400, spacing: 3, fill: G });
out += wm1.svg + wm2.svg;
out += text("KOVANÉ BRÁNY", { x: 80, y: 305, size: 112, weight: 900, spacing: -1, fill: "#F3F1EC" }).svg;
const l2a = text("A PLOTY ", { x: 80, y: 418, size: 112, weight: 900, spacing: -1, fill: "#F3F1EC" });
const l2b = text("NA MIERU", { x: 80 + l2a.width - 1, y: 418, size: 112, weight: 900, spacing: -1, fill: G });
out += l2a.svg + l2b.svg;
out += text("Kovovýroba Prečín", { x: 82, y: 490, size: 34, weight: 400, spacing: 2, fill: "#A4ACAF" }).svg;
out += text("0911 782 251", { x: 80, y: 590, size: 40, weight: 700, spacing: 4, fill: G }).svg;
out += text("KOVOCER.EU", { x: 1120, y: 590, size: 32, weight: 400, spacing: 4, fill: "#F3F1EC", anchor: "end" }).svg;

const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
<defs><radialGradient id="glow" cx="22%" cy="8%" r="70%"><stop offset="0" stop-color="${G}" stop-opacity=".28"/><stop offset="1" stop-color="${G}" stop-opacity="0"/></radialGradient></defs>
<rect width="1200" height="630" fill="#0B171C"/><rect width="1200" height="630" fill="url(#glow)"/>
<g transform="translate(700 40) scale(8.4)" opacity=".13">${inner}</g>
<g transform="translate(80 64) scale(1.3)">${inner}</g>
${out}
</svg>`;
fs.writeFileSync(path.join(root, "design/og/og.svg"), svg);
sharp(Buffer.from(svg), { density: 72 }).png({ compressionLevel: 9 }).toFile(path.join(root, "public/og-image.png")).then(() => console.log("OK"));
