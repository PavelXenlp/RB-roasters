<?php
$asset = static function (string $path): string {
    $path = ltrim($path, '/');

    if (function_exists('get_template_directory_uri')) {
        return get_template_directory_uri() . '/assets/' . $path;
    }

    return '/assets/' . $path;
};

if (!function_exists('route_url')) {
    function route_url(string $page = 'home', string $fragment = ''): string
    {
        $routes = [
            'home' => '/',
            'catalog' => '/catalog/',
            'product' => '/catalog/mexico-chiapas/',
            'loyalty' => '/loyalty/',
            'delivery' => '/delivery/',
            'news' => '/news/',
            'contacts' => '/contacts/',
            'cart' => '/cart/',
            'account' => '/account/',
            'business' => '/business/',
            'business-account' => '/business-account/',
        ];

        $path = $routes[$page] ?? '/';

        if (function_exists('home_url')) {
            return home_url($path) . $fragment;
        }

        return $path . $fragment;
    }
}

$contacts = [
    'phone' => '+7 (919) 700-33-11',
    'phone_href' => '+79197003311',
    'address' => 'Пермь, ул. Деревообделочная, 8к6',
    'vk' => 'https://vk.com/rb_roasters',
    'tg' => 'https://t.me/rb_roasters',
    'manager' => 'https://t.me/rbr_zakaz',
    'trainer' => 'https://t.me/Valeryaboldt',
    'map' => 'https://yandex.ru/maps/-/CPecyDp5',
];

$menu = [
    ['Главная', route_url('home')],
    ['О нас', route_url('home', '#about')],
    ['Каталог', route_url('catalog')],
    ['Программа лояльности', route_url('loyalty')],
    ['Доставка и оплата', route_url('delivery')],
    ['Кофе для бизнеса', route_url('business')],
    ['Новости и акции', route_url('news')],
    ['Контакты', route_url('contacts')],
];

$heroCards = [
    ['title' => 'Каталог кофе для дома', 'href' => route_url('catalog'), 'image' => $asset('img/1.webp')],
    ['title' => 'Кофе для бизнеса', 'href' => route_url('business'), 'image' => $asset('img/2.webp')],
    ['title' => 'Обучение. Курсы бариста', 'href' => route_url('business', '#training'), 'image' => $asset('img/3_1.webp')],
    ['title' => 'Сервис кофейного оборудования', 'href' => route_url('business', '#service'), 'image' => $asset('img/4.webp')],
];

$products = [
    [
        'title' => 'Mexico Chiapas',
        'description' => 'Цветы, лимон, карамель, орехи',
        'process' => 'Мытый',
        'roast' => 'Средняя',
        'country' => 'Мексика',
        'region' => 'Хельтенанго',
        'height' => '2000 м',
        'variety' => 'Местные разновидности',
        'image' => $asset('img/1.webp'),
        'old_200' => '500 ₽',
        'price_200' => '485 ₽',
        'old_1000' => '1000 ₽',
        'price_1000' => '970 ₽',
    ],
    [
        'title' => 'Ethiopia Yirgacheffe',
        'description' => 'Бергамот, черный чай, жасмин',
        'process' => 'Мытый',
        'roast' => 'Светлая',
        'country' => 'Эфиопия',
        'region' => 'Иргачеффе',
        'height' => '1900 м',
        'variety' => 'Heirloom',
        'image' => $asset('img/IMG_8068.jpg'),
        'old_200' => '620 ₽',
        'price_200' => '601 ₽',
        'old_1000' => '2350 ₽',
        'price_1000' => '2279 ₽',
    ],
    [
        'title' => 'Colombia Inza',
        'description' => 'Карамбола, лимон, слива',
        'process' => 'Натуральный',
        'roast' => 'Средняя',
        'country' => 'Колумбия',
        'region' => 'Каука',
        'height' => '1800 м',
        'variety' => 'Катурра',
        'image' => $asset('img/IMG_1192_1.jpg'),
        'old_200' => '540 ₽',
        'price_200' => '524 ₽',
        'old_1000' => '1980 ₽',
        'price_1000' => '1921 ₽',
    ],
    [
        'title' => 'Brazil Cerrado',
        'description' => 'Шоколад, фундук, сухофрукты',
        'process' => 'Натуральный',
        'roast' => 'Средняя',
        'country' => 'Бразилия',
        'region' => 'Серрадо',
        'height' => '1100 м',
        'variety' => 'Мундо Ново',
        'image' => $asset('img/IMG_1561_1.jpg'),
        'old_200' => '430 ₽',
        'price_200' => '417 ₽',
        'old_1000' => '1590 ₽',
        'price_1000' => '1542 ₽',
    ],
    [
        'title' => 'Drip Coffee Set',
        'description' => 'Набор дрип-пакетов для поездок',
        'process' => 'Ассорти',
        'roast' => 'Разная',
        'country' => 'Смесь лотов',
        'region' => 'RB Roasters',
        'height' => 'разная',
        'variety' => 'разные',
        'image' => $asset('img/__3.png'),
        'old_200' => '690 ₽',
        'price_200' => '669 ₽',
        'old_1000' => '—',
        'price_1000' => '—',
    ],
];

