<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
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
        return $order->delivery_method === 'office' ? 'До офис на куриер' : 'До личен адрес';
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, ',', ' ') . ' €';
    }
}
