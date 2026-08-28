<?php

declare(strict_types=1);

return [
    'accepted' => 'Полето :attribute трябва да бъде прието.',
    'confirmed' => 'Полето :attribute не съвпада с потвърждението.',
    'email' => 'Полето :attribute трябва да бъде валиден имейл адрес.',
    'max' => [
        'array' => 'Полето :attribute трябва да има най-много :max елемента.',
        'file' => 'Полето :attribute трябва да бъде най-много :max килобайта.',
        'numeric' => 'Полето :attribute трябва да бъде най-много :max.',
        'string' => 'Полето :attribute трябва да бъде най-много :max символа.',
    ],
    'min' => [
        'array' => 'Полето :attribute трябва да има поне :min елемента.',
        'file' => 'Полето :attribute трябва да бъде поне :min килобайта.',
        'numeric' => 'Полето :attribute трябва да бъде поне :min.',
        'string' => 'Полето :attribute трябва да бъде поне :min символа.',
    ],
    'required' => 'Полето :attribute е задължително.',
    'string' => 'Полето :attribute трябва да бъде текст.',
    'unique' => 'Стойността на полето :attribute вече е заета.',
    'uuid' => 'Полето :attribute трябва да бъде валиден UUID.',
    'regex' => 'Полето :attribute е в невалиден формат.',
    'in' => 'Избраната стойност за :attribute е невалидна.',
    'boolean' => 'Полето :attribute трябва да бъде да или не.',
    'integer' => 'Полето :attribute трябва да бъде цяло число.',
    'size' => [
        'string' => 'Полето :attribute трябва да бъде :size символа.',
    ],
    'attributes' => [
        'first_name' => 'име',
        'last_name' => 'фамилия',
        'email' => 'имейл',
        'password' => 'парола',
        'password_confirmation' => 'потвърждение на паролата',
        'phone' => 'телефон',
        'role' => 'роля',
        'is_active' => 'активен',
    ],
];