$categories = ['Кофе под фильтр', 'Кофе под эспрессо', 'Микролоты', 'Дрип-пакеты', 'Кофе в капсулах', 'Аксессуары', 'Другое'];
$brewMethods = [
    ['title' => 'Эспрессо', 'icon' => 'espresso'],
    ['title' => 'Турка', 'icon' => 'cezve'],
    ['title' => 'Гейзерная кофеварка', 'icon' => 'moka'],
    ['title' => 'Воронка', 'icon' => 'dripper'],
    ['title' => 'Аэропресс', 'icon' => 'aeropress'],
];

$news = [
    [
        'title' => 'Мы вошли в десятку лучших обжарщиков России!',
        'image' => $asset('img/IMG_1159_1.jpg'),
        'date' => '17 апреля',
        'text' => 'Ещё в ноябре мы рассказывали, что вошли в топ-25 национальной премии «Обжарщик года». Сейчас новый этап: организаторы подвели итоги второго тура, и наше имя снова в списке среди десяти финалистов. Спасибо команде за работу и вам, наши партнёры и клиенты, за поддержку и доверие.',
    ],
    ['title' => 'Новый урожай под фильтр', 'image' => $asset('img/1.webp'), 'date' => 'май', 'text' => 'Готовим подборку свежих лотов для каталога.'],
    ['title' => 'Каппинг на производстве', 'image' => $asset('img/3_1.webp'), 'date' => 'май', 'text' => 'Открытые дегустации вернутся в расписание тренинг-центра.'],
    ['title' => 'Сервисная диагностика', 'image' => $asset('img/4.webp'), 'date' => 'май', 'text' => 'Плановое обслуживание кофейного оборудования для партнеров.'],
    ['title' => 'Дрип-пакеты в дорогу', 'image' => $asset('img/__3.png'), 'date' => 'май', 'text' => 'Удобный формат кофе для офиса, поездок и подарков.'],
];

$courses = [
    [
        'title' => 'Базовый курс бариста',
        'duration' => '2 дня по 5-6 часов',
        'price' => '12000 ₽ индивидуально / 15000 ₽ для двух человек',
        'image' => $asset('img/IMG_1352_1.jpg'),
        'items' => ['Путь кофе от зерна до чашки', 'Обработка и обжарка', 'Теория эспрессо и молока', 'Основы каппинга', 'Практика эспрессо, молока и латте-арта'],
    ],
    [
        'title' => 'Углубленный курс бариста',
        'duration' => '5 дней по 5-6 часов',
        'price' => '25000 ₽ индивидуально / 30000 ₽ для двух человек',
        'image' => $asset('img/3_1.webp'),
        'items' => ['Терруар, сбор, обработка и эффекты зерна', 'День эспрессо', 'День молока и базовый латте-арт', 'Альтернативные методы', 'Сенсорный анализ и каппинги'],
    ],
    [
        'title' => 'Альтернативные способы заваривания',
        'duration' => '3 часа',
        'price' => '3500 ₽ индивидуально / 4000 ₽ для двоих',
        'image' => $asset('img/IMG_1463_1.jpg'),
        'items' => ['Классическая воронка', 'Иммерсионная воронка', 'Аэропресс', 'Практика рецептов'],
    ],
    [
        'title' => 'Латте-арт',
        'duration' => '3-4 часа',
        'price' => '4000 ₽ индивидуально / 5500 ₽ два человека',
        'image' => $asset('img/IMG_0972_1.jpg'),
        'items' => ['Базовая теория', 'Отработка элементов', 'Постановка техники молока', 'Молоко включено в стоимость'],
    ],
];
?>
