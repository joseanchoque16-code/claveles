from PIL import Image, ImageOps
import numpy as np

# ---------- CONFIG ----------
inp = "logo.webp"

# Recorte (para tu imagen 1162x365 aprox)
CROP_BOX = (82, 0, 450, 365)  # (left, top, right, bottom)

# Elegí color:
# Blanco: "#FFFFFF"
# Esmeralda (ejemplo): "#00A86B"  (podés cambiarlo)
TARGET_HEX = "#E9E9E9"

# Umbrales para hacer transparente el fondo (ajustables)
T0 = 10    # distancia mínima (más alto = más fondo eliminado)
T1 = 70    # distancia donde ya es 100% opaco
# ---------------------------

def hex_to_rgb(h: str):
    h = h.strip().lstrip("#")
    return tuple(int(h[i:i+2], 16) for i in (0, 2, 4))

img = Image.open(inp).convert("RGBA")
icon = img.crop(CROP_BOX).convert("RGBA")

arr = np.array(icon).astype(np.int16)
rgb = arr[:, :, :3]

# 1) Estimar color de fondo usando el borde
border = np.concatenate([
    rgb[0, :, :],      # top
    rgb[-1, :, :],     # bottom
    rgb[:, 0, :],      # left
    rgb[:, -1, :]      # right
], axis=0)

bg = np.median(border, axis=0)  # (R,G,B) del fondo aprox

# 2) Crear alpha según distancia al fondo (fondo -> transparente)
dist = np.sqrt(((rgb - bg) ** 2).sum(axis=2)).astype(np.float32)
alpha = np.clip((dist - T0) / (T1 - T0), 0, 1) * 255
alpha = alpha.astype(np.uint8)

# 3) Recolorear todo al color objetivo, conservando alpha
tr, tg, tb = hex_to_rgb(TARGET_HEX)
out = np.zeros_like(arr, dtype=np.uint8)
out[:, :, 0] = tr
out[:, :, 1] = tg
out[:, :, 2] = tb
out[:, :, 3] = alpha

icon_clean = Image.fromarray(out, mode="RGBA")

# 4) Hacer cuadrado (para favicon) con transparencia
w, h = icon_clean.size
side = max(w, h)
pad = ((side - w)//2, (side - h)//2, side - w - (side - w)//2, side - h - (side - h)//2)
icon_sq = ImageOps.expand(icon_clean, border=pad, fill=(0, 0, 0, 0))

# 5) Exportar favicon.ico (multi-size)
sizes = [(16,16), (32,32), (48,48), (64,64), (128,128), (256,256)]
icon_sq.save("favicon.ico", format="ICO", sizes=sizes)

# Extra útil: PNG 32x32 (a veces ayuda a cache/browsers)
icon_sq.resize((32,32), Image.Resampling.LANCZOS).save("favicon-32.png")

print("Listo: favicon.ico y favicon-32.png")
