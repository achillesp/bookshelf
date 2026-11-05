import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

/**
 * Focuses an element without the browser's instant jump. Only when the element
 * is off-screen (inside `reveal`'s scroll margins, which leave room for sticky
 * bars) does the page scroll just enough to show `reveal`: smoothly, or at once
 * when the Reader prefers reduced motion.
 */
export function focusAndReveal(element: HTMLElement, reveal: Element): void {
    element.focus({ preventScroll: true });

    const margins = getComputedStyle(reveal);
    const { top, bottom } = element.getBoundingClientRect();
    const isVisible =
        top >= parseFloat(margins.scrollMarginTop) &&
        bottom <= window.innerHeight - parseFloat(margins.scrollMarginBottom);

    if (isVisible) {
        return;
    }

    reveal.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? 'auto'
            : 'smooth',
        block: 'nearest',
    });
}
