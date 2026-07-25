from __future__ import annotations

from pathlib import Path

from PIL import Image


ROOT = Path(__file__).resolve().parents[1]
BRAND = ROOT / "public" / "assets" / "brand"
SOURCE = BRAND / "hsj-bullterrier-mascot-sprites.png"
OUTPUT = BRAND / "hsj-bullterrier-error.gif"

GRID_COLUMNS = 3
GRID_ROWS = 2
FRAME_SIZE = (320, 320)
FRAME_DURATIONS_MS = [650, 150, 150, 220, 160, 520]


def gif_frame(image: Image.Image) -> Image.Image:
    rgba = image.convert("RGBA").resize(FRAME_SIZE, Image.Resampling.LANCZOS)
    alpha = rgba.getchannel("A")
    palette = rgba.quantize(colors=255, method=Image.Quantize.FASTOCTREE, dither=Image.Dither.NONE)

    pixels = bytearray(palette.tobytes())
    alpha_pixels = alpha.tobytes()
    for index, opacity in enumerate(alpha_pixels):
        if opacity < 96:
            pixels[index] = 255

    palette.frombytes(bytes(pixels))
    palette.info["transparency"] = 255
    palette.info["disposal"] = 2
    return palette


def main() -> None:
    sheet = Image.open(SOURCE).convert("RGBA")
    cell_width = sheet.width // GRID_COLUMNS
    cell_height = sheet.height // GRID_ROWS
    frames: list[Image.Image] = []

    for row in range(GRID_ROWS):
        for column in range(GRID_COLUMNS):
            cell = sheet.crop((
                column * cell_width,
                row * cell_height,
                (column + 1) * cell_width,
                (row + 1) * cell_height,
            ))
            frames.append(gif_frame(cell))

    frames[0].save(
        OUTPUT,
        format="GIF",
        save_all=True,
        append_images=frames[1:],
        duration=FRAME_DURATIONS_MS,
        loop=0,
        disposal=2,
        transparency=255,
        optimize=True,
    )

    result = Image.open(OUTPUT)
    assert result.size == FRAME_SIZE
    assert result.n_frames == 6
    assert result.info.get("loop") == 0
    assert "transparency" in result.info
    print(f"{OUTPUT} ({OUTPUT.stat().st_size} bytes, {result.n_frames} frames)")


if __name__ == "__main__":
    main()
