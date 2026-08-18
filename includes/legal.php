<?php
/**
 * Legal pages, requisites and consent evidence.
 *
 * @package ROASTBERRY_THEME
 */

function rb_legal_defaults(): array
{
    $contacts = function_exists('rb_contacts') ? rb_contacts() : [];

    return [
        'legal_name' => 'ИП Тюхтин Дмитрий Владимирович',
        'inn' => '590418682504',
        'ogrnip' => '316595800106225',
        'registration_date' => '31.05.2016',
        'tax_system' => 'УСН',
        'okved' => '46.37 — Торговля оптовая кофе, чаем, какао и пряностями',
        'registered_address' => '',
        'return_address' => 'Пермь, ул. Деревообделочная, 8к6',
        'email' => (string) get_option('admin_email'),
        'phone' => (string) ($contacts['phone'] ?? '+7 (919) 700-33-11'),
        'bank_name' => '',
        'bik' => '',
        'correspondent_account' => '',
        'settlement_account' => '',
        'order_processing_time' => '1–3 рабочих дня после подтверждения заказа или оплаты',
        'pickup_storage_time' => '7 календарных дней после уведомления о готовности',
        'policy_version' => '19.08.2026',
    ];
}

function rb_legal_settings(): array
{
    return wp_parse_args((array) get_option('rb_legal_settings', []), rb_legal_defaults());
}

function rb_sanitize_legal_settings(array $input): array
{
    $text_fields = ['legal_name', 'inn', 'ogrnip', 'registration_date', 'tax_system', 'okved', 'registered_address', 'return_address', 'phone', 'bank_name', 'bik', 'correspondent_account', 'settlement_account', 'order_processing_time', 'pickup_storage_time', 'policy_version'];
    $clean = [];
    foreach ($text_fields as $field) {
        $clean[$field] = sanitize_text_field((string) ($input[$field] ?? ''));
    }
    $clean['email'] = sanitize_email((string) ($input['email'] ?? ''));
    return $clean;
}

add_action('admin_init', 'rb_register_legal_settings');
function rb_register_legal_settings(): void
{
    register_setting('rb_legal_settings_group', 'rb_legal_settings', [
        'type' => 'array',
        'sanitize_callback' => 'rb_sanitize_legal_settings',
        'default' => [],
    ]);
}

add_action('admin_menu', 'rb_register_legal_admin_page', 30);
function rb_register_legal_admin_page(): void
{
    add_submenu_page('rb-roasters', 'Юридические данные', 'Юридические данные', 'manage_options', 'rb-legal', 'rb_render_legal_admin_page');
}

function rb_render_legal_admin_page(): void
{
    if (!current_user_can('manage_options')) return;
    $settings = rb_legal_settings();
    $fields = [
        'legal_name' => 'Полное наименование', 'inn' => 'ИНН', 'ogrnip' => 'ОГРНИП',
        'registration_date' => 'Дата регистрации', 'tax_system' => 'Система налогообложения',
        'okved' => 'Основной ОКВЭД', 'registered_address' => 'Адрес регистрации из ЕГРИП',
        'return_address' => 'Адрес для претензий и возвратов', 'email' => 'Юридически значимая почта',
        'phone' => 'Телефон', 'bank_name' => 'Банк', 'bik' => 'БИК',
        'correspondent_account' => 'Корреспондентский счет', 'settlement_account' => 'Расчетный счет',
        'order_processing_time' => 'Срок обработки заказа', 'pickup_storage_time' => 'Срок хранения самовывоза',
        'policy_version' => 'Дата/версия документов',
    ];
    ?>
    <div class="wrap">
        <h1>Юридические данные и условия продажи</h1>
        <?php if ($settings['registered_address'] === '' || $settings['bank_name'] === '' || $settings['settlement_account'] === ''): ?>
            <div class="notice notice-warning"><p><strong>Заполните данные по актуальной выписке ЕГРИП и банковским документам.</strong> Тема намеренно не публикует предположительные адрес и банковские реквизиты.</p></div>
        <?php endif; ?>
        <p>Данные автоматически используются на юридических страницах, в оферте и подвале. После изменения существенных условий обновите версию документов.</p>
        <form method="post" action="options.php">
            <?php settings_fields('rb_legal_settings_group'); ?>
            <table class="form-table" role="presentation">
                <?php foreach ($fields as $key => $label): ?>
                    <tr><th><label for="rb-legal-<?= esc_attr($key) ?>"><?= esc_html($label) ?></label></th><td><input class="regular-text" id="rb-legal-<?= esc_attr($key) ?>" name="rb_legal_settings[<?= esc_attr($key) ?>]" value="<?= esc_attr((string) $settings[$key]) ?>"<?= $key === 'email' ? ' type="email"' : '' ?>></td></tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button('Сохранить юридические данные'); ?>
        </form>
    </div>
    <?php
}

