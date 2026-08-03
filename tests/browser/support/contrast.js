const COLOR_PATTERN = /rgba?\([^)]*\)|#[\da-f]{3,8}/gi;

function parseColor(value) {
    if (!value || value === 'transparent') return { r: 0, g: 0, b: 0, alpha: 0 };

    const hex = value.match(/^#([\da-f]{3,8})$/i)?.[1];
    if (hex) {
        const expanded = hex.length <= 4 ? [...hex].map((channel) => channel + channel).join('') : hex;
        return {
            r: Number.parseInt(expanded.slice(0, 2), 16),
            g: Number.parseInt(expanded.slice(2, 4), 16),
            b: Number.parseInt(expanded.slice(4, 6), 16),
            alpha: expanded.length === 8 ? Number.parseInt(expanded.slice(6, 8), 16) / 255 : 1,
        };
    }

    const channels = value.match(/[\d.]+/g)?.map(Number) ?? [];
    if (channels.length < 3) return null;
    return {
        r: channels[0],
        g: channels[1],
        b: channels[2],
        alpha: channels.length > 3 ? channels[3] : 1,
    };
}

function composite(foreground, background) {
    const alpha = foreground.alpha + background.alpha * (1 - foreground.alpha);
    if (alpha === 0) return { r: 0, g: 0, b: 0, alpha: 0 };
    return {
        r: (foreground.r * foreground.alpha + background.r * background.alpha * (1 - foreground.alpha)) / alpha,
        g: (foreground.g * foreground.alpha + background.g * background.alpha * (1 - foreground.alpha)) / alpha,
        b: (foreground.b * foreground.alpha + background.b * background.alpha * (1 - foreground.alpha)) / alpha,
        alpha,
    };
}

function luminance(color) {
    return [color.r, color.g, color.b].map((channel) => {
        const normalized = channel / 255;
        return normalized <= 0.03928 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
    }).reduce((total, channel, index) => total + channel * [0.2126, 0.7152, 0.0722][index], 0);
}

export function contrastRatio(foreground, background) {
    const [light, dark] = [luminance(foreground), luminance(background)].sort((a, b) => b - a);
    return (light + 0.05) / (dark + 0.05);
}

function backgroundColors(style) {
    const imageColors = style.backgroundImage === 'none'
        ? []
        : (style.backgroundImage.match(COLOR_PATTERN) ?? []).map(parseColor).filter(Boolean);
    const color = parseColor(style.backgroundColor);
    return imageColors.length > 0 ? imageColors : (color && color.alpha > 0 ? [color] : []);
}

function applySurface(background, style) {
    const layers = backgroundColors(style);
    if (layers.length === 0) return [background];

    const opacity = Number.parseFloat(style.opacity);
    return layers.map((layer) => composite({ ...layer, alpha: layer.alpha * opacity }, background));
}

function ancestorBackgrounds(element) {
    const nodes = [];
    let current = element.parentElement;
    while (current) {
        nodes.unshift(current);
        current = current.parentElement;
    }

    let backgrounds = [{ r: 0, g: 0, b: 0, alpha: 0 }];
    for (const node of nodes) {
        const style = getComputedStyle(node);
        backgrounds = backgrounds.flatMap((background) => applySurface(background, style)).slice(0, 64);
    }

    return backgrounds.map((background) => background.alpha === 0
        ? { r: 255, g: 255, b: 255, alpha: 1 }
        : { ...background, alpha: 1 });
}

function visible(element) {
    const style = getComputedStyle(element);
    const box = element.getBoundingClientRect();
    return style.display !== 'none' && style.visibility !== 'hidden' && box.width > 0 && box.height > 0;
}

export async function assessContrast(page, selector) {
    return page.evaluate((candidateSelector) => {
        const parse = (value) => {
            if (!value || value === 'transparent') return { r: 0, g: 0, b: 0, alpha: 0 };
            const hex = value.match(/^#([\da-f]{3,8})$/i)?.[1];
            if (hex) {
                const expanded = hex.length <= 4 ? [...hex].map((channel) => channel + channel).join('') : hex;
                return {
                    r: Number.parseInt(expanded.slice(0, 2), 16),
                    g: Number.parseInt(expanded.slice(2, 4), 16),
                    b: Number.parseInt(expanded.slice(4, 6), 16),
                    alpha: expanded.length === 8 ? Number.parseInt(expanded.slice(6, 8), 16) / 255 : 1,
                };
            }
            const channels = value.match(/[\d.]+/g)?.map(Number) ?? [];
            return channels.length >= 3
                ? { r: channels[0], g: channels[1], b: channels[2], alpha: channels.length > 3 ? channels[3] : 1 }
                : null;
        };
        const blend = (foreground, background) => {
            const alpha = foreground.alpha + background.alpha * (1 - foreground.alpha);
            if (alpha === 0) return { r: 0, g: 0, b: 0, alpha: 0 };
            return {
                r: (foreground.r * foreground.alpha + background.r * background.alpha * (1 - foreground.alpha)) / alpha,
                g: (foreground.g * foreground.alpha + background.g * background.alpha * (1 - foreground.alpha)) / alpha,
                b: (foreground.b * foreground.alpha + background.b * background.alpha * (1 - foreground.alpha)) / alpha,
                alpha,
            };
        };
        const luminanceOf = (color) => [color.r, color.g, color.b].map((channel) => {
            const normalized = channel / 255;
            return normalized <= 0.03928 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
        }).reduce((total, channel, index) => total + channel * [0.2126, 0.7152, 0.0722][index], 0);
        const ratio = (foreground, background) => {
            const [light, dark] = [luminanceOf(foreground), luminanceOf(background)].sort((a, b) => b - a);
            return (light + 0.05) / (dark + 0.05);
        };
        const colorsIn = (value) => (value === 'none' ? [] : (value.match(/rgba?\([^)]*\)|#[\da-f]{3,8}/gi) ?? []).map(parse).filter(Boolean));
        const backgroundsFor = (element) => {
            const nodes = [];
            let current = element.parentElement;
            while (current) {
                nodes.unshift(current);
                current = current.parentElement;
            }
            let backgrounds = [{ r: 0, g: 0, b: 0, alpha: 0 }];
            for (const node of nodes) {
                const style = getComputedStyle(node);
                const imageLayers = colorsIn(style.backgroundImage);
                const layers = imageLayers.length > 0 ? imageLayers : [parse(style.backgroundColor)].filter((color) => color?.alpha > 0);
                if (layers.length === 0) continue;
                const opacity = Number.parseFloat(style.opacity);
                backgrounds = backgrounds.flatMap((background) => layers.map((layer) => blend({ ...layer, alpha: layer.alpha * opacity }, background))).slice(0, 64);
            }
            return backgrounds.map((background) => background.alpha === 0 ? { r: 255, g: 255, b: 255, alpha: 1 } : { ...background, alpha: 1 });
        };
        const visible = (element) => {
            const style = getComputedStyle(element);
            const box = element.getBoundingClientRect();
            return style.display !== 'none' && style.visibility !== 'hidden' && box.width > 0 && box.height > 0;
        };
        const ownBackgroundsFor = (element, parentBackgrounds) => {
            const style = getComputedStyle(element);
            const imageLayers = colorsIn(style.backgroundImage);
            const layers = imageLayers.length > 0 ? imageLayers : [parse(style.backgroundColor)].filter(Boolean);
            return (layers.length > 0 ? layers : [{ r: 0, g: 0, b: 0, alpha: 0 }]).flatMap((layer) => parentBackgrounds.map((parent) => ({
                local: blend(layer, parent),
                parent,
            })));
        };
        return [...document.querySelectorAll(candidateSelector)]
            .filter(visible)
            .filter((element) => !element.matches(':disabled, [aria-disabled="true"]'))
            .filter((element) => element.textContent?.trim() || element.getAttribute('aria-label') || element.value)
            .map((element) => {
                const style = getComputedStyle(element);
                const foreground = parse(style.color);
                const parentBackgrounds = backgroundsFor(element);
                const elementBackgrounds = ownBackgroundsFor(element, parentBackgrounds);
                const ratios = elementBackgrounds.map(({ local, parent }) => {
                    const opacity = Number.parseFloat(style.opacity);
                    const foregroundOnLocal = blend(foreground, local);
                    const renderedForeground = blend({ ...foregroundOnLocal, alpha: foregroundOnLocal.alpha * opacity }, parent);
                    const renderedBackground = blend({ ...local, alpha: local.alpha * opacity }, parent);
                    return {
                        foreground: renderedForeground,
                        background: renderedBackground,
                        ratio: ratio(renderedForeground, renderedBackground),
                    };
                });
                const bestEvidence = ratios.reduce((lowest, current) => current.ratio < lowest.ratio ? current : lowest);
                return {
                    label: element.getAttribute('aria-label') || element.textContent?.trim().slice(0, 60) || element.tagName,
                    tag: element.tagName.toLowerCase(),
                    className: typeof element.className === 'string' ? element.className : '',
                    rect: (() => { const box = element.getBoundingClientRect(); return { width: box.width, height: box.height }; })(),
                    ratio: bestEvidence?.ratio ?? 0,
                    foreground: bestEvidence?.foreground ?? foreground,
                    background: bestEvidence?.background ?? parentBackgrounds[0],
                    computedForeground: style.color,
                    computedBackground: style.backgroundColor,
                    computedBackgroundImage: style.backgroundImage,
                };
            });
    }, selector);
}
