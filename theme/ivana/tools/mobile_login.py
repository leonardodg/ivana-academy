#!/usr/bin/env python3
# Gera pix/loginbg-mobile.jpg (retrato 828x1472) a partir da imagem de login do desktop,
# levando os cantos (velas no topo a esquerda, macrame embaixo a direita) para o formato do celular.
# Uso: python3 theme/ivana/tools/mobile_login.py PageLogin.png theme/ivana/pix/loginbg-mobile.jpg
# As coordenadas dos recortes sao desta imagem (1472x832); outra imagem pede outros recortes.
import sys
from PIL import Image, ImageFilter, ImageDraw, ImageStat

src, out = sys.argv[1], sys.argv[2]
im = Image.open(src).convert('RGB')
W, H = 828, 1472  # retrato (~9:16)

# Cor do fundo: media de uma area so de creme (centro da imagem).
bg = tuple(int(c) for c in ImageStat.Stat(im.crop((600, 380, 900, 460))).mean)
canvas = Image.new('RGB', (W, H), bg)

def feather(size, edges, px):
    """Mascara com degrade nas bordas indicadas (r=direita, b=baixo, l=esquerda, t=topo)."""
    w, h = size
    m = Image.new('L', size, 255)
    d = ImageDraw.Draw(m)
    for i in range(px):
        v = int(255 * i / px)
        if 'r' in edges: d.line([(w - 1 - i, 0), (w - 1 - i, h)], fill=v)
        if 'b' in edges: d.line([(0, h - 1 - i), (w, h - 1 - i)], fill=v)
        if 'l' in edges: d.line([(i, 0), (i, h)], fill=v)
        if 't' in edges: d.line([(0, i), (w, i)], fill=v)
    return m

scale = 1.32
# Canto superior esquerdo: velas + circulo rosa.
tl = im.crop((0, 0, 480, 360))
tl = tl.resize((int(tl.width * scale), int(tl.height * scale)), Image.LANCZOS)
canvas.paste(tl, (0, 0), feather(tl.size, 'rb', 60))
# Canto inferior direito: macrame + circulo rosa.
br = im.crop((1010, 480, 1472, 832))
br = br.resize((int(br.width * scale), int(br.height * scale)), Image.LANCZOS)
canvas.paste(br, (W - br.width, H - br.height), feather(br.size, 'lt', 60))

canvas.save(out, 'JPEG', quality=86, optimize=True, progressive=True)
print(out, canvas.size, 'bg', bg)