function rb_legal_page_definitions(): array
{
    return [
        'privacy' => ['Политика конфиденциальности', 'template-privacy.php'],
        'personal-data-consent' => ['Согласие на обработку персональных данных', 'template-personal-data-consent.php'],
        'requisites' => ['Реквизиты', 'template-requisites.php'],
        'public-offer' => ['Публичная оферта', 'template-public-offer.php'],
        'user-agreement' => ['Пользовательское соглашение', 'template-user-agreement.php'],
        'returns' => ['Возврат товара и денежных средств', 'template-returns.php'],
    ];
}

add_action('admin_init', 'rb_create_legal_pages_once', 20);
function rb_create_legal_pages_once(): void
{
    if (!current_user_can('manage_options') || get_option('rb_legal_pages_version') === '2') return;
    foreach (rb_legal_page_definitions() as $slug => [$title, $template]) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if (!$page) {
            $page_id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug]);
            if ($page_id && !is_wp_error($page_id)) update_post_meta($page_id, '_wp_page_template', $template);
        } elseif (in_array(get_page_template_slug($page->ID), ['', 'default'], true)) {
            update_post_meta($page->ID, '_wp_page_template', $template);
        }
    }
    update_option('rb_legal_pages_version', '2', false);
}

function rb_legal_link(string $route, string $label): string
{
    return '<a href="' . esc_url(route_url($route)) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>';
}

function rb_render_legal_consents(bool $require_offer = false): void
{
    ?>
    <div class="legal-consents">
        <label class="legal-consent"><input type="checkbox" name="rb_personal_data_consent" value="1" required><span>Даю <?= rb_legal_link('personal-data-consent', 'согласие на обработку персональных данных') ?>.</span></label>
        <label class="legal-consent"><input type="checkbox" name="rb_privacy_ack" value="1" required><span>Я ознакомлен(а) с <?= rb_legal_link('privacy', 'Политикой конфиденциальности') ?>.</span></label>
        <?php if ($require_offer): ?>
            <label class="legal-consent"><input type="checkbox" name="rb_public_offer_accept" value="1" required><span>Принимаю условия <?= rb_legal_link('public-offer', 'Публичной оферты') ?>.</span></label>
        <?php endif; ?>
    </div>
    <?php
}

function rb_validate_legal_consents(bool $require_offer = false): bool
{
    if (empty($_POST['rb_personal_data_consent']) || empty($_POST['rb_privacy_ack'])) return false;
    return !$require_offer || !empty($_POST['rb_public_offer_accept']);
}

function rb_legal_acceptance_data(string $context): array
{
    $settings = rb_legal_settings();
    return [
        'context' => sanitize_key($context),
        'accepted_at_utc' => gmdate('c'),
        'document_version' => (string) $settings['policy_version'],
        'privacy_url' => route_url('privacy'),
        'consent_url' => route_url('personal-data-consent'),
        'offer_url' => route_url('public-offer'),
        'ip' => sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? '')),
        'user_agent' => sanitize_text_field((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')),
    ];
}

function rb_record_user_legal_acceptance(int $user_id, array $data): void
{
    if ($user_id > 0) update_user_meta($user_id, 'rb_legal_acceptance', $data);
}

function rb_record_order_legal_acceptance(int $order_id, string $context): void
{
    if ($order_id > 0) update_post_meta($order_id, 'rb_legal_acceptance', rb_legal_acceptance_data($context));
}

