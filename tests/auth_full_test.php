<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/eloquent.php';
require dirname(__DIR__) . '/api/app/Core/Autoloader.php';

use App\Exceptions\AuthException;
use App\Models\ApiToken;
use App\Models\DeviceLoginCode;
use App\Models\EmailVerificationToken;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\Auth\AdminBootstrapService;
use App\Services\Auth\DeviceLoginService;
use App\Services\Auth\DeviceService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\LoginAttemptService;
use App\Services\Auth\LoginService;
use App\Services\Auth\PasswordHasher;
use App\Services\Auth\PasswordResetService;
use App\Services\Auth\RegisterService;
use App\Services\Auth\TokenService;
use App\Services\Mail\MailerInterface;
use App\Services\Mail\MailService;
use App\Validation\ForgotPasswordValidator;
use App\Validation\LoginValidator;
use App\Validation\RegisterValidator;
use App\Validation\ResetPasswordValidator;
use Illuminate\Database\Capsule\Manager as DB;

final class AuthTestMailer implements MailerInterface
{
    /** @var list<array<string, mixed>> */
    public array $messages = [];

    public function send(string $to, string $subject, string $html, ?string $text = null): void
    {
        $this->messages[] = compact('to', 'subject', 'html', 'text');
    }

    public function sendTemplate(string $to, string $subject, string $template, array $data, ?string $text = null): void
    {
        $this->messages[] = compact('to', 'subject', 'template', 'data', 'text');
    }

    public function sendTemplateWithAttachments(string $to, string $subject, string $template, array $data, array $attachments, ?string $text = null): void
    {
        $this->messages[] = compact('to', 'subject', 'template', 'data', 'attachments', 'text');
    }

