# -*- coding: utf-8 -*-
"""Генерация логотипов «Ваш Финдир» по спецификации §2 (Вариант Б).

Два файла на прозрачном фоне:
  logo_dark_bg.png  — для тёмного фона: знак #B00020, литеры и слово белые
  logo_light_bg.png — для светлого фона: знак #0B1020, литеры «ВФ» белые,
                      словесная часть #0B1020
"""
from PIL import Image, ImageDraw, ImageFont

FONT_PATH = r"C:\Users\pavel\OneDrive\Документы\Kimi\Workspaces\Дизайн\logo\Manrope.ttf"
OUT_DARK = r"C:\Users\pavel\OneDrive\Документы\Kimi\Workspaces\Дизайн\logo\logo_dark_bg.png"
OUT_LIGHT = r"C:\Users\pavel\OneDrive\Документы\Kimi\Workspaces\Дизайн\logo\logo_light_bg.png"

SS = 4  # суперсэмплинг для гладких краёв

# Базовые пропорции от размера знака S (в финальном PNG)
S = 152                      # сторона квадрата (38px @4x)
R = round(S * 8 / 38)        # скругление 8px @38
VF_SIZE = round(S * 14 / 38)     # литеры «ВФ»
WM_SIZE = round(S * 18 / 38)     # словесная часть
GAP = round(S * 12 / 38)         # зазор между знаком и текстом


def manrope(size):
    f = ImageFont.truetype(FONT_PATH, size)
    f.set_variation_by_axes([800])
    return f


def make_logo(sign_color, vf_color, wm_color, out_path):
    vf_font = manrope(VF_SIZE * SS)
    wm_font = manrope(WM_SIZE * SS)

    # Размеры текстов
    vf_bbox = vf_font.getbbox("ВФ")
    vf_w, vf_h = vf_bbox[2] - vf_bbox[0], vf_bbox[3] - vf_bbox[1]
    wm_bbox = wm_font.getbbox("Ваш Финдир")
    wm_w, wm_h = wm_bbox[2] - wm_bbox[0], wm_bbox[3] - wm_bbox[1]

    # Охранное поле = высота литер «ВФ»
    margin = vf_h // SS + round(S * 0.06)

    W = S + GAP + (wm_w // SS) + 2 * margin
    H = S + 2 * margin

    img = Image.new("RGBA", (W * SS, H * SS), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)

    # Знак: квадрат со скруглением
    d.rounded_rectangle(
        [margin * SS, margin * SS, (margin + S) * SS, (margin + S) * SS],
        radius=R * SS, fill=sign_color,
    )

    # Литеры «ВФ» — по центру знака
    cx = margin * SS + (S * SS) // 2
    cy = margin * SS + (S * SS) // 2
    d.text(
        (cx - vf_w // 2 - vf_bbox[0], cy - vf_h // 2 - vf_bbox[1]),
        "ВФ", font=vf_font, fill=vf_color,
    )

    # Словесная часть — по центру знака по вертикали
    wm_x = (margin + S + GAP) * SS
    d.text(
        (wm_x - wm_bbox[0], cy - wm_h // 2 - wm_bbox[1]),
        "Ваш Финдир", font=wm_font, fill=wm_color,
    )

    img = img.resize((W, H), Image.LANCZOS)
    img.save(out_path)
    print("saved:", out_path, img.size)


make_logo("#B00020", "#FFFFFF", "#FFFFFF", OUT_DARK)   # тёмный фон: знак бордовый, всё белое
make_logo("#0B1020", "#FFFFFF", "#0B1020", OUT_LIGHT)  # светлый фон: знак тёмный, «ВФ» белые, слово тёмное