function rb_legal_header(string $eyebrow, string $title, string $intro = ''): void
{
    echo '<section class="legal-hero"><div><span class="eyebrow">' . esc_html($eyebrow) . '</span><h1>' . esc_html($title) . '</h1>';
    if ($intro !== '') echo '<p>' . esc_html($intro) . '</p>';
    echo '</div></section>';
}

function rb_legal_requisites_table(array $s): string
{
    $rows = [
        'Продавец и оператор данных' => $s['legal_name'], 'ИНН' => $s['inn'], 'ОГРНИП' => $s['ogrnip'],
        'Дата регистрации' => $s['registration_date'], 'Система налогообложения' => $s['tax_system'],
        'Основной ОКВЭД' => $s['okved'], 'Адрес регистрации' => $s['registered_address'],
        'Адрес для претензий и возвратов' => $s['return_address'], 'Электронная почта' => $s['email'],
        'Телефон' => rb_format_phone((string) $s['phone']), 'Банк' => $s['bank_name'], 'БИК' => $s['bik'],
        'Корреспондентский счет' => $s['correspondent_account'], 'Расчетный счет' => $s['settlement_account'],
    ];
    $html = '<div class="legal-table" role="table">';
    foreach ($rows as $label => $value) if ((string) $value !== '') $html .= '<div role="row"><span role="cell">' . esc_html($label) . '</span><strong role="cell">' . esc_html((string) $value) . '</strong></div>';
    return $html . '</div>';
}

