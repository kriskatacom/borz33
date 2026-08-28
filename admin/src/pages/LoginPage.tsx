import { Link } from 'react-router-dom';
import { routes } from '@/app/constants';

export function LoginPage() {
  return (
    <main className="auth-card">
      <p className="eyebrow">Админ панел</p>
      <h1>Вход</h1>
      <p className="muted">Формата за вход ще бъде добавена тук. Регистрация в админ панела няма.</p>
      <p>
        <Link to={routes.forgotPassword}>Забравена парола</Link>
      </p>
    </main>
  );
}
