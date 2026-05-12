#!/usr/bin/env bash
set -euo pipefail

date_label="${1:-2026-05-13}"
out_dir="${2:-tmp/generated-media}"
out_file="$out_dir/wuxing-chuanyi-$date_label-cover.webp"
mkdir -p "$out_dir"
font_path="${YI_COVER_FONT:-/System/Library/Fonts/STHeiti Medium.ttc}"
font_args=()
if [[ -f "$font_path" ]]; then
  font_args=(-font "$font_path")
fi

if command -v magick >/dev/null 2>&1; then
  magick -size 1600x900 canvas:'#f8f6ef' \
    -fill '#e8f2ea' -draw 'roundrectangle 80,80 1520,820 34,34' \
    -fill '#d7eadb' -draw 'circle 300,260 450,260' \
    -fill '#bfe0c6' -draw 'circle 1290,650 1460,650' \
    -fill '#ffffff' -draw 'roundrectangle 210,180 760,720 26,26' \
    -fill '#f6f1df' -draw 'roundrectangle 840,180 1390,720 26,26' \
    -fill '#2f855a' -draw 'roundrectangle 285,260 685,365 18,18' \
    -fill '#0f766e' -draw 'roundrectangle 285,395 685,500 18,18' \
    -fill '#86c98b' -draw 'roundrectangle 285,530 685,635 18,18' \
    -fill '#111827' -draw 'roundrectangle 920,260 1170,365 18,18' \
    -fill '#2b6cb0' -draw 'roundrectangle 920,395 1325,500 18,18' \
    -fill '#1a365d' -draw 'roundrectangle 920,530 1250,635 18,18' \
    "${font_args[@]}" -fill '#1f2933' -pointsize 62 -gravity north -annotate +0+70 '五行穿衣颜色参考' \
    "${font_args[@]}" -fill '#4b5563' -pointsize 34 -gravity south -annotate +0+78 "$date_label  绿色 · 青色 · 浅绿" \
    -quality 92 "$out_file"
elif command -v convert >/dev/null 2>&1; then
  convert -size 1600x900 xc:'#f8f6ef' "$out_file"
else
  echo "缺少 ImageMagick magick/convert，无法生成封面图。" >&2
  exit 1
fi

printf '%s\n' "$out_file"