function rb_render_legal_page(string $type): void
{
    $s = rb_legal_settings();
    $email = '<a href="mailto:' . esc_attr($s['email']) . '">' . esc_html($s['email']) . '</a>';
    if ($type === 'requisites') {
        rb_legal_header('Информация о продавце', 'Реквизиты', 'Данные индивидуального предпринимателя, принимающего заказы на сайте Roastberry Coffee Roasters.');
        echo '<section class="section legal-content">' . rb_legal_requisites_table($s) . '<aside class="legal-callout"><strong>Для договоров и счетов</strong><p>Полные банковские реквизиты указываются в выставленном счете или договоре. Юридически значимые обращения и возвраты принимаются по опубликованным контактам и адресу.</p></aside></section>';
        return;
    }
    if ($type === 'privacy') {
        rb_legal_header('Редакция от ' . $s['policy_version'], 'Политика конфиденциальности', 'Как Roastberry Coffee Roasters получает, использует, хранит и защищает персональные данные.');
        ?>
        <section class="section legal-content">
            <h2>1. Общие положения</h2><p>Оператор персональных данных — <?= esc_html($s['legal_name']) ?>, ИНН <?= esc_html($s['inn']) ?>, ОГРНИП <?= esc_html($s['ogrnip']) ?>. Политика применяется к сайту <?= esc_html(wp_parse_url(home_url(), PHP_URL_HOST)) ?>, личным кабинетам, заказам, обращениям и заявкам.</p>
            <h2>2. Какие данные обрабатываются</h2><p>ФИО, телефон, электронная почта, адрес и пункт доставки, сведения об организации и ее представителе, состав и история заказов, сведения об оплате без хранения полных реквизитов банковской карты, а также IP-адрес, cookie, сведения о браузере и действиях на сайте.</p>
            <h2>3. Цели и основания</h2><p>Данные используются для регистрации и работы кабинета, заключения и исполнения договора, оплаты, доставки, возврата, поддержки, выполнения требований налогового и бухгалтерского учета, защиты прав сторон и обеспечения безопасности сайта. Основания: согласие субъекта, заключение и исполнение договора, обязанности оператора по закону и законный интерес в защите сервиса.</p>
            <h2>4. Получатели и обработчики</h2><p>В необходимом объеме данные могут передаваться хостинг- и почтовому провайдеру, YooKassa для платежа, СДЭК для доставки, оператору фискальных данных, банкам и государственным органам в установленных законом случаях. Карты Яндекса получают технические данные при загрузке виджета. Данные не продаются.</p>
            <h2>5. Хранение и безопасность</h2><p>Первичная запись и хранение данных граждан РФ выполняются в базах данных на территории России. Срок зависит от цели: данные кабинета хранятся до его удаления, сведения о заказах и расчетах — в сроки обязательного учета и исковой давности, доказательства согласий — до окончания цели и применимых сроков защиты прав. Оператор применяет разграничение доступа, резервное копирование, обновления, защищенное соединение и журналирование действий.</p>
            <h2>6. Права пользователя</h2><p>Можно запросить сведения об обработке, уточнение, блокирование или удаление данных, отозвать согласие и возразить против обработки. Обращение направляется на <?= $email ?> с указанием ФИО, контакта и сути требования. Оператор вправе запросить сведения для подтверждения личности.</p>
            <h2>7. Cookie и изменения</h2><p>Необходимые cookie обеспечивают корзину, авторизацию и безопасность. Аналитические cookie могут применяться только при наличии правового основания. Новая редакция политики действует с момента публикации; дата редакции указана в начале страницы.</p>
        </section><?php return;
    }
    if ($type === 'personal-data-consent') {
        rb_legal_header('Отдельный документ', 'Согласие на обработку персональных данных', 'Согласие предоставляется отдельной отметкой в форме и может быть отозвано.');
        ?><section class="section legal-content"><p>Я свободно, своей волей и в своем интересе даю <?= esc_html($s['legal_name']) ?>, ИНН <?= esc_html($s['inn']) ?>, ОГРНИП <?= esc_html($s['ogrnip']) ?>, согласие на обработку данных, указанных мной на сайте: ФИО, телефона, электронной почты, адреса и параметров доставки, сведений о компании и ее представителе, заказах и обращениях, а также технических идентификаторов, необходимых для безопасности и доказательства согласия.</p>
        <h2>Цели и действия</h2><p>Цели: создание кабинета, обработка заявки или заказа, оплата, доставка, возврат, поддержка и исполнение требований закона. Разрешаю сбор, запись, систематизацию, накопление, хранение, уточнение, использование, передачу необходимым обработчикам, обезличивание, блокирование и уничтожение с автоматизацией или без нее.</p>
        <h2>Передача обработчикам</h2><p>Данные в необходимом объеме могут получать хостинг- и почтовый провайдер, YooKassa, СДЭК, оператор фискальных данных и иные лица, привлеченные для исполнения заказа. Состав и условия раскрыты в <?= rb_legal_link('privacy', 'Политике конфиденциальности') ?>.</p>
        <h2>Срок и отзыв</h2><p>Согласие действует до достижения целей либо отзыва, если закон не требует дальнейшего хранения. Отзыв направляется на <?= $email ?>. Отзыв не влияет на законность предыдущей обработки и не прекращает обработку, необходимую для исполнения закона или уже заключенного договора.</p></section><?php return;
    }
    if ($type === 'returns') {
        rb_legal_header('Покупателям', 'Возврат товара и денежных средств', 'Порядок отказа от дистанционной покупки, возврата качественного и ненадлежащего товара.');
        ?><section class="section legal-content"><h2>Отказ до и после получения</h2><p>Покупатель вправе отказаться от дистанционного заказа до передачи товара, а после передачи — в течение 7 календарных дней. Если письменная информация о порядке и сроках возврата не была предоставлена при доставке, срок составляет 3 месяца.</p>
        <h2>Товар надлежащего качества</h2><p>Необходимо сохранить товарный вид, потребительские свойства и документ, подтверждающий покупку. Отсутствие документа не лишает права ссылаться на иные доказательства. Вскрытые упаковки пищевой продукции обычно не отвечают условию сохранения товарного вида и свойств; это не ограничивает права при недостатке, несоответствии описанию или ошибке продавца.</p>
        <h2>Недостаток или ошибка в заказе</h2><p>Сообщите о проблеме как можно скорее, приложите номер заказа и фотографии. Для товара ненадлежащего качества применяются требования и сроки, установленные Законом РФ «О защите прав потребителей»; необходимые расходы на возврат несет продавец.</p>
        <h2>Как оформить</h2><ol><li>Направьте заявление на <?= $email ?>: ФИО, номер заказа, товар, причина, требование и удобный контакт.</li><li>После согласования передайте товар по адресу: <?= esc_html($s['return_address']) ?>.</li><li>Для возврата укажите платежные данные, необходимые выбранному способу возврата. Не отправляйте полные данные банковской карты.</li></ol>
        <h2>Срок возврата денег</h2><p>Деньги возвращаются не позднее 10 календарных дней со дня получения требования. При возврате качественного товара продавец вправе удержать расходы на обратную доставку. Онлайн-платеж возвращается, как правило, тем же способом, которым он был совершен.</p></section><?php return;
    }
    if ($type === 'public-offer') {
        rb_legal_header('Редакция от ' . $s['policy_version'], 'Публичная оферта', 'Условия дистанционной розничной продажи товаров Roastberry Coffee Roasters.');
        ?><section class="section legal-content"><h2>1. Стороны и предмет</h2><p><?= esc_html($s['legal_name']) ?>, ИНН <?= esc_html($s['inn']) ?>, ОГРНИП <?= esc_html($s['ogrnip']) ?>, предлагает дееспособному физическому лицу приобрести товары для личных нужд. Для организаций применяются счет, договор и согласованные условия.</p>
        <h2>2. Заключение договора</h2><p>Описание товара, цена и доступность указаны в карточке и корзине. Договор заключается после заполнения обязательных полей, отдельного принятия оферты и отправки заказа. Подтверждение с номером заказа направляется на указанную почту; продавец вправе уточнить наличие и существенные параметры до подтверждения исполнения.</p>
        <h2>3. Цена и оплата</h2><p>Цены указаны в рублях. Доступны оплата после оформления по согласованию с менеджером и, при подключенной YooKassa, онлайн-оплата способами, показанными платежной формой. Электронный кассовый чек направляется по указанным контактам. Стоимость доставки рассчитывается отдельно и показывается до отправки заказа.</p>
        <h2>4. Доставка</h2><p>Заказ обрабатывается <?= esc_html($s['order_processing_time']) ?>. Доступны самовывоз из выбранной кофейни, самовывоз с производства и доставка СДЭК до выбранного ПВЗ. Срок СДЭК является расчетным и отсчитывается после передачи отправления перевозчику. Риск случайного повреждения переходит при передаче товара покупателю или получателю.</p>
        <h2>5. Приемка, отмена и возврат</h2><p>При получении проверьте количество, комплектность и внешнее состояние. Порядок отказа и возврата опубликован на странице <?= rb_legal_link('returns', '«Возврат товара и денежных средств»') ?> и является частью оферты.</p>
        <h2>6. Ответственность и споры</h2><p>Стороны отвечают в пределах закона. Продавец не отвечает за задержки перевозчика, но помогает с розыском отправления. Претензия направляется на <?= $email ?> или по адресу <?= esc_html($s['return_address']) ?>. Потребитель сохраняет все права, которые не могут быть ограничены договором.</p>
        <h2>7. Реквизиты</h2><?= rb_legal_requisites_table($s) ?></section><?php return;
    }
    rb_legal_header('Правила сервиса', 'Пользовательское соглашение', 'Условия использования сайта и личного кабинета.');
    ?><section class="section legal-content"><h2>1. Аккаунт и доступ</h2><p>Пользователь указывает достоверные данные, подтверждает доступ к электронной почте и отвечает за сохранность средств авторизации. Действия после подтверждения одноразовым кодом считаются действиями пользователя, пока оператор не уведомлен о несанкционированном доступе.</p>
    <h2>2. Допустимое использование</h2><p>Запрещены вмешательство в работу сайта, обход ограничений, автоматизированный сбор данных, размещение вредоносного кода, оформление фиктивных заказов и использование чужих данных.</p>
    <h2>3. Материалы сайта</h2><p>Тексты, фотографии, товарные знаки и дизайн охраняются законом. Использование за пределами личного просмотра допускается с разрешения правообладателя.</p>
    <h2>4. Уведомления и документы</h2><p>Оператор может направлять сервисные сообщения о входе, заказе, оплате и доставке на указанные контакты. Рекламные сообщения требуют отдельного согласия. К отношениям также применяются <?= rb_legal_link('privacy', 'Политика конфиденциальности') ?> и <?= rb_legal_link('public-offer', 'Публичная оферта') ?>.</p>
    <h2>5. Прекращение и обращения</h2><p>Пользователь вправе прекратить использование сайта и запросить удаление кабинета. Оператор может ограничить доступ при нарушении соглашения или угрозе безопасности. Обращения направляются на <?= $email ?>.</p></section><?php
}
