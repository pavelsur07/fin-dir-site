// Логотип генерируется по стороне знака S (формулы — в README, раздел «Логотип»).
// Цвет версии берётся из темы: внутри [data-theme="dark"] знак crimson, слово белое.
import cx from 'clsx';

type LogoSize = 24 | 28 | 32 | 40 | 64;

export const Logo = ({ size = 32, signOnly = false, href = '/' }: { size?: LogoSize; signOnly?: boolean; href?: string }) => (
  <a href={href} className={cx('vf-logo', size !== 32 && `vf-logo--${size}`)} aria-label="Ваш Финдир — на главную">
    <span className="vf-logo__sign" aria-hidden="true">ВФ</span>
    {!signOnly && <span className="vf-logo__word" aria-hidden="true">Ваш Финдир</span>}
  </a>
);
