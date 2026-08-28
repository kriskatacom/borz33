import { Link } from 'react-router-dom';
import { routes } from '@/app/constants';

export function ForgotPasswordPage() {
  return (
    <main className="auth-card">
      <p className="eyebrow">Админ панел</p>
      <h1>Забравена парола</h1>
      <p className="muted">Възстановяването на парола ще бъде добавено тук.</p>
      <p>
        <Link to={routes.login}>Към входа</Link>
      </p>
    </main>
  );
}
