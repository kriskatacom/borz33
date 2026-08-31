<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
use App\Resources\OrderResource;
use App\Services\Mail\MailerInterface;
use App\Services\Mail\MailService;

class OrderNotificationService
{
    /** @var array<string, string> */
    private array $company;

    private string $adminEmail;

    public function __construct(
        private readonly MailerInterface $mailer = new MailService()
    ) {
        $this->company = require dirname(__DIR__, 4) . '/config/company.php';
        $mail = require dirname(__DIR__, 4) . '/config/mail.php';
        $this->adminEmail = trim((string) ($mail['order_admin_address'] ?? ''));
    }

    /** @return array{customer: bool, admin: bool} */
    public function send(Order $order): array
    {
        $order->loadMissing('items');
        $data = [
            'order' => $order,
            'title' => 'Поръчка ' . $order->number,
            'preheader' => 'Получихме Вашата поръчка ' . $order->number . '.',
        ];

        $customerSent = $this->attempt(
            (string) $order->email,
            'Получихме поръчката Ви · ' . $order->number,
            'order-confirmation',
            $data,
            $this->customerText($order)
        );

        $adminEmail = $this->adminEmail;
        $adminSent = false;

        if ($adminEmail !== '') {
            $adminSent = $this->attempt(
                $adminEmail,
                'Нова поръчка · ' . $order->number,
                'order-admin',
                [
                    ...$data,
                    'title' => 'Нова поръчка ' . $order->number,
                    'preheader' => 'Нова поръчка от ' . trim($order->first_name . ' ' . $order->last_name) . '.',
                ],
                $this->adminText($order)
            );
        }

        return ['customer' => $customerSent, 'admin' => $adminSent];
    }

    public function sendStatusChanged(Order $order, string $status): bool
    {
        $messages = [
            'pending' => ['Поръчката Ви очаква обработка', 'Поръчката отново е отбелязана като нова и очаква обработка от нашия екип.', 'Ще Ви уведомим при следваща промяна.'],
            'confirmed' => ['Поръчката Ви е потвърдена', 'Потвърдихме поръчката и данните за нейното изпълнение.', 'Скоро ще започнем подготовката на продуктите.'],
            'processing' => ['Подготвяме поръчката Ви', 'Вашата поръчка вече се обработва и продуктите се подготвят за изпращане.', 'Ще получите ново съобщение, когато пратката бъде предадена на куриер.'],
            'shipped' => ['Поръчката Ви е изпратена', 'Предадохме поръчката Ви за доставка.', 'Куриерът ще се свърже с Вас според избрания начин на доставка.'],
            'delivered' => ['Поръчката Ви е доставена', 'Поръчката е отбелязана като успешно доставена.', 'Благодарим Ви, че избрахте ' . (string) ($this->company['name'] ?? 'Borz33') . '.'],
            'cancelled' => ['Поръчката Ви е отказана', 'Поръчката е отбелязана като отказана и няма да бъде изпълнена.', 'Ако смятате, че това е грешка, моля свържете се с нас.'],
        ];

        if (!isset($messages[$status])) return false;
        [$statusTitle, $statusMessage, $statusNote] = $messages[$status];

        return $this->attempt(
            (string) $order->email,
            $statusTitle . ' · ' . $order->number,
            'order-status',
            [
                'order' => $order,
                'title' => $statusTitle,
                'preheader' => $statusMessage,
                'statusTitle' => $statusTitle,
                'statusMessage' => $statusMessage,
                'statusNote' => $statusNote,
                'status' => $status,
                'trackingUrl' => OrderResource::trackingUrl((string) ($order->tracking_number ?? '')),
            ],
            implode("\n", [
                'Здравейте, ' . $order->first_name . ',',
                '',
                $statusTitle,
                $statusMessage,
                $statusNote,
                '',
                'Поръчка: ' . $order->number,
                'Обща стойност: ' . $this->money($order->total),
                $order->tracking_number ? 'Товарителница: ' . $order->tracking_number : '',
                $order->tracking_number ? 'Проследяване: ' . OrderResource::trackingUrl((string) $order->tracking_number) : '',
                '',
                (string) ($this->company['name'] ?? 'Borz33'),
            ])
        );
    }

    /** @param array<string, mixed> $data */
    private function attempt(string $to, string $subject, string $template, array $data, string $text): bool
    {
        try {
            $this->mailer->sendTemplate($to, $subject, $template, $data, $text);

            return true;
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Order email failed [order=%s, template=%s]: %s',
                (string) ($data['order']->number ?? 'unknown'),
                $template,
                $exception->getMessage()
            ));

            return false;
        }
    }

    private function customerText(Order $order): string
    {
        $lines = [
            'Здравейте, ' . $order->first_name . ',',
            '',
            'Получихме Вашата поръчка ' . $order->number . '.',
            '',
            ...$this->itemText($order),
            '',
            'Общо за продуктите: ' . $this->money($order->total),
            'Доставка: уточнява се при обработка',
            'Плащане: ' . $this->paymentLabel($order),
            '',
            'Доставка: ' . $this->deliveryLabel($order),
            $this->address($order),
            '',
            'Ще се свържем с Вас при обработката.',
            '',
            (string) ($this->company['name'] ?? 'Borz33'),
        ];

        return implode("\n", $lines);
    }

    private function adminText(Order $order): string
    {
        return implode("\n", [
            'Нова поръчка ' . $order->number,
            '',
            'Клиент: ' . trim($order->first_name . ' ' . $order->last_name),
            'Имейл: ' . $order->email,
            'Телефон: ' . $order->phone,
            '',
            ...$this->itemText($order),
            '',
            'Общо за продуктите: ' . $this->money($order->total),
            'Плащане: ' . $this->paymentLabel($order),
            'Доставка: ' . $this->deliveryLabel($order),
            'Адрес: ' . $this->address($order),
            $order->notes ? 'Бележка: ' . $order->notes : '',
        ]);
    }

    /** @return list<string> */
    private function itemText(Order $order): array
    {
        $lines = ['Продукти:'];

        foreach ($order->items as $item) {
            $description = $item->qty . ' × ' . $item->name;

            if ($item->options) {
                $description .= ' (' . $item->options . ')';
            }

            $lines[] = '- ' . $description . ' — ' . $this->money($item->total);
        }

        return $lines;
    }

    private function address(Order $order): string
    {
        return implode(', ', array_filter([
            (string) $order->address_line,
            (string) $order->city,
            (string) ($order->postal_code ?? ''),
            (string) $order->country,
        ]));
    }

    private function paymentLabel(Order $order): string
    {
        return $order->payment_method === 'bank_transfer' ? 'Банков превод' : 'Наложен платеж';
    }

    private function deliveryLabel(Order $order): string
    {
        return match ($order->delivery_method) { 'office' => 'До офис на куриер', 'machine' => 'До Еконтомат', default => 'До личен адрес' };
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, ',', ' ') . ' €';
    }
}
