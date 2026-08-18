"""Replace EFGTrack branding with GWTrack inside glyph-encoded resource PDFs."""

from __future__ import annotations

import re
import sys
from pathlib import Path

import pikepdf
from pypdf import PdfReader

ROOT = Path(__file__).resolve().parents[1]
DOC_DIR = ROOT / "storage" / "app" / "public" / "resources" / "documents"

WORD_SWAPS = [
    ("EFGTrack", "GWTrack"),
    ("EFGTRACK", "GWTRACK"),
    ("efgtrack", "gwtrack"),
    ("Efgtrack", "Gwtrack"),
]

RENAME_FILES = {
    "helping-clients-become-efg-associates-24.pdf": "helping-clients-become-gw-associates-24.pdf",
    "efgtrack-application-user-guide-26.pdf": "gwtrack-application-user-guide-26.pdf",
}

HEX_TOKEN_RE = re.compile(rb"<([0-9A-Fa-f]*)>")
BETWEEN = rb"(?:-?\d+(?:\.\d+)?)?(?:<>(?:-?\d+(?:\.\d+)?)?){0,4}"
BFCHAR_RE = re.compile(r"<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>")


def count_brand(path: Path) -> int:
    text = "\n".join((page.extract_text() or "") for page in PdfReader(str(path)).pages)
    return len(re.findall(r"efgtrack", text, flags=re.I))


def parse_tounicode(data: bytes) -> dict[str, str]:
    text = data.decode("latin-1", errors="replace")
    uni_to_cid: dict[str, str] = {}
    for m in BFCHAR_RE.finditer(text):
        cid_raw = m.group(1).lower()
        uni_hex = m.group(2)
        if len(uni_hex) < 4 or len(cid_raw) > 4:
            continue
        ch = chr(int(uni_hex[:4], 16))
        uni_to_cid.setdefault(ch, cid_raw.zfill(4))
    return uni_to_cid


def collect_cid_maps(pdf: pikepdf.Pdf) -> list[dict[str, str]]:
    maps: list[dict[str, str]] = []
    seen: set[tuple[tuple[str, str], ...]] = set()
    for page in pdf.pages:
        fonts = page.Resources.get("/Font") or {}
        for _name, font in fonts.items():
            tu = font.get("/ToUnicode")
            if not tu:
                continue
            try:
                u2c = parse_tounicode(tu.read_bytes())
            except Exception:
                continue
            needed = set("EFGTrackWefgw")
            if not needed.issubset(u2c):
                # Accept partial maps that can still encode at least one swap.
                if not all(ch in u2c for ch in "EFGTrack"):
                    continue
            key = tuple(sorted((ch, u2c[ch]) for ch in needed if ch in u2c))
            if key in seen:
                continue
            seen.add(key)
            maps.append(u2c)
    return maps


def expand_hex_tokens(data: bytes) -> bytes:
    def expand(m: re.Match[bytes]) -> bytes:
        payload = m.group(1).decode("ascii").lower()
        if payload == "":
            return b"<>"
        if len(payload) % 4 != 0:
            return m.group(0)
        return "".join(f"<{payload[i:i+4]}>" for i in range(0, len(payload), 4)).encode("ascii")

    return HEX_TOKEN_RE.sub(expand, data)


def word_to_cids(word: str, uni_to_cid: dict[str, str]) -> list[str] | None:
    try:
        return [uni_to_cid[ch] for ch in word]
    except KeyError:
        return None


def replace_with_map(data: bytes, uni_to_cid: dict[str, str]) -> tuple[bytes, int]:
    expanded = expand_hex_tokens(data)
    total = 0
    for old_word, new_word in WORD_SWAPS:
        old_cids = word_to_cids(old_word, uni_to_cid)
        new_cids = word_to_cids(new_word, uni_to_cid)
        if not old_cids or not new_cids:
            continue

        parts: list[bytes] = [f"<{old_cids[0]}>".encode("ascii")]
        for cid in old_cids[1:]:
            parts.append(rb"(" + BETWEEN + rb")")
            parts.append(f"<{cid}>".encode("ascii"))
        pattern = re.compile(b"".join(parts))

        def make_repl(new_cids: list[str]):
            def repl(match: re.Match[bytes]) -> bytes:
                spacers = list(match.groups())
                if len(spacers) >= len(new_cids):
                    spacers = spacers[1 : len(new_cids)]
                if len(spacers) < len(new_cids) - 1:
                    spacers = spacers + [b""] * ((len(new_cids) - 1) - len(spacers))
                else:
                    spacers = spacers[: len(new_cids) - 1]
                out = bytearray(f"<{new_cids[0]}>".encode("ascii"))
                for cid, spacer in zip(new_cids[1:], spacers):
                    out.extend(spacer)
                    out.extend(f"<{cid}>".encode("ascii"))
                return bytes(out)

            return repl

        expanded, n = pattern.subn(make_repl(new_cids), expanded)
        total += n
    return expanded, total


def iter_content_streams(page) -> list:
    contents = page.Contents
    if contents is None:
        return []
    if isinstance(contents, pikepdf.Array):
        return list(contents)
    return [contents]


def rebrand_pdf(path: Path) -> int:
    replacements = 0
    with pikepdf.open(path, allow_overwriting_input=True) as pdf:
        maps = collect_cid_maps(pdf)
        if not maps:
            print("  warning: no usable ToUnicode maps")
            return 0
        print(f"  cid maps: {len(maps)}")
        for page in pdf.pages:
            for stream in iter_content_streams(page):
                try:
                    raw = stream.read_bytes()
                except Exception:
                    continue
                new_raw = raw
                page_hits = 0
                for uni_to_cid in maps:
                    new_raw, n = replace_with_map(new_raw, uni_to_cid)
                    page_hits += n
                if page_hits:
                    stream.write(new_raw)
                    replacements += page_hits
        pdf.save(path)
    return replacements


def main() -> int:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    if not DOC_DIR.exists():
        print(f"Missing directory: {DOC_DIR}")
        return 1

    priority = [
        "how-to-invite-6.pdf",
        "building-your-prospects-list-20.pdf",
        "the-professional-presentation-system-21.pdf",
        "the-follow-up-system-22.pdf",
        "helping-prospects-become-clients-23.pdf",
        "helping-clients-become-efg-associates-24.pdf",
        "helping-clients-become-gw-associates-24.pdf",
        "mastering-objection-handling-25.pdf",
        "goal-setting-for-success-19.pdf",
        "efgtrack-application-user-guide-26.pdf",
        "gwtrack-application-user-guide-26.pdf",
    ]

    targets: list[Path] = []
    seen: set[str] = set()
    for name in priority:
        path = DOC_DIR / name
        if path.exists() and name not in seen:
            targets.append(path)
            seen.add(name)
    for path in sorted(DOC_DIR.glob("*efg*.pdf")):
        if path.name not in seen:
            targets.append(path)
            seen.add(path.name)

    print(f"Processing {len(targets)} PDFs", flush=True)
    for path in targets:
        before = count_brand(path)
        print(f"\n{path.name}: efgtrack before={before}", flush=True)
        if before == 0:
            print("  skip (no mentions)", flush=True)
            continue
        n = rebrand_pdf(path)
        after = count_brand(path)
        print(f"  stream hits={n}, efgtrack after={after}", flush=True)

    for old_name, new_name in RENAME_FILES.items():
        old_path = DOC_DIR / old_name
        new_path = DOC_DIR / new_name
        if old_path.exists():
            if new_path.exists() and new_path != old_path:
                new_path.unlink()
            old_path.rename(new_path)
            print(f"Renamed {old_name} -> {new_name}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
