// Примитивы раскладки: единственный способ задать расстояние между соседями — gap из шкалы.
import type { ElementType, ReactNode } from 'react';
import cx from 'clsx';

type Gap = 1 | 2 | 3 | 4 | 6 | 8 | 12 | 16;

interface LayoutProps { as?: ElementType; gap?: Gap; children: ReactNode; className?: string }

export const Stack = ({ as: Tag = 'div', gap = 4, children, className }: LayoutProps) =>
  <Tag className={cx('vf-stack', `vf-gap-${gap}`, className)}>{children}</Tag>;

export const Cluster = ({ as: Tag = 'div', gap = 2, children, className }: LayoutProps) =>
  <Tag className={cx('vf-cluster', `vf-gap-${gap}`, className)}>{children}</Tag>;

export const Grid = ({ as: Tag = 'div', gap = 6, min = 'md', children, className }: LayoutProps & { min?: 'sm' | 'md' | 'lg' }) =>
  <Tag className={cx('vf-grid', min !== 'md' && `vf-grid--${min}`, `vf-gap-${gap}`, className)}>{children}</Tag>;

export const Container = ({ as: Tag = 'div', children }: Omit<LayoutProps, 'gap'>) =>
  <Tag className="vf-container">{children}</Tag>;
