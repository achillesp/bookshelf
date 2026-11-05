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
 * Focuses an element without the browser's instant jump, then scrolls just
 * enough to show `reveal`: smoothly, or at once when the Reader prefers
 * reduced motion.
 */
export function focusAndReveal(
    element: HTMLElement,
    reveal: Element = element,
): void {
    element.focus({ preventScroll: true });
    reveal.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? 'auto'
            : 'smooth',
        block: 'nearest',
    });
}
