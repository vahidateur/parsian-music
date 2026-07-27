# AI Scene System — Parsian Music Academy

> مرجع تمام Scene های Hero. هر تصویر AI از این فایل‌ها تولید می‌شود.
> هر مدل (GPT Image, Midjourney, Flux, ...) باید از همین مشخصات استفاده کند.
> یکدستی بصری کل سایت وابسته به این کتابخانه است.

## Pipeline

```
Scene Spec (.md) → AI Production → Manifest (.json) → Hero Integration
```

## ساختار مانیفست هر استاد

```json
{
  "background":       "background/{instrument}/{scene-name}.webp",
  "background_mobile":"background/{instrument}/{scene-name}-mobile.webp",
  "lighting":         "lighting/{style}.webp",
  "frame":            "frame/{frame-name}.webp",
  "portrait":         "portrait/{teacher-slug}.webp",
  "decorations":      "decorations/{style}.webp",
  "grading":          "grading/{style}.json"
}
```

## قوانین مشترک همه Sceneها

### ✅ Always
- Portrait safe area reserved (right 42% of composition)
- Left side has intentional negative space for portrait placement
- Warm cinematic atmosphere
- Deep shadows, rich darks
- Subtle fog or dust (very light)
- No harsh lights or overexposure

### ❌ Never
- No people (teacher portrait is separate layer)
- No text, logo, watermark
- No fantasy creatures or cartoon elements
- No modern objects in historical scenes
- No exaggerated magic or unrealistic glow
- No fisheye or extreme lens distortion
- No busy/cluttered composition

## Asset Dimensions

| Asset | Desktop | Mobile |
|-------|---------|--------|
| background | 1500 × 820px | 768 × 600px |
| lighting | 1500 × 820px | — |
| frame | 520 × 720px | 380 × 530px |
| portrait | 460 × 660px | 340 × 490px |
| decorations | 1500 × 820px | — |

## Scenes Index

| # | Scene | استاد/ساز |
|---|-------|-----------|
| 01 | Gothic Concert Hall | ویولن |
| 02 | Grand Piano Room | پیانو |
| 03 | Persian Mystical Chamber | دف |
| 04 | Modern Studio | گیتار الکتریک |
| 05 | Classical Music Room | گیتار کلاسیک |
