<section class="account-page account-page--business">
        <aside>
            <h1>ЛК юрлица</h1>
            <a href="#b-catalog">Каталог</a>
            <a href="#b-orders">Заказы</a>
            <a href="#b-company">Мои компании</a>
            <a href="#b-terms">Условия заказа</a>
        </aside>
        <div class="account-content">
            <section id="b-company"><h2>Регистрация компании</h2><div class="form-grid"><input placeholder="ФИО"><input placeholder="Почта"><input placeholder="Телефон"><input placeholder="Город"><input placeholder="Название компании"><input placeholder="ИНН"></div><button class="button button--small">Добавить компанию</button></section>
            <section id="b-catalog"><h2>Каталог для юрлиц</h2><div class="category-row"><a>Кофе</a><a>Чай</a><a>Дрипы</a><a>Капсулы</a><a>Сиропы</a><a>Аксессуары</a></div><div class="product-grid product-grid--b2b"><?php foreach (array_slice($products, 0, 3) as $product) product_card($product); ?></div></section>
            <section id="b-terms"><h2>Условия заказа</h2><p>При покупке одного и того же лота кофе от 10 кг действует скидка 10%, от 25 кг - 20%.</p></section>
        </div>
    </section>