    public function last(string $template): array
    {
        foreach (array_reverse($this->messages) as $message) {
            if (($message['template'] ?? null) === $template) return $message;
        }

        throw new RuntimeException('Не е изпратен имейл шаблон: ' . $template);
    }
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$expectFailure = static function (callable $callback, string $message) use ($assert): Throwable {
    try { $callback(); } catch (Throwable $exception) { return $exception; }
    $assert(false, $message);
    throw new RuntimeException($message);
};

try {
    DB::connection()->transaction(function () use ($assert, $expectFailure): void {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.42';
        $_SERVER['HTTP_USER_AGENT'] = 'Auth full integration test';
        $mailer = new AuthTestMailer();
        $mailConfig = require dirname(__DIR__) . '/config/mail.php';
        $assert(is_string($mailConfig['dsn']) && parse_url($mailConfig['dsn'], PHP_URL_SCHEME) !== false, 'MAIL_DSN няма валидна схема.');
        $assert(filter_var((string) $mailConfig['from_address'], FILTER_VALIDATE_EMAIL) !== false, 'MAIL_FROM_ADDRESS не е валиден имейл.');
        new MailService((string) $mailConfig['dsn'], (string) $mailConfig['from_address'], (string) $mailConfig['from_name']);
        $password = 'TestPass123!';
        $email = 'auth-test-' . bin2hex(random_bytes(5)) . '@example.test';
        $device = '11111111-1111-4111-8111-' . bin2hex(random_bytes(6));
        $secondDevice = '22222222-2222-4222-8222-' . bin2hex(random_bytes(6));

        $registration = (new RegisterValidator())->validate([
            'first_name' => 'Auth', 'last_name' => 'Test', 'email' => $email,
            'password' => $password, 'password_confirmation' => $password,
            'device_uuid' => $device, 'device_name' => 'Тестово устройство',
        ]);
        $register = new RegisterService(
            new PasswordHasher(), new AdminBootstrapService(), new EmailVerificationService($mailer), new DeviceService()
        );
        $user = $register->register($registration);
        $assert($user->isCustomer() && !$user->hasVerifiedEmail(), 'Регистрацията не създава непотвърден клиент.');
        $verification = $mailer->last('verify-registration');
        $code = (string) ($verification['data']['code'] ?? '');
        $assert(strlen($code) === 6 && $verification['to'] === $email, 'Имейлът за потвърждение е некоректен.');
        $assert(EmailVerificationToken::query()->where('email', $email)->exists(), 'Кодът за потвърждение не е записан.');

        $login = new LoginService(new PasswordHasher(), new LoginAttemptService(), new DeviceService(), new DeviceLoginService($mailer), new TokenService(), new AdminBootstrapService());
        $beforeVerification = $expectFailure(static fn() => $login->login(['email'=>$email,'password'=>$password,'device_uuid'=>$device]), 'Входът преди потвърждение е разрешен.');
        $assert($beforeVerification instanceof AuthException, 'Грешката преди потвърждение не е AuthException.');
        (new EmailVerificationService($mailer))->verify($email, $code);
        $valid = (new LoginValidator())->validate(['email'=>$email,'password'=>$password,'device_uuid'=>$device]);
        $result = $login->login($valid);
        $assert($result->token !== null && !$result->requiresDeviceVerification, 'Входът от доверено устройство не издаде токен.');
        $expectFailure(static fn() => $login->login(['email'=>$email,'password'=>'WrongPass123!','device_uuid'=>$device]), 'Невалидна парола е приета.');

        $challenge = $login->login(['email'=>$email,'password'=>$password,'device_uuid'=>$secondDevice,'device_name'=>'Второ устройство']);
        $assert($challenge->requiresDeviceVerification && $challenge->token === null, 'Ново устройство не изисква код.');
        $deviceMessage = $mailer->last('device-login');
        $deviceCode = (string) ($deviceMessage['data']['code'] ?? '');
        $verified = $login->verifyDevice(['email'=>$email,'device_uuid'=>$secondDevice,'code'=>$deviceCode,'device_name'=>'Второ устройство']);
        $assert($verified->token !== null && !$verified->requiresDeviceVerification, 'Потвърждението на ново устройство не издаде токен.');
        $expectFailure(static fn() => $login->verifyDevice(['email'=>$email,'device_uuid'=>$secondDevice,'code'=>'000000']), 'Невалиден код за устройство е приет.');

        $passwordReset = new PasswordResetService(new PasswordHasher(), new TokenService(), $mailer);
        $passwordReset->sendResetLink($email);
        $resetMessage = $mailer->last('password-reset');
        $resetUrl = (string) ($resetMessage['data']['reset_url'] ?? '');
        parse_str((string) parse_url($resetUrl, PHP_URL_QUERY), $query);
        $resetToken = (string) ($query['token'] ?? '');
        $assert(strlen($resetToken) === 64 && PasswordResetToken::query()->where('email', $email)->exists(), 'Линкът за нулиране не е създаден.');
        $resetPayload = (new ResetPasswordValidator())->validate(['email'=>$email,'token'=>$resetToken,'password'=>'NewPass123!','password_confirmation'=>'NewPass123!']);
        $passwordReset->reset($resetPayload);
        $assert(!PasswordResetToken::query()->where('email', $email)->exists(), 'Използваният reset token не е изтрит.');
        $assert(ApiToken::query()->where('user_id',$user->id)->count() === 0, 'Старите токени не са оттеглени след смяна на парола.');
        $expectFailure(static fn() => $passwordReset->reset(['email'=>$email,'token'=>$resetToken,'password'=>'ThirdPass123!','password_confirmation'=>'ThirdPass123!']), 'Използван reset token е приет.');

        (new AdminBootstrapService())->ensureExists();
        $admin = User::query()->where('email', AdminBootstrapService::configuredEmail())->first();
        $assert($admin !== null, 'Администраторският профил не е наличен.');
        $adminResult = $login->login(['email'=>$admin->email,'password'=>(string)getenv('ADMIN_PASSWORD'),'device_uuid'=>$device], true);
        $assert($adminResult->token !== null || $adminResult->requiresDeviceVerification, 'Админ входът не връща очакван резултат.');

        throw new RuntimeException('AUTH_FULL_TEST_RESULT:' . json_encode(['status'=>'passed','registration'=>'passed','email_verification'=>'passed','login'=>'passed','device_verification'=>'passed','password_reset'=>'passed','admin_login'=>'passed'], JSON_UNESCAPED_UNICODE));
    });
} catch (Throwable $exception) {
    if (str_starts_with($exception->getMessage(), 'AUTH_FULL_TEST_RESULT:')) { echo substr($exception->getMessage(), strlen('AUTH_FULL_TEST_RESULT:')) . PHP_EOL; exit(0); }
    fwrite(STDERR, $exception->getMessage() . PHP_EOL); exit(1);
}
