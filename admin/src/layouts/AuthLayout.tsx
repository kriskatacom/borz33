import type { ReactNode } from 'react';

type AuthLayoutProps = {
  eyebrow: string;
  title: string;
  children: ReactNode;
  footer?: ReactNode;
};

export function AuthLayout({ eyebrow, title, children, footer }: AuthLayoutProps) {
  return (
    <div className="auth-screen">
      <aside className="auth-brand" aria-hidden="true">
        <p className="eyebrow">Borz33</p>
        <h2>Админ панел за ежедневната работа с магазина.</h2>
        <p>Вход само за екипа. Регистрация тук няма.</p>
      </aside>
      <main className="auth-card">
        <p className="eyebrow">{eyebrow}</p>
        <h1>{title}</h1>
        {children}
        {footer ? <div className="auth-footer">{footer}</div> : null}
      </main>
    </div>
  );
}
