// Эталон компонента @vashfindir/ui. Стили — только в Button.module.css через var(--vf-*).
import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react';
import cx from 'clsx';
import s from './Button.module.css';

type Variant = 'primary' | 'secondary' | 'ghost';
type Size = 'lg' | 'md' | 'sm';

export interface ButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'style' | 'className'> {
  variant?: Variant;      // primary — одна на экран; secondary — вторичное действие; ghost — в тулбарах
  size?: Size;            // lg 48 (формы, CTA) · md 40 (тулбары, таблицы) · sm 32 (плотные списки)
  loading?: boolean;      // блокирует повторный клик, сохраняет ширину (защита от двойного платежа)
  block?: boolean;        // на всю ширину — мобильные формы
  iconStart?: ReactNode;
  iconEnd?: ReactNode;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  { variant = 'primary', size = 'lg', loading = false, block = false, iconStart, iconEnd, children, disabled, type = 'button', ...rest },
  ref
) {
  return (
    <button
      ref={ref}
      type={type}
      className={cx(s.btn, s[variant], s[size], block && s.block)}
      disabled={disabled}
      aria-busy={loading || undefined}
      data-loading={loading || undefined}
      {...rest}
    >
      {loading ? <span className={s.spinner} aria-hidden="true" /> : iconStart}
      <span className={s.label}>{children}</span>
      {!loading && iconEnd}
    </button>
  );
});
