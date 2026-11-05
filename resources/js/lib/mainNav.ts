import { ChartColumn, House, LibraryBig, Search } from '@lucide/vue';
import { dashboard, search, stats } from '@/routes';
import { index as library } from '@/routes/library';
import type { NavItem } from '@/types';

/**
 * The four destinations a Reader moves between: the top bar on desktop, the bottom tabs on mobile.
 */
export const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: House },
    { title: 'Search', href: search(), icon: Search },
    { title: 'Library', href: library(), icon: LibraryBig },
    { title: 'Stats', href: stats(), icon: ChartColumn },
];
